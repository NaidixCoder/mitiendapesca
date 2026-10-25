<?php
namespace App\Mail\Auth;

class PasswordResetMail
{
    public static function sendReset(string $to, string $token): bool
    {
        $subject = 'Restablecer contraseña';
        $link = base_url('/reset-clave?token=' . urlencode($token));

        $body =
            "Hola!\n\n" .
            "Para restablecer tu contraseña, entrá acá:\n$link\n\n" .
            "Si no fuiste vos, podés ignorar este email.\n";

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'From: Mi Tienda Pesca <no-reply@mitiendapesca.com>';

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));

        if (function_exists('log_error')) {
            log_error('[EMAIL] password_reset send to=' . $to . ' ok=' . ($ok ? '1' : '0'));
        } else {
            error_log('[EMAIL] password_reset send to=' . $to . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

    public static function sendOauthOnlyNotice(string $to): bool
    {
        $subject = 'Acceso con Google';
        $link = base_url('/login');

        $body =
            "Hola!\n\n" .
            "Tu cuenta está vinculada a Google, por lo que no usa contraseña.\n" .
            "Ingresá desde el login con Google:\n$link\n\n";

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'From: Mi Tienda Pesca <no-reply@mitiendapesca.com>';

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));

        if (function_exists('log_error')) {
            log_error('[EMAIL] oauth_notice send to=' . $to . ' ok=' . ($ok ? '1' : '0'));
        } else {
            error_log('[EMAIL] oauth_notice send to=' . $to . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }
}
