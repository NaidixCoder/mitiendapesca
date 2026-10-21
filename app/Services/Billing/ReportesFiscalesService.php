<?php
namespace App\Services\Billing;

use App\Repositories\Billing\ReportesFiscalesRepository;
use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class ReportesFiscalesService
{
    private ReportesFiscalesRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new ReportesFiscalesRepository($db);
    }



private function decorateDeviationRow(array $r): array
{
    $orderTotal = (int)($r['order_total'] ?? 0);
    $invCount = (int)($r['invoices_count'] ?? 0);
    $crCount = (int)($r['credit_count'] ?? 0);
    $invTotal = (int)($r['invoices_total'] ?? 0);
    $crTotal = (int)($r['credit_total'] ?? 0);
    $net = (int)($r['net_issued'] ?? ($invTotal - $crTotal));

    $envMin = (string)($r['env_min'] ?? '');
    $envMax = (string)($r['env_max'] ?? '');
    $mixedEnv = ($envMin !== '' && $envMax !== '' && $envMin !== $envMax);
    $hasDupes = ($invCount > 1 || $crCount > 1);

    $annulled = ($invTotal > 0 && $invTotal === $crTotal && $net === 0);
    $matchesOrder = ($net === $orderTotal);

    $label = 'Revisar';
    $level = 'warn'; // ok | info | warn | danger

    if ($hasDupes) {
        $label = 'Inconsistente (duplicados)';
        $level = 'danger';
    } elseif ($mixedEnv) {
        $label = 'Revisar (entorno mixto)';
        $level = 'danger';
    } elseif ($annulled) {
        $label = 'Anulado fiscalmente';
        $level = 'info';
    } elseif ($matchesOrder) {
        $label = 'Facturado completo';
        $level = 'ok';
    } else {
        $label = 'Revisar (monto distinto)';
        $level = 'warn';
    }

    $r['net_issued'] = $net;
    $r['resultado_label'] = $label;
    $r['resultado_level'] = $level;

    // Para debug/export: monto de ajuste sin signo confuso (pedido - neto)
    $r['ajuste_pedido_menos_neto'] = $orderTotal - $net;

    return $r;
}

    /**
     * @return array{filters:array<string,string>, totals:array<string,mixed>, series:array<int,array<string,mixed>>, conciliation:array<string,array<int,array<string,mixed>>>}
     */
    public function build(array $filters): array
    {
        $from = (string)($filters['from'] ?? '');
        $to   = (string)($filters['to'] ?? '');
        $group = (string)($filters['group'] ?? 'day');
        $env = (string)($filters['arca_env'] ?? '');

        // Defaults seguros
        $today = date('Y-m-d');
        if (!$from) $from = date('Y-m-01');
        if (!$to) $to = $today;

        $group = ($group === 'month') ? 'month' : 'day';
        if (!in_array($env, ['', 'homologacion', 'produccion'], true)) $env = '';

        $totals = $this->repo->totalsIssued($from, $to, $env);
        $series = $this->repo->seriesIssued($from, $to, $group, $env);

        // M3: Conciliación configurable por Ajustes
        $settings = new AppSettingsRepository($this->db);
        $csvToArr = static function(string $csv): array {
            $csv = strtolower(trim($csv));
            if ($csv === '') return [];
            $parts = array_values(array_filter(array_map('trim', explode(',', $csv)), fn($x) => $x !== ''));
            return array_values(array_unique($parts));
        };
        $facturable = $csvToArr((string)($settings->get('arca_facturable_statuses', 'paid,processing,packed,shipped,delivered') ?? ''));
        $noFacturable = $csvToArr((string)($settings->get('arca_non_facturable_statuses', 'pending_payment,payment_failed,payment_expired,cancelled,chargeback,refunded') ?? ''));
        // Evita intersección: si aparece en ambos, prevalece facturable
        $noFacturable = array_values(array_diff($noFacturable, $facturable));

        $missing = $this->repo->ordersMissingInvoice($from, $to, $env, $facturable);
        $notIssued = $this->repo->comprobantesNotIssued($from, $to, $env);
        $deviations = $this->repo->conciliationDeviations($from, $to, $env);
        $deviations = array_map(fn($r) => $this->decorateDeviationRow((array)$r), $deviations);
        $dupesExact = $this->repo->duplicatesExactIssued($from, $to, $env);
        $nonBillable = $this->repo->nonBillableOrdersWithInvoiceIssued($from, $to, $env, $noFacturable);

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'group' => $group,
                'arca_env' => $env,
                'tab' => (string)($filters['tab'] ?? 'resumen'),
            ],
            'totals' => $totals,
            'series' => $series,
            'conciliation' => [
                'orders_missing_invoice' => $missing,
                'comprobantes_not_issued' => $notIssued,
                'deviations' => $deviations,
                'duplicates_exact' => $dupesExact,
                'non_billable_with_invoice' => $nonBillable,
            ],
        ];
    }


/**
 * @return array<int,array<string,mixed>>
 */
public function issuedDetailed(string $from, string $to, string $env = '', string $kind = ''): array
{
    return $this->repo->issuedComprobantesDetailed($from, $to, $env, $kind);
}

}
