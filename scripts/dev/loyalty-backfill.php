<?php
/**
 * Backfill histórico de loyalty_events por pedido pagado.
 *
 * Uso (CLI):
 *   php tools/loyalty-backfill.php --dry-run
 *   php tools/loyalty-backfill.php
 *   php tools/loyalty-backfill.php --user=1
 *   php tools/loyalty-backfill.php --limit=200
 */

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  echo "Este script solo puede ejecutarse por CLI.\n";
  exit(1);
}

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/bootstrap.php';

use App\Services\Loyalty\LoyaltyBackfillService;
use App\Repositories\Account\LoyaltyEventsRepository;

$argv = $_SERVER['argv'] ?? [];
$dryRun = in_array('--dry-run', $argv, true);

function arg_value(array $argv, string $key): ?string {
  foreach ($argv as $a) {
    if (strpos($a, $key.'=') === 0) return substr($a, strlen($key) + 1);
  }
  return null;
}

$userArg = arg_value($argv, '--user');
$limitArg = arg_value($argv, '--limit');
$limit = (int)($limitArg ?? 500);
$limit = max(1, min(5000, $limit));

$pdo = db();
$eventsRepo = new LoyaltyEventsRepository($pdo);
if (!$eventsRepo->tableExists()) {
  echo "ERROR: no existe la tabla loyalty_events. Aplicá la migración 20260118_01_loyalty_events.sql\n";
  exit(1);
}

$svc = new LoyaltyBackfillService($pdo);

// ---- Determinar usuarios a procesar ----
$userIds = [];
if ($userArg !== null && (int)$userArg > 0) {
  $userIds = [(int)$userArg];
} else {
  // Seguridad: por defecto backfilleamos SOLO usuarios que tengan snapshot.
  $st = $pdo->prepare("SELECT DISTINCT user_id FROM loyalty_events WHERE dedupe_key LIKE 'snapshot:user:%' ORDER BY user_id ASC LIMIT $limit");
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($rows as $r) {
    $uid = (int)($r['user_id'] ?? 0);
    if ($uid > 0) $userIds[] = $uid;
  }
}

echo "\n== Loyalty backfill por pedidos pagados ==\n";
echo "Modo: " . ($dryRun ? 'DRY RUN (no escribe / no borra snapshot)' : 'EJECUCIÓN (escribe + borra snapshot si corresponde)') . "\n";
echo "Usuarios a procesar: " . count($userIds) . "\n\n";

$totCreated = 0;
$totSnapDeleted = 0;
$totSkipped = 0;

foreach ($userIds as $uid) {
  $r = $svc->backfillUser($uid, $dryRun);
  if (!empty($r['skipped'])) {
    $totSkipped++;
    continue;
  }
  $c = (int)($r['created'] ?? 0);
  $d = (int)($r['deleted_snapshot'] ?? 0);
  $totCreated += $c;
  $totSnapDeleted += $d;
  echo "- user_id={$uid}: eventos_creados={$c}, snapshot_eliminado={$d}\n";
}

echo "\nResumen:\n";
echo "- eventos_creados: {$totCreated}\n";
echo "- snapshots_eliminados: {$totSnapDeleted}\n";
echo "- usuarios_salteados: {$totSkipped}\n\n";

if ($dryRun) {
  echo "DRY RUN: no se escribio nada. Para ejecutar de verdad: php tools/loyalty-backfill.php\n\n";
  exit(0);
}

echo "OK.\n\n";
exit(0);
