<?php
namespace App\Mail\Core;

/**
 * Mailer mínimo (sin dependencias nuevas)
 * - Usa mail() (como el resto del proyecto) pero soporta HTML.
 * - Centraliza From y logging.
 */
final class Mailer
{
    public static function sendHtml(string $to, string $subject, string $html, ?string $text = null): bool
    {
        $fromEmail = (string)env('MAIL_FROM_EMAIL', 'no-reply@mitiendapesca.com');
        $fromName  = (string)env('MAIL_FROM_NAME', 'Mi Tienda Pesca');

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>';

        // Importante: mail() admite headers como string. No agregamos Return-Path por compatibilidad.
        $ok = @mail($to, $subject, $html, implode("\r\n", $headers));

        if (function_exists('log_error')) {
            log_error('[EMAIL] send to=' . $to . ' ok=' . ($ok ? '1' : '0') . ' subject=' . $subject);
        } else {
            error_log('[EMAIL] send to=' . $to . ' ok=' . ($ok ? '1' : '0') . ' subject=' . $subject);
        }

        return $ok;
    }

    /**
     * Envío HTML con adjuntos (MVP): multipart/mixed con HTML + N adjuntos.
     *
     * @param array<int,array{filename:string,contentType:string,bytes:string}> $attachments
     */
    public static function sendHtmlWithAttachments(string $to, string $subject, string $html, array $attachments, ?string $text = null): bool
    {
        $fromEmail = (string)env('MAIL_FROM_EMAIL', 'no-reply@mitiendapesca.com');
        $fromName  = (string)env('MAIL_FROM_NAME', 'Mi Tienda Pesca');

        $boundary = 'mix_' . bin2hex(random_bytes(12));

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

        // Cuerpo
        $body = "";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $html . "\r\n";

        foreach ($attachments as $att) {
            $filename = (string)($att['filename'] ?? 'archivo');
            $contentType = (string)($att['contentType'] ?? 'application/octet-stream');
            $bytes = (string)($att['bytes'] ?? '');

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$contentType}; name=\"" . addslashes($filename) . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"" . addslashes($filename) . "\"\r\n\r\n";
            $body .= chunk_split(base64_encode($bytes)) . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));

        if (function_exists('log_error')) {
            log_error('[EMAIL] send+attach to=' . $to . ' ok=' . ($ok ? '1' : '0') . ' subject=' . $subject);
        } else {
            error_log('[EMAIL] send+attach to=' . $to . ' ok=' . ($ok ? '1' : '0') . ' subject=' . $subject);
        }

        return $ok;
    }

    private static function encodeName(string $name): string
    {
        // UTF-8 safe for From name
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }

    /** Renderiza una plantilla de email dentro de app/Views/emails */
    public static function render(string $template, array $vars = []): string
    {
        $file = base_path('app/Views/emails/' . $template . '.php');
        if (!is_file($file)) {
            return '<p>Plantilla no encontrada: ' . e($template) . '</p>';
        }
        if (!empty($vars)) extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    public static function wrapLayout(string $title, string $contentHtml): string
    {
        return self::render('layout', [
            'title' => $title,
            'content' => $contentHtml,
            'brandName' => (string)env('MAIL_FROM_NAME', 'Mi Tienda Pesca'),
        ]);
    }
}
