<?php
namespace App\Services\Auth;

use PDO;
use App\Mail\Auth\PasswordResetMail;

class PasswordResetService
{
    public function __construct(private PDO $db) {}

    /**
     * Auditoría silenciosa (opcional). No rompe UX si AUDIT está apagado o falla DB.
     */
    private function auditEvent(string $action, ?int $userId = null, array $meta = []): void
    {
        if (!function_exists('audit')) return;

        try {
            // Enriquecemos con contexto de request (sin depender del controlador)
            if (!array_key_exists('ip', $meta)) {
                $meta['ip'] = function_exists('client_ip') ? (string)client_ip() : (string)($_SERVER['REMOTE_ADDR'] ?? '');
            }
            if (!array_key_exists('ua', $meta)) {
                $meta['ua'] = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
            }

            audit($action, 'user', $userId, $meta);
        } catch (\Throwable $e) {
            // silenciar
        }
    }

    /**
     * Solicita reset (anti-enumeración): si no existe, no hace nada.
     * @return bool true si se intentó enviar email (existía user y se generó / notificó)
     */
    public function requestReset(string $email, string $ip = '', string $ua = ''): bool
    {
        $email = trim(mb_strtolower($email));
        if ($email === '') return false;

        $stmt = $this->db->prepare('SELECT id, email, password_hash, oauth_provider FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            return false; // anti-enumeración: no indicamos nada
        }

        $uid = (int)$u['id'];

        $this->auditEvent('password_reset_requested', $uid, [
            'via'   => 'email_link',
            'email' => $email,
            'ip'    => (string)$ip,
            'ua'    => (string)$ua,
        ]);

        // Cuenta OAuth-only (sin password)
        if (empty($u['password_hash']) && !empty($u['oauth_provider'])) {
            PasswordResetMail::sendOauthOnlyNotice($email);

            $this->auditEvent('password_reset_oauth_notice', $uid, [
                'via'      => 'oauth_notice',
                'email'    => $email,
                'provider' => (string)$u['oauth_provider'],
                'ip'       => (string)$ip,
                'ua'       => (string)$ua,
            ]);
            return true;
        }

        // Limpiar tokens viejos del mismo email (higiene)
        $this->db->prepare('DELETE FROM password_resets WHERE email = ? AND (used_at IS NOT NULL OR expires_at < NOW())')
            ->execute([$email]);

        $token = bin2hex(random_bytes(32)); // 64 hex
        $thash = hash('sha256', $token);
        $exp   = (new \DateTime('+60 minutes'))->format('Y-m-d H:i:s');

        $ins = $this->db->prepare(
            'INSERT INTO password_resets (user_id, email, token_hash, expires_at, ip, ua)
             VALUES (?,?,?,?,?,?)'
        );
        $ins->execute([
            $uid,
            $email,
            $thash,
            $exp,
            substr((string)$ip, 0, 64),
            substr((string)$ua, 0, 200),
        ]);

        PasswordResetMail::sendReset($email, $token);

        $this->auditEvent('password_reset_token_sent', $uid, [
            'via'   => 'email_link',
            'email' => $email,
            'exp'   => $exp,
            'ip'    => (string)$ip,
        ]);

        return true;
    }

    /**
     * Valida token y devuelve payload para UI.
     * @return array|null ['reset_id'=>int,'user_id'=>int,'email'=>string,'expires_at'=>string]
     */
    public function validateToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 20) return null;

        $hash = hash('sha256', $token);

        $stmt = $this->db->prepare(
            'SELECT id, user_id, email, expires_at, used_at
             FROM password_resets
             WHERE token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        if (!empty($row['used_at'])) return null;
        if (strtotime((string)$row['expires_at']) < time()) return null;

        return [
            'reset_id'   => (int)$row['id'],
            'user_id'    => (int)($row['user_id'] ?? 0),
            'email'      => (string)$row['email'],
            'expires_at' => (string)$row['expires_at'],
        ];
    }

    /**
     * Aplica nueva contraseña (one-time token) y marca used_at.
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $payload = $this->validateToken($token);
        if (!$payload) return false;

        $uid = (int)$payload['user_id'];
        if ($uid <= 0) return false;

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!$hash) return false;

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'UPDATE users SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?'
            )->execute([$hash, $uid]);

            $this->db->prepare(
                'UPDATE password_resets SET used_at = NOW() WHERE id = ?'
            )->execute([(int)$payload['reset_id']]);

            // Invalidar "remember me" (sesiones persistentes)
            $this->db->prepare('DELETE FROM user_tokens WHERE user_id = ?')->execute([$uid]);

            $this->db->commit();

            // âœ… Audit DESPUÉS del commit (para no loguear eventos que no se consolidaron)
            $this->auditEvent('password_reset_completed', $uid, [
                'via'   => 'token',
                'email' => (string)($payload['email'] ?? ''),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            if (function_exists('log_error')) log_error('[PW_RESET] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambio de password estando logueado.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || empty($u['password_hash'])) return false;

        if (!password_verify($currentPassword, (string)$u['password_hash'])) return false;

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!$hash) return false;

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'UPDATE users SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?'
            )->execute([$hash, $userId]);

            $this->db->prepare('DELETE FROM user_tokens WHERE user_id = ?')->execute([$userId]);

            $this->db->commit();

            // âœ… Audit post-commit
            $this->auditEvent('password_changed', $userId, ['via' => 'account_security']);

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            if (function_exists('log_error')) log_error('[PW_CHANGE] ' . $e->getMessage());
            return false;
        }
    }

    public function getPasswordChangedAt(int $userId): ?string
    {
        $stmt = $this->db->prepare('SELECT password_changed_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $v = $row['password_changed_at'] ?? null;
        return $v ? (string)$v : null;
    }
}

