<?php
/**
 * Limpieza de auth_login_events según retención.
 *
 * Uso (CLI):
 *   php tools/cleanup-auth-login-events.php --dry-run
 *   php tools/cleanup-auth-login-events.php
 *   php tools/cleanup-auth-login-events.php --success-days=365 --failed-days=180 --logout-days=90
 *
 * Recomendado correr por cron (mensual o semanal).
 */

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  echo "Este script solo puede ejecutarse por CLI.\n";
  exit(1);
}

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/bootstrap.php';

use App\Services\Admin\AuthLoginEventsMaintenanceService;

// ----------------- Args -----------------
$argv = $_SERVER['argv'] ?? [];
$dryRun = in_array('--dry-run', $argv, true);

function arg_value(array $argv, string $key): ?string {
  foreach ($argv as $a) {
    if (strpos($a, $key.'=') === 0) return substr($a, strlen($key) + 1);
  }
  return null;
}

$baseRetention = AuthLoginEventsMaintenanceService::retentionFromEnv();
$retention = [
  'login_success' => (int)(arg_value($argv, '--success-days') ?? $baseRetention['login_success']),
  'login_failed'  => (int)(arg_value($argv, '--failed-days')  ?? $baseRetention['login_failed']),
  'logout'        => (int)(arg_value($argv, '--logout-days')  ?? $baseRetention['logout']),
];

$pdo = db();
$result = AuthLoginEventsMaintenanceService::cleanup($pdo, $retention, $dryRun);

echo "\n== Limpieza auth_login_events ==\n";
echo "Modo: " . ($dryRun ? 'DRY RUN (no borra)' : 'EJECUCIÓN (borra)') . "\n";
echo "Retención: success={$retention['login_success']}d (<= {$result['cutoffs']['login_success']}), failed={$retention['login_failed']}d (<= {$result['cutoffs']['login_failed']}), logout={$retention['logout']}d (<= {$result['cutoffs']['logout']})\n\n";

foreach (['login_success','login_failed','logout'] as $evt) {
  $c = (int)($result['to_delete'][$evt] ?? 0);
  $cut = (string)($result['cutoffs'][$evt] ?? '');
  echo "- {$evt}: {$c} registro(s) a limpiar (antes de {$cut})\n";
}

echo "\nTotal a limpiar: " . (int)$result['total_to_delete'] . "\n\n";

if ($dryRun) {
  echo "DRY RUN: no se borró nada.\n\n";
  exit(0);
}

echo "OK: borrados " . (int)$result['total_deleted'] . " registro(s).\n\n";
exit(0);
