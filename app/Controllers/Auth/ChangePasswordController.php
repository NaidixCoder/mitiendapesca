<?php
namespace App\Controllers\Auth;

use App\Services\Auth\PasswordResetService;

class ChangePasswordController
{
    /**
     * @return array{can_change_password:bool, access_label:string, access_hint:string, password_changed_at:?string}
     */
    private function securityMeta(int $uid): array
    {
        $meta = [
            'can_change_password' => true,
            'access_label' => 'Email y contraseña',
            'access_hint' => 'Podés cambiar tu contraseña desde esta sección.',
            'password_changed_at' => null,
        ];

        if ($uid <= 0) {
            return $meta;
        }

        try {
            $stmt = db()->prepare('SELECT password_hash, oauth_provider, password_changed_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            $hasPassword = !empty($row['password_hash']);
            $oauthProvider = trim((string)($row['oauth_provider'] ?? ''));
            $isGoogle = $oauthProvider === 'google';
            $passwordChangedAt = isset($row['password_changed_at']) && $row['password_changed_at'] !== ''
                ? (string)$row['password_changed_at']
                : null;

            $meta['password_changed_at'] = $passwordChangedAt;

            if (!$hasPassword && $isGoogle) {
                $meta['can_change_password'] = false;
                $meta['access_label'] = 'Google';
                $meta['access_hint'] = 'Tu acceso se gestiona con Google.';
                return $meta;
            }

            if ($hasPassword && $isGoogle) {
                $meta['can_change_password'] = true;
                $meta['access_label'] = 'Google y contraseña';
                $meta['access_hint'] = 'Podés entrar con Google o con contraseña local.';
                return $meta;
            }
        } catch (\Throwable $e) {
            // fallback no bloqueante
        }

        return $meta;
    }

    public function show(): void
    {
        auth_required('/cuenta/seguridad');
        $uid = (int)($_SESSION['uid'] ?? 0);
        render_page('cuenta/seguridad', [
            'pageId' => 'account-security',
            'securityMeta' => $this->securityMeta($uid),
        ]);
    }

    public function update(): void
    {
        auth_required('/cuenta/seguridad');
        verify_csrf_or_abort();

        if (function_exists('antibot_check') && function_exists('antibot_fields')) {
            $ab = antibot_check('change_password', 2);

            // En acciones autenticadas ignoramos honeypot (autofill/password managers)
            if ($ab === 'fast_submit') {
                http_response_code(429);
                render_page('errors/429', [
                    'pageId' => 'error-429',
                    'reason' => 'fast_submit'
                ]);
                return;
            }
        }

        $uid = (int)($_SESSION['uid'] ?? 0);
        $securityMeta = $this->securityMeta($uid);
        if (empty($securityMeta['can_change_password'])) {
            flash('warn', 'Tu acceso actual se gestiona con Google. No tenés contraseña local para cambiar.');
            redirect('/cuenta/seguridad');
            return;
        }
        $ip  = client_ip();

        $retry = rate_limit_check('pwchange:uid:' . $uid, 10, 1800);
        if ($retry !== false) {
            header('Retry-After: ' . (int)$retry);
            http_response_code(429);
            render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'password_change_limit', 'retry' => (int)$retry]);
            return;
        }
        $retry = rate_limit_check('pwchange:ip:' . $ip, 20, 1800);
        if ($retry !== false) {
            header('Retry-After: ' . (int)$retry);
            http_response_code(429);
            render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'password_change_limit', 'retry' => (int)$retry]);
            return;
        }

        $curr = (string)($_POST['current_password'] ?? '');
        $p1   = (string)($_POST['password'] ?? '');
        $p2   = (string)($_POST['password2'] ?? '');

        if (strlen($p1) < 8) {
            flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            redirect('/cuenta/seguridad');
            return;
        }
        if ($p1 !== $p2) {
            flash('error', 'Las nuevas contraseñas no coinciden.');
            redirect('/cuenta/seguridad');
            return;
        }

        $svc = new PasswordResetService(db());
        if (!$svc->changePassword($uid, $curr, $p1)) {
            flash('error', 'No se pudo cambiar la contraseña. Revisá tu contraseña actual.');
            redirect('/cuenta/seguridad');
            return;
        }

        // El cambio revoca todas las sesiones (incluida la actual).
        // Evitar redirigir a /cuenta con una cookie cuya session_version quedó obsoleta.
        \App\Services\Auth\Auth::logout();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        flash('ok', 'Contraseña cambiada. Iniciá sesión nuevamente con tu nueva contraseña.');
        redirect('/login');
    }
}


