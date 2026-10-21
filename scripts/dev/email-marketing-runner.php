<?php

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/config/bootstrap.php';

use App\Services\Marketing\EmailAutomationSchedulerService;
use App\Services\Marketing\EmailBroadcastSchedulerService;
use App\Services\Marketing\EmailQueueWorkerService;
use App\Repositories\Settings\AppSettingsRepository;

$db = db();

// Permite override desde Ajustes (DB) para no depender de .env en producción.
$settings = new AppSettingsRepository($db);

$schedLimit = (int)($settings->get('email_marketing_scheduler_limit', '') ?: env('EMAIL_MARKETING_SCHEDULER_LIMIT', 200));
$broadcastLimit = (int)($settings->get('email_marketing_broadcast_limit', '') ?: $schedLimit);
$workLimit  = (int)($settings->get('email_marketing_worker_limit', '') ?: env('EMAIL_MARKETING_WORKER_LIMIT', 25));

$schedLimit = max(10, min(2000, $schedLimit));
$broadcastLimit = max(10, min(2000, $broadcastLimit));
$workLimit  = max(1, min(500, $workLimit));

$started = microtime(true);

$broadcast = new EmailBroadcastSchedulerService($db);
$sched = new EmailAutomationSchedulerService($db);
$w = new EmailQueueWorkerService($db);

$broadcastStats = $broadcast->run($broadcastLimit);
$schedStats = $sched->run($schedLimit);
$workStats = $w->processDue($workLimit);

$ms = (int)round((microtime(true) - $started) * 1000);

echo "[Email Marketing Runner] OK\n";
echo "- Campañas: encoladas={$broadcastStats['enqueued']} | finalizadas={$broadcastStats['finalized']} (limite={$broadcastLimit})\n";
echo "- Encolado: welcome={$schedStats['welcome_enqueued']} | carrito={$schedStats['cart_enqueued']} | post_compra={$schedStats['post_purchase_enqueued']} | bajas_omitidas={$schedStats['skipped_unsubscribed']}\n";
echo "- Procesado: {$workStats['processed']} | enviados={$workStats['sent']} | fallados={$workStats['failed']}\n";
echo "- Tiempo: {$ms} ms\n";
