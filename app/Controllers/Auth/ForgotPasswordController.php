<?php
namespace App\Controllers\Auth;

use App\Services\Auth\PasswordResetService;

class ForgotPasswordController
{
    public function show(): void
    {
        render_page('auth/forgot_password', ['pageId' => 'auth-forgot']);
    }

    public function send(): void
    {
        verify_csrf_or_abort();

        // Anti-bot (tiempo mínimo) + rate limit.
        // Nota: evitamos bloquear por honeypot acá, porque navegadores/password managers pueden autocompletar
        // inputs ocultos y generar falsos positivos. En endpoints públicos, el rate-limit ya corta el abuso.
        if (function_exists('antibot_check')) {
            $ab = antibot_check('forgot_password', 2);
            if ($ab === 'fast_submit') {
                http_response_code(429);
                render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'fast_submit']);
                return;
            }
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $ip    = client_ip();
        $ua    = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);

        // Rate limit (anti-spam)
        // - IP: 10 / 30min
        // - email+ip: 3 / 30min
        $retry = rate_limit_check('pwreset:req:ip:' . $ip, 10, 1800);
        if ($retry !== false) {
            header('Retry-After: ' . (int)$retry);
            http_response_code(429);
            render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'password_reset_limit', 'retry' => (int)$retry]);
            return;
        }
        $retry = rate_limit_check('pwreset:req:' . mb_strtolower($email) . ':' . $ip, 3, 1800);
        if ($retry !== false) {
            header('Retry-After: ' . (int)$retry);
            http_response_code(429);
            render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'password_reset_limit', 'retry' => (int)$retry]);
            return;
        }

        $svc = new PasswordResetService(db());
        $svc->requestReset($email, $ip, $ua);

        // Anti-enumeración: mensaje neutro SIEMPRE
        flash('ok', 'Si el email existe, te enviamos un link para restablecer la contraseña.');
        redirect('/olvide-clave');
    }
}


