<?php
namespace App\Controllers\Shipping;

use App\Services\Shipping\CorreoArgentino\CorreoTrackingRunnerService;

/**
 * FIX518: Runner vía web (fallback) para Hostinger.
 *
 * Endpoint: /shipping/correo/run?token=XXXX
 *
 * Seguridad:
 * - Token secreto por ENV: CORREO_TRACKING_CRON_TOKEN
 * - Deshabilitado si no hay token configurado
 * - Rate limit agresivo
 * - Lock para evitar ejecuciones concurrentes
 */
final class CorreoWebRunnerController
{
    public function run(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $secret = trim((string)env('CORREO_TRACKING_CRON_TOKEN', ''));

        if ($secret === '') {
            http_response_code(404);
            exit('Not Found');
        }
        if ($token === '' || !hash_equals($secret, $token)) {
            http_response_code(403);
            exit('Forbidden');
        }

        if (function_exists('rate_limit_or_fail')) {
            rate_limit_or_fail('correowebrun:' . client_ip(), 2, 60);
        }

        $limit = (int)($_GET['limit'] ?? 50);
        $minm  = (int)($_GET['minm'] ?? 10);

        // Lock (storage/locks)
        $lockFile = BASE_PATH . '/storage/locks/correo_tracking.lock';
        if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0775, true);
        $fp = @fopen($lockFile, 'c+');
        if (!$fp) {
            http_response_code(500);
            exit('Lock error');
        }
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            http_response_code(429);
            exit('Busy');
        }

        try {
            $svc = new CorreoTrackingRunnerService(db());
            $summary = $svc->run($limit, $minm);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }
}
