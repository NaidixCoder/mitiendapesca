<?php
/**
 * FIX553: CLI mínimo para tareas operativas.
 *
 * Uso:
 *   php cli.php imports:purge [days]
 *   php cli.php orders:expire-transfers [limit]
 *
 * Ejemplo:
 *   php cli.php imports:purge 183
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/bootstrap.php';

// No emitir headers/sesión en CLI

$argv = $_SERVER['argv'] ?? [];
$cmd  = $argv[1] ?? '';

if ($cmd === '' || in_array($cmd, ['-h','--help','help'], true)) {
    echo "Mi Tienda Pesca - CLI\n\n";
    echo "Comandos disponibles:\n";
    echo "  orders:expire-transfers [limit]   Vence pedidos por transferencia pendientes fuera de la ventana de reserva.\n";
    echo "  imports:purge [days]   Purga importaciones + archivos más viejos que N días (default 183).\n";
    exit(0);
}

switch ($cmd) {
    case 'imports:purge':
        $days = isset($argv[2]) ? (int)$argv[2] : 183;
        $db = db();
        $repo = new \App\Repositories\ImportJobRepository($db);
        $svc  = new \App\Services\Import\ImportPurgeService($repo);

        $res = $svc->purgeOlderThanDays($days, 500);

        // Log simple en storage/logs
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'import_purge.log';
        $line = '[' . date('Y-m-d H:i:s') . ' local] purge imports older_than_days=' . (int)$days
              . ' cutoff_utc=' . ($res['cutoff_utc'] ?? '')
              . ' scanned=' . (int)($res['scanned'] ?? 0)
              . ' db_deleted=' . (int)($res['db_deleted'] ?? 0)
              . ' file_deleted=' . (int)($res['file_deleted'] ?? 0)
              . ' warnings=' . (int)count($res['warnings'] ?? [])
              . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND);

        echo json_encode($res, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
        exit(0);

    case 'orders:expire-transfers':
        $limit = isset($argv[2]) ? (int)$argv[2] : 200;
        $db = db();
        $svc = new \App\Services\Orders\ExpirePendingTransferOrdersService($db);
        $res = $svc->run($limit);

        $logDir = storage_path('logs');
        if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'orders_expire_transfers.log';
        $line = '[' . date('Y-m-d H:i:s') . ' local] expire_transfers'
              . ' reservation_hours=' . (int)($res['reservation_hours'] ?? 0)
              . ' cutoff=' . (string)($res['cutoff'] ?? '')
              . ' scanned=' . (int)($res['scanned'] ?? 0)
              . ' expired=' . (int)($res['expired'] ?? 0)
              . ' errors=' . (int)($res['errors'] ?? 0)
              . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND);

        echo json_encode($res, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
        exit(0);

    default:
        fwrite(STDERR, "Comando desconocido: {$cmd}\n");
        exit(1);
}
