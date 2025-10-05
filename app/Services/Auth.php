<?php
namespace App\Services;

use PDO;
use Throwable;

class Auth
{
    /** Intenta login con POST (valida CSRF). Devuelve [ok, errors[]]. */
    public static function attemptFromPost(): array
    {
        $errors = [];
        if (!verify_csrf()) $errors[] = 'Token inválido. Refrescá la página.';

        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[]='Email inválido.';
        if ($pass === '')                               $errors[]='Ingresá tu clave.';
        if ($errors) return [false, $errors];

        try {
            $pdo  = db();
            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, oauth_provider FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            // Caso: cuenta creada por Google (sin password_hash)
            if ($u && empty($u['password_hash']) && ($u['oauth_provider'] === 'google')) {
                return [false, ['Esta cuenta usa Google. Iniciá con “Continuar con Google”.']];
            }

            if ($u && !empty($u['password_hash']) && password_verify($pass, $u['password_hash'])) {
                // éxito
                session_regenerate_id(true);
                $_SESSION['uid']   = (int)$u['id'];
                $_SESSION['uname'] = $u['name'] ?: $u['email'];
                $_SESSION['email'] = $u['email'];
                $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$_SESSION['uid']]);
                return [true, []];
            }

            return [false, ['Credenciales inválidas.']];
        } catch (Throwable $e) {
            error_log('[Auth::attemptFromPost] '.$e->getMessage());
            return [false, ['Error de servidor. Intentá más tarde.']];
        }
    }

    /** Devuelve la URL de destino post-login y limpia el back_to. */
    public static function intended(string $fallback='/cuenta'): string
    {
        $back = $_SESSION['back_to'] ?? $fallback;
        unset($_SESSION['back_to']);
        return $back;
    }

    /** Cierra sesión de forma segura (ya tenés un handler POST /logout que usa esto). */
    public static function logout(): void
    {
        session_regenerate_id(true);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
