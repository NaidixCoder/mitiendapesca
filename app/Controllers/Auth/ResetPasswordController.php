<?php
namespace App\Controllers\Auth;

use App\Services\Auth\PasswordResetService;

class ResetPasswordController
{
    public function show(): void
    {
        $token = (string)($_GET['token'] ?? '');
        $svc = new PasswordResetService(db());
        $payload = $svc->validateToken($token);

        if (!$payload) {
            flash('error', 'Link inválido o vencido.');
            redirect('/login');
            return;
        }

        render_page('auth/reset_password', [
            'pageId' => 'auth-reset',
            'token'  => $token,
            'email'  => $payload['email'] ?? '',
        ]);
    }

    public function update(): void
    {
        verify_csrf_or_abort();

        // Anti-bot (solo tiempo mínimo). Evitamos bloquear por honeypot para no castigar autofill/gestores.
        if (function_exists('antibot_check')) {
            $ab = antibot_check('reset_password', 2);
            if ($ab === 'fast_submit') {
                http_response_code(429);
                render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'fast_submit']);
                return;
            }
        }

        $ip = client_ip();
        $retry = rate_limit_check('pwreset:do:ip:' . $ip, 10, 1800);
        if ($retry !== false) {
            header('Retry-After: ' . (int)$retry);
            http_response_code(429);
            render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'password_reset_limit', 'retry' => (int)$retry]);
            return;
        }

        $token = (string)($_POST['token'] ?? '');
        $p1 = (string)($_POST['password'] ?? '');
        $p2 = (string)($_POST['password2'] ?? '');

        if (strlen($p1) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            redirect('/reset-clave?token=' . urlencode($token));
            return;
        }
        if ($p1 !== $p2) {
            flash('error', 'Las contraseñas no coinciden.');
            redirect('/reset-clave?token=' . urlencode($token));
            return;
        }

        $svc = new PasswordResetService(db());
        if ($svc->resetPassword($token, $p1)) {
            flash('ok', 'Contraseña actualizada. Ya podés iniciar sesión.');
            redirect('/login');
            return;
        }

        flash('error', 'No se pudo restablecer la contraseña. El link puede estar vencido.');
        redirect('/login');
    }
}


