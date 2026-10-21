<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo "Not Found";
    exit;
}

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/bootstrap.php';

$maxSeconds = 20;
$limitEnqueue = 800;
$limitSend = 50;

foreach ($argv as $arg) {
    if (strpos($arg, '--max-seconds=') === 0) {
        $maxSeconds = (int)substr($arg, strlen('--max-seconds='));
    } elseif (strpos($arg, '--enqueue=') === 0) {
        $limitEnqueue = (int)substr($arg, strlen('--enqueue='));
    } elseif (strpos($arg, '--send=') === 0) {
        $limitSend = (int)substr($arg, strlen('--send='));
    }
}

$maxSeconds = max(1, min(110, $maxSeconds));
$limitEnqueue = max(10, min(5000, $limitEnqueue));
$limitSend = max(1, min(200, $limitSend));

$db = db();

$started = microtime(true);

$total = [
    'scheduler_seen' => 0,
    'scheduler_enqueued' => 0,
    'scheduler_finalized' => 0,
    'worker_processed' => 0,
    'worker_sent' => 0,
    'worker_failed' => 0,
    'loops' => 0,
];

while (true) {
    $total['loops']++;

    // 1) Enqueue due campaigns
    $sched = (new \App\Services\Marketing\EmailBroadcastSchedulerService($db))->run($limitEnqueue);
    $total['scheduler_seen'] += (int)($sched['campaigns_seen'] ?? 0);
    $total['scheduler_enqueued'] += (int)($sched['enqueued'] ?? 0);
    $total['scheduler_finalized'] += (int)($sched['finalized'] ?? 0);

    // 2) Send due emails
    $work = (new \App\Services\Marketing\EmailQueueWorkerService($db))->processDue($limitSend);
    $total['worker_processed'] += (int)($work['processed'] ?? 0);
    $total['worker_sent'] += (int)($work['sent'] ?? 0);
    $total['worker_failed'] += (int)($work['failed'] ?? 0);

    // 3) Finalize (run scheduler again to mark campaigns as sent when queue drained)
    $sched2 = (new \App\Services\Marketing\EmailBroadcastSchedulerService($db))->run(10);
    $total['scheduler_finalized'] += (int)($sched2['finalized'] ?? 0);

    // Stop when nothing happened or time budget exceeded
    $elapsed = microtime(true) - $started;
    $nothing = ((int)($sched['enqueued'] ?? 0) === 0) && ((int)($work['processed'] ?? 0) === 0) && ((int)($sched2['finalized'] ?? 0) === 0);
    if ($nothing) {
        break;
    }
    if ($elapsed >= $maxSeconds) {
        break;
    }
}

$elapsed = microtime(true) - $started;

// Output JSON for easy cron logging
echo json_encode([
    'ok' => true,
    'elapsed_s' => round($elapsed, 3),
    'max_seconds' => $maxSeconds,
    'stats' => $total,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
