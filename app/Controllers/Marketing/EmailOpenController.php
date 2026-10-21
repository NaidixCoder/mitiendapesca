<?php
namespace App\Controllers\Marketing;

use PDO;

/**
 * Etapa 4: Open tracking.
 *
 * Endpoint pixel 1x1: /email/open?t=TOKEN
 * Marca opened_at en email_send_logs (best-effort) y devuelve una imagen GIF.
 */
final class EmailOpenController
{
    public function open(): void
    {
        $token = trim((string)($_GET['t'] ?? ''));

        // Best-effort: si no hay token, igual devolvemos el pixel.
        if ($token !== '') {
            try {
                /** @var PDO $db */
                $db = db();
                $st = $db->prepare(
                    "UPDATE email_send_logs
                        SET opened_at = COALESCE(opened_at, NOW())
                      WHERE open_token = :t"
                );
                $st->execute([':t' => $token]);
            } catch (\Throwable $e) {
                // silencioso (tracking no debe romper nada)
            }
        }

        // 1x1 transparente (GIF)
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        // GIF 1x1 (43 bytes)
        echo base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
    }
}
