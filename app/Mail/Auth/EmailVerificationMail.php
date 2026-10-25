<?php
namespace App\Mail\Auth;

class EmailVerificationMail
{
    public static function send(string $to, string $token): bool
    {
        $subject = 'Verificá tu email';
        $link = base_url('/verificar-email?token=' . urlencode($token));

        $body = "Hola!\n\nVerificá tu email entrando acá:\n$link\n\n";

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'From: Mi Tienda Pesca <no-reply@mitiendapesca.com>';

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));

        // LOG DURO
        if (function_exists('log_error')) {
            log_error('[EMAIL] verify send to=' . $to . ' ok=' . ($ok ? '1' : '0'));
        } else {
            error_log('[EMAIL] verify send to=' . $to . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

}
