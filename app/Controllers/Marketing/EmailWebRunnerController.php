<?php
namespace App\Controllers\Marketing;

use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Marketing\EmailAutomationSchedulerService;
use App\Services\Marketing\EmailBroadcastSchedulerService;
use App\Services\Marketing\EmailQueueWorkerService;

/**
 * Etapa 5: Runner vía web (fallback).
 *
 * Endpoint: /email/run?token=XXXX
 *
 * - Está DESHABILITADO por defecto.
 * - Se habilita desde Admin -> Ajustes -> Email Marketing.
 * - Útil como plan B si no tenés terminal/cron disponible.
 *
 * Seguridad:
 * - token secreto (hash_equals)
 * - rate limit agresivo
 * - límites de encolado/envío se respetan (settings)
 */
final class EmailWebRunnerController
{
    public function run(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));

        // Lee settings (best-effort: si no existe la tabla, queda deshabilitado)
        $db = db();
        $settings = new AppSettingsRepository($db);
        $enabled = $settings->get('email_marketing_web_runner_enabled', '0');
        $secret  = (string)($settings->get('email_marketing_web_runner_token', '') ?? '');

        if (!in_array($enabled, ['1', 'true', 'yes', 'on'], true)) {
            http_response_code(404);
            exit('Not Found');
        }

        if ($token === '' || $secret === '' || !hash_equals($secret, $token)) {
            http_response_code(403);
            exit('Forbidden');
        }

        // Anti-abuso: 2 ejecuciones por minuto por IP
        if (function_exists('rate_limit_or_fail')) {
            rate_limit_or_fail('email_web_run:' . client_ip(), 2, 60);
        }

        $schedLimit = (int)($settings->get('email_marketing_scheduler_limit', '') ?: env('EMAIL_MARKETING_SCHEDULER_LIMIT', 200));
        $broadcastLimit = (int)($settings->get('email_marketing_broadcast_limit', '') ?: $schedLimit);
        $workLimit  = (int)($settings->get('email_marketing_worker_limit', '') ?: env('EMAIL_MARKETING_WORKER_LIMIT', 25));

        $schedLimit = max(10, min(2000, $schedLimit));
        $broadcastLimit = max(10, min(2000, $broadcastLimit));
        $workLimit  = max(1, min(500, $workLimit));

        $started = microtime(true);

        $broadcastStats = ['enqueued'=>0,'finalized'=>0];
        $schedStats = ['welcome_enqueued'=>0,'cart_enqueued'=>0,'post_purchase_enqueued'=>0,'skipped_unsubscribed'=>0];
        $workStats = ['processed'=>0,'sent'=>0,'failed'=>0];

        try {
            $broadcastStats = (new EmailBroadcastSchedulerService($db))->run($broadcastLimit);
        } catch (\Throwable $e) {
            // no-op
        }

        try {
            $schedStats = (new EmailAutomationSchedulerService($db))->run($schedLimit);
        } catch (\Throwable $e) {
            // no-op
        }

        try {
            $workStats = (new EmailQueueWorkerService($db))->processDue($workLimit);
        } catch (\Throwable $e) {
            // no-op
        }

        $ms = (int)round((microtime(true) - $started) * 1000);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo json_encode([
            'ok' => true,
            'limits' => [
                'broadcast' => $broadcastLimit,
                'scheduler' => $schedLimit,
                'worker' => $workLimit,
            ],
            'campaigns' => $broadcastStats,
            'scheduler' => $schedStats,
            'worker' => $workStats,
            'ms' => $ms,
            'ts' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
