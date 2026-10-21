<?php

namespace App\Controllers\Auth;

use App\Services\Cart\CartWriteService;
use PDO;
use Throwable;

class GoogleController
{
    public function oauth(): void
    {
        $wantJson = $this->wantsJson();

        $idToken = $_POST['id_token'] ?? '';
        if (!is_string($idToken) || trim($idToken) === '') {
            if (function_exists('auth_metrics_log')) {
                auth_metrics_log('login_failed', 'google', 'missing_id_token', null, null);
            }
            $this->respond($wantJson, false, 'Falta id_token.');
            return;
        }

        try {
            // Validar token con Google
            $info = $this->fetchGoogleTokenInfo($idToken);
            if (!$info) {
                $this->respond($wantJson, false, 'Token inválido.');
                return;
            }

            $email  = (string)($info['email'] ?? '');
            $name   = (string)($info['name'] ?? '');
            $avatar = (string)($info['picture'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->respond($wantJson, false, 'Email inválido.');
                return;
            }

            // Upsert user
            [$uid, $role] = $this->upsertUser($email, $name, $avatar);

            // Sesión
            session_regenerate_id(true);
            $_SESSION['uid']     = $uid;
            $_SESSION['uname']   = $name ?: $email;
            $_SESSION['email']   = $email;
            $_SESSION['uavatar'] = $avatar ?: '';
            $_SESSION['urole']   = $role ?: 'customer';
            $_SESSION['email_verified']    = 1;
            $_SESSION['email_verified_at'] = date('Y-m-d H:i:s');

            // session_version (revocación global)
            try {
                $stsv = db()->prepare('SELECT session_version FROM users WHERE id=? LIMIT 1');
                $stsv->execute([$uid]);
                $svrow = $stsv->fetch(\PDO::FETCH_ASSOC) ?: [];
                $_SESSION['sv'] = (int)($svrow['session_version'] ?? 1);
            } catch (\Throwable $e) {
                $_SESSION['sv'] = 1;
            }


            // Marca de cambio de contraseña (best-effort)
            try {
                $st = db()->prepare('SELECT password_changed_at FROM users WHERE id=? LIMIT 1');
                $st->execute([$uid]);
                $row = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
                $_SESSION['_pwd_changed_at'] = (string)($row['password_changed_at'] ?? '');
            } catch (\Throwable $e) {
                $_SESSION['_pwd_changed_at'] = '';
            }


            if (function_exists('auth_metrics_log')) {
                auth_metrics_log('login_success', 'google', null, (int)($_SESSION['uid'] ?? 0), (string)($_SESSION['email'] ?? ''));
            }

            // Destino post-login (prioridad: next explícito, luego /cuenta)
            $redirectTo = base_url('/cuenta');
            $next = sanitize_next_url((string)($_POST['next'] ?? ''), '');
            if ($next !== '') {
                $redirectTo = base_url($next);
            }

            // Merge de carrito (si corresponde) + UX de decisión en /carrito
            try {
                $res = (new CartWriteService(db()))->prepareMergeOnLogin((int)$uid);
                if ($res === 'conflict') {
                    $redirectTo = base_url('/carrito?merge=1');
                }
            } catch (\Throwable $e) {
                // best-effort: no bloqueamos login
            }

            $this->respond($wantJson, true, null, $redirectTo);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Google OAuth error: ' . $e->getMessage());
            }
            if (function_exists('auth_metrics_log')) {
                auth_metrics_log('login_failed', 'google', 'error', null, null);
            }
            $this->respond($wantJson, false, 'Error al validar Google.');
        }
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

        return $xhr || stripos($accept, 'application/json') !== false;
    }

    private function fetchGoogleTokenInfo(string $idToken): ?array
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $resp = @file_get_contents($url);
        if ($resp === false) return null;

        $info = json_decode($resp, true);
        return is_array($info) ? $info : null;
    }

    /**
     * @return array{0:int,1:string} [uid, role]
     */
    private function upsertUser(string $email, string $name, string $avatar): array
    {
        /** @var \PDO $pdo */
        $pdo = db();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT id, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $uid  = (int)$user['id'];
            $role = (string)($user['role'] ?? 'customer');

            $pdo->prepare(
                'UPDATE users
                 SET last_login_at = NOW(),
                     avatar_url = COALESCE(?, avatar_url),
                     oauth_provider = "google"
                 WHERE id = ?'
            )->execute([$avatar ?: null, $uid]);

            $pdo->commit();
            return [$uid, $role];
        }

        $pdo->prepare(
            $this->buildInsertSqlForNewUser()
        )->execute($this->buildInsertParamsForNewUser($name ?: $email, $email, $avatar));

        $uid = (int)$pdo->lastInsertId();
        $pdo->commit();

        return [$uid, 'customer'];
    }

    private static ?bool $usersHasCampaignId = null;

    private function usersHasCampaignColumn(): bool
    {
        if (self::$usersHasCampaignId !== null) return self::$usersHasCampaignId;
        try {
            $st = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='marketing_campaign_id'");
            $st->execute();
            self::$usersHasCampaignId = ((int)$st->fetchColumn() > 0);
            return self::$usersHasCampaignId;
        } catch (\Throwable $e) {
            self::$usersHasCampaignId = false;
            return false;
        }
    }

    private function getSessionMarketingCampaignId(): int
    {
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                return (int)($_SESSION['marketing_campaign_id'] ?? 0);
            }
        } catch (\Throwable $e) {
        }
        return 0;
    }

    private function buildInsertSqlForNewUser(): string
    {
        $hasCol = $this->usersHasCampaignColumn();
        $cid = $this->getSessionMarketingCampaignId();
        if ($hasCol && $cid > 0) {
            return 'INSERT INTO users (name, email, avatar_url, oauth_provider, email_verified, created_at, marketing_campaign_id)
                    VALUES (?,?,?,?,1,NOW(),?)';
        }

        return 'INSERT INTO users (name, email, avatar_url, oauth_provider, email_verified, created_at)
                VALUES (?,?,?,?,1,NOW())';
    }

    private function buildInsertParamsForNewUser(string $name, string $email, string $avatar): array
    {
        $params = [$name, $email, $avatar ?: null, 'google'];
        $hasCol = $this->usersHasCampaignColumn();
        $cid = $this->getSessionMarketingCampaignId();
        if ($hasCol && $cid > 0) {
            $params[] = $cid;
        }
        return $params;
    }

    private function respond(bool $json, bool $ok, ?string $error = null, ?string $redirect = null): void
    {
        $redirect = $redirect ?: base_url('/login');

        if ($json) {
            json([
                'ok'       => $ok,
                'error'    => $ok ? null : ($error ?: 'Error'),
                'redirect' => $redirect,
            ], $ok ? 200 : 400);
            exit;
        }

        if (!$ok && $error && function_exists('flash')) {
            flash('error', $error);
        }

        redirect($redirect);
    }
}
