<?php
namespace App\Services\Admin;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

/**
 * Mantenimiento de auth_login_events (retención/limpieza).
 *
 * Seguridad-first:
 * - No registra PII en claro (la tabla ya usa hashes).
 * - Best-effort en capa UI (pero este servicio puede lanzar excepciones si el caller quiere).
 */
class AuthLoginEventsMaintenanceService
{
    /** Retención por defecto (días). */
    public static function retentionFromEnv(): array
    {
        // Defaults seguros
        $successDays = (int) (function_exists('env') ? env('AUTH_LOGIN_RETENTION_SUCCESS_DAYS', '365') : '365');
        $failedDays  = (int) (function_exists('env') ? env('AUTH_LOGIN_RETENTION_FAILED_DAYS', '180') : '180');
        $logoutDays  = (int) (function_exists('env') ? env('AUTH_LOGIN_RETENTION_LOGOUT_DAYS', '90')  : '90');

        // Mínimos razonables
        $successDays = max(7, $successDays);
        $failedDays  = max(7, $failedDays);
        $logoutDays  = max(7, $logoutDays);

        return [
            'login_success' => $successDays,
            'login_failed'  => $failedDays,
            'logout'        => $logoutDays,
        ];
    }

    /**
     * Ejecuta (o simula) limpieza.
     *
     * @return array{cutoffs:array<string,string>, to_delete:array<string,int>, deleted:array<string,int>, total_to_delete:int, total_deleted:int}
     */
    public static function cleanup(PDO $pdo, array $retentionDays, bool $dryRun = true): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $plan = [];
        foreach (['login_success', 'login_failed', 'logout'] as $event) {
            $days = (int)($retentionDays[$event] ?? 0);
            $days = max(7, $days);
            $cut = $now->modify('-' . $days . ' days')->format('Y-m-d H:i:s');
            $plan[] = ['event' => $event, 'cutoff' => $cut];
        }

        $cutoffs = [];
        $toDelete = [];
        $deleted = ['login_success' => 0, 'login_failed' => 0, 'logout' => 0];

        $countStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM auth_login_events WHERE event = :event AND created_at < :cutoff');
        $deleteStmt = $pdo->prepare('DELETE FROM auth_login_events WHERE event = :event AND created_at < :cutoff');

        $totalToDelete = 0;
        foreach ($plan as $p) {
            $cutoffs[$p['event']] = $p['cutoff'];
            $countStmt->execute([':event' => $p['event'], ':cutoff' => $p['cutoff']]);
            $c = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            $toDelete[$p['event']] = $c;
            $totalToDelete += $c;
        }

        $totalDeleted = 0;
        if (!$dryRun) {
            foreach ($plan as $p) {
                $deleteStmt->execute([':event' => $p['event'], ':cutoff' => $p['cutoff']]);
                $n = (int)$deleteStmt->rowCount();
                $deleted[$p['event']] = $n;
                $totalDeleted += $n;
            }
        }

        return [
            'cutoffs' => $cutoffs,
            'to_delete' => $toDelete,
            'deleted' => $deleted,
            'total_to_delete' => $totalToDelete,
            'total_deleted' => $totalDeleted,
        ];
    }
}
