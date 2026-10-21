<?php
namespace App\Services\Billing;

use App\Repositories\Billing\ReporteMensualIvaRepository;
use App\Repositories\Billing\PurchaseInvoiceRepository;
use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Repositories\Accounting\AccountingClosureRepository;
use PDO;

/**
 * Reporte mensual de "Saldo IVA" (Débito - Crédito)
 * - Débito: IVA de Ventas (comprobantes_fiscales emitidos)
 * - Crédito: IVA de Compras (purchase_invoices cargadas)
 *
 * Nota: Ventas puede filtrarse por entorno (homologacion/produccion). Compras no tiene entorno.
 */
final class ReporteSaldoIvaService
{
    private ReporteMensualIvaRepository $ventasRepo;
    private PurchaseInvoiceRepository $comprasRepo;

    public function __construct(private PDO $db)
    {
        $this->ventasRepo = new ReporteMensualIvaRepository($db);
        $this->comprasRepo = new PurchaseInvoiceRepository($db);
    }

    /**
     * @param array{from_month:string,to_month:string,arca_env:string} $filters
     * @return array<string,mixed>
     */
    public function build(array $filters): array
    {
        [$fromDate, $toDate] = $this->resolveDates($filters['from_month'] ?? '', $filters['to_month'] ?? '');

        // --- Snapshot lock: si el período está cerrado, NO recalculamos ni hacemos backfill de ese mes ---
        $months = $this->monthsBetween($fromDate, $toDate);
        $closureRepo = new AccountingClosureRepository($this->db);
        $closuresByPeriod = $closureRepo->findByPeriods($months);

        // Normaliza importes legacy de ventas antes de agregar.
        // ✅ Solo para meses NO cerrados (evita modificar histórico cerrado).
        $cfRepo = new ComprobanteFiscalRepository($this->db);
        foreach ($months as $ym) {
            if (isset($closuresByPeriod[$ym])) continue;
            [$mFrom, $mTo] = $this->monthBounds($ym);
            if ($mFrom < $fromDate) $mFrom = $fromDate;
            if ($mTo > $toDate) $mTo = $toDate;
            $cfRepo->backfillAmountsFromRequestJsonForIssued($mFrom, $mTo, (string)($filters['arca_env'] ?? ''));
        }

        $ventas = $this->ventasRepo->monthlySummary($fromDate, $toDate, (string)($filters['arca_env'] ?? ''));
        $compras = $this->comprasRepo->monthlySummary($fromDate, $toDate, []);

        $ventasByYm = [];
        foreach ($ventas as $r) {
            $ym = (string)($r['ym'] ?? '');
            if ($ym !== '') $ventasByYm[$ym] = $r;
        }

        $comprasByYm = [];
        foreach ($compras as $r) {
            $ym = (string)($r['ym'] ?? '');
            if ($ym !== '') $comprasByYm[$ym] = $r;
        }

        // Unión de meses (incluye meses cerrados aunque no haya data live)
        $months = array_unique(array_merge($months, array_keys($ventasByYm), array_keys($comprasByYm)));
        sort($months);

        $rows = [];
        foreach ($months as $ym) {
            if (isset($closuresByPeriod[$ym])) {
                $c = $closuresByPeriod[$ym];
                $ventasSnap = [];
                $comprasSnap = [];
                if (is_string($c['ventas_json'] ?? null)) {
                    $d = json_decode((string)$c['ventas_json'], true);
                    if (is_array($d)) $ventasSnap = $d;
                }
                if (is_string($c['compras_json'] ?? null)) {
                    $d = json_decode((string)$c['compras_json'], true);
                    if (is_array($d)) $comprasSnap = $d;
                }

                $ivaVentas = (int)($c['iva_ventas'] ?? ($ventasSnap['iva'] ?? 0));
                $ivaCompras = (int)($c['iva_compras'] ?? ($comprasSnap['iva'] ?? 0));
                $saldo = (int)($c['saldo_iva'] ?? ($ivaVentas - $ivaCompras));

                $rows[] = [
                    'ym' => $ym,
                    'iva_ventas' => $ivaVentas,
                    'iva_compras' => $ivaCompras,
                    'saldo_iva' => $saldo,
                    'ventas_total' => (int)($ventasSnap['total'] ?? 0),
                    'compras_total' => (int)($comprasSnap['total'] ?? 0),
                    'ventas_neto' => (int)($ventasSnap['neto'] ?? 0),
                    'compras_neto' => (int)($comprasSnap['neto'] ?? 0),
                    'ventas_comprobantes_count' => (int)($ventasSnap['comprobantes_count'] ?? 0),
                    'compras_comprobantes_count' => (int)($comprasSnap['comprobantes_count'] ?? 0),
                    'closed' => true,
                ];
                continue;
            }

            $v = $ventasByYm[$ym] ?? ['iva' => 0, 'neto' => 0, 'total' => 0, 'comprobantes_count' => 0];
            $c = $comprasByYm[$ym] ?? ['iva' => 0, 'neto' => 0, 'total' => 0, 'comprobantes_count' => 0];

            $ivaVentas = (int)($v['iva'] ?? 0);
            $ivaCompras = (int)($c['iva'] ?? 0);

            $rows[] = [
                'ym' => $ym,
                'iva_ventas' => $ivaVentas,
                'iva_compras' => $ivaCompras,
                'saldo_iva' => $ivaVentas - $ivaCompras,
                'ventas_total' => (int)($v['total'] ?? 0),
                'compras_total' => (int)($c['total'] ?? 0),
                'ventas_neto' => (int)($v['neto'] ?? 0),
                'compras_neto' => (int)($c['neto'] ?? 0),
                'ventas_comprobantes_count' => (int)($v['comprobantes_count'] ?? 0),
                'compras_comprobantes_count' => (int)($c['comprobantes_count'] ?? 0),
                'closed' => false,
            ];
        }

        $totals = [
            'iva_ventas' => 0,
            'iva_compras' => 0,
            'saldo_iva' => 0,
            'ventas_total' => 0,
            'compras_total' => 0,
        ];

        foreach ($rows as $r) {
            $totals['iva_ventas'] += (int)($r['iva_ventas'] ?? 0);
            $totals['iva_compras'] += (int)($r['iva_compras'] ?? 0);
            $totals['saldo_iva'] += (int)($r['saldo_iva'] ?? 0);
            $totals['ventas_total'] += (int)($r['ventas_total'] ?? 0);
            $totals['compras_total'] += (int)($r['compras_total'] ?? 0);
        }

        return [
            'filters' => [
                'from_month' => (string)($filters['from_month'] ?? ''),
                'to_month' => (string)($filters['to_month'] ?? ''),
                'arca_env' => (string)($filters['arca_env'] ?? ''),
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
    /**
     * @return array<int,string> lista de YYYY-MM
     */
    private function monthsBetween(string $fromDate, string $toDate): array
    {
        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $fromDate);
        $to = \DateTimeImmutable::createFromFormat('Y-m-d', $toDate);
        if (!$from || !$to) return [];
        $from = $from->modify('first day of this month');
        $to = $to->modify('first day of this month');
        $months = [];
        for ($d = $from; $d <= $to; $d = $d->modify('+1 month')) {
            $months[] = $d->format('Y-m');
        }
        return $months;
    }

    /**
     * @return array{0:string,1:string} fromDate,toDate (Y-m-d)
     */
    private function monthBounds(string $ym): array
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $ym . '-01');
        if (!$d) return [$ym . '-01', $ym . '-01'];
        $from = $d->format('Y-m-01');
        $to = $d->modify('last day of this month')->format('Y-m-d');
        return [$from, $to];
    }

    /**
     * @return array{0:string,1:string} fromDate,toDate (Y-m-d)
     */
    private function resolveDates(string $fromMonth, string $toMonth): array
    {
        $fromMonth = trim($fromMonth);
        $toMonth = trim($toMonth);

        if (!preg_match('/^\d{4}-\d{2}$/', $fromMonth)) {
            $fromMonth = date('Y-m');
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $toMonth)) {
            $toMonth = date('Y-m');
        }

        $fromDate = $fromMonth . '-01';
        $toDate = date('Y-m-t', strtotime($toMonth . '-01'));

        return [$fromDate, $toDate];
    }
}
