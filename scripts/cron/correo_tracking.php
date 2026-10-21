<?php
// FIX518: Cron CLI para tracking Correo Argentino.
// Uso:
//   php scripts/correo_tracking_cron.php --limit=80 --minm=10
//
// Recomendación Hostinger (cron):
//   /usr/bin/php -d detect_unicode=0 /home/USER/public_html/../scripts/correo_tracking_cron.php --limit=80 --minm=10

require_once __DIR__ . '/../../bootstrap.php';

use App\Services\Shipping\CorreoArgentino\CorreoTrackingRunnerService;

function arg(string $name, $default = null) {
    global $argv;
    foreach ($argv as $a) {
        if (strpos($a, '--'.$name.'=') === 0) return substr($a, strlen('--'.$name.'='));
    }
    return $default;
}

$limit = (int)arg('limit', 50);
$minm  = (int)arg('minm', 10);

// Lock (storage/locks)
$lockFile = BASE_PATH . '/storage/locks/correo_tracking.lock';
if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0775, true);
$fp = @fopen($lockFile, 'c+');
if (!$fp) {
    fwrite(STDERR, "Lock error\n");
    exit(2);
}
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Busy\n");
    exit(3);
}

try {
    $svc = new CorreoTrackingRunnerService(db());
    $summary = $svc->run($limit, $minm);

    echo json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL;

    $exit = ((int)($summary['tracked_fail'] ?? 0) > 0) ? 1 : 0;
    exit($exit);
} finally {
    @flock($fp, LOCK_UN);
    @fclose($fp);
}
