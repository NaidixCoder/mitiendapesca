<?php
namespace App\Services\Billing;

use App\Repositories\Billing\ReporteMensualIvaRepository;
use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Repositories\Accounting\AccountingClosureRepository;
use PDO;

final class ReporteMensualIvaService
{
    private ReporteMensualIvaRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new ReporteMensualIvaRepository($db);
    }

    /**
     * @param array{from_month:string,to_month:string,arca_env:string} $filters
     * @return array<string,mixed>
     */
    public function build(array $filters): array
    {
        [$fromDate, $toDate] = $this->resolveDates($filters['from_month'], $filters['to_month']);

        // --- Snapshot lock: si el período está cerrado, NO recalculamos ni hacemos backfill de ese mes ---
        $months = $this->monthsBetween($fromDate, $toDate);
        $closureRepo = new AccountingClosureRepository($this->db);
        $closuresByPeriod = $closureRepo->findByPeriods($months);

        // 6.3.B: normaliza importes legacy desde request_json antes de agregar (evita IVA=0 en registros emitidos).
        // ✅ Solo para meses NO cerrados (evita modificar histórico cerrado).
        $cfRepo = new ComprobanteFiscalRepository($this->db);
        foreach ($months as $ym) {
            if (isset($closuresByPeriod[$ym])) continue;
            [$mFrom, $mTo] = $this->monthBounds($ym);
            if ($mFrom < $fromDate) $mFrom = $fromDate;
            if ($mTo > $toDate) $mTo = $toDate;
            $cfRepo->backfillAmountsFromRequestJsonForIssued($mFrom, $mTo, (string)$filters['arca_env']);
        }

        $rows = $this->repo->monthlySummary($fromDate, $toDate, (string)$filters['arca_env']);

        // ✅ Override con snapshot (lock lógico): si existe cierre del mes, usamos ventas_json.
        if (!empty($closuresByPeriod)) {
            $byYm = [];
            foreach ($rows as $r) {
                $ym = (string)($r['ym'] ?? '');
                if ($ym !== '') $byYm[$ym] = $r;
            }
            foreach ($closuresByPeriod as $ym => $c) {
                $ventas = [];
                if (is_string($c['ventas_json'] ?? null)) {
                    $d = json_decode((string)$c['ventas_json'], true);
                    if (is_array($d)) $ventas = $d;
                }
                if ($ventas) {
                    $ventas['ym'] = $ym;
                    $byYm[$ym] = $ventas;
                }
            }
            $rows = array_values($byYm);
            usort($rows, fn($a, $b) => strcmp((string)($a['ym'] ?? ''), (string)($b['ym'] ?? '')));
        }

        $totals = [
            'neto' => 0,
            'iva' => 0,
            'total' => 0,
            'comprobantes_count' => 0,
            'facturas_count' => 0,
            'notas_credito_count' => 0,
        ];

        foreach ($rows as $r) {
            $totals['neto'] += (int)($r['neto'] ?? 0);
            $totals['iva'] += (int)($r['iva'] ?? 0);
            $totals['total'] += (int)($r['total'] ?? 0);
            $totals['comprobantes_count'] += (int)($r['comprobantes_count'] ?? 0);
            $totals['facturas_count'] += (int)($r['facturas_count'] ?? 0);
            $totals['notas_credito_count'] += (int)($r['notas_credito_count'] ?? 0);
        }

        return [
            'filters' => [
                'from_month' => $filters['from_month'],
                'to_month' => $filters['to_month'],
                'arca_env' => $filters['arca_env'],
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
        $now = new \DateTimeImmutable('now');

        $fromMonth = trim($fromMonth);
        $toMonth = trim($toMonth);

        if (!preg_match('/^\d{4}-\d{2}$/', $fromMonth)) {
            $fromMonth = $now->modify('-11 months')->format('Y-m');
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $toMonth)) {
            $toMonth = $now->format('Y-m');
        }

        // Normalize order
        if ($fromMonth > $toMonth) {
            [$fromMonth, $toMonth] = [$toMonth, $fromMonth];
        }

        $fromDate = $fromMonth . '-01';

        $toDt = \DateTimeImmutable::createFromFormat('Y-m-d', $toMonth . '-01');
        if (!$toDt) {
            $toDt = $now;
        }
        $toLast = $toDt->modify('last day of this month')->format('Y-m-d');

        return [$fromDate, $toLast];
    }
}
