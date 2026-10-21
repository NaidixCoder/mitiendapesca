<?php
namespace App\Services\Accounting;

use App\Repositories\Accounting\AccountingClosureRepository;
use App\Repositories\Billing\PurchaseInvoiceRepository;
use App\Services\Billing\ReporteMensualIvaService;
use PDO;
use RuntimeException;

final class MonthlyClosureService
{
    private AccountingClosureRepository $closures;
    private PurchaseInvoiceRepository $comprasRepo;

    public function __construct(private PDO $db)
    {
        $this->closures = new AccountingClosureRepository($db);
        $this->comprasRepo = new PurchaseInvoiceRepository($db);
    }

    public function getForPeriod(string $period): ?array
    {
        return $this->closures->findByPeriod($period);
    }

    /**
     * Cierra (crea snapshot) para un período YYYY-MM.
     * No recalcula períodos cerrados.
     */
    public function closePeriod(string $period, int $userId): array
    {
        $period = trim($period);
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new RuntimeException('Período inválido. Usá formato YYYY-MM.');
        }

        if ($this->closures->findByPeriod($period)) {
            throw new RuntimeException('Este período ya está cerrado.');
        }

        [$fromDate, $toDate] = $this->monthBounds($period);

        // Ventas (reutiliza cálculo actual del reporte mensual IVA)
        $ventasSvc = new ReporteMensualIvaService($this->db);
        $ventasData = $ventasSvc->build([
            'from_month' => $period,
            'to_month' => $period,
            'arca_env' => '', // snapshot global (no por entorno)
        ]);

        $ventasRow = null;
        foreach (($ventasData['rows'] ?? []) as $r) {
            if ((string)($r['ym'] ?? '') === $period) { $ventasRow = $r; break; }
        }
        if (!$ventasRow) $ventasRow = ['ym' => $period];

        // Compras (resumen mensual, excluye void por defecto)
        $comprasRows = $this->comprasRepo->monthlySummary($fromDate, $toDate, []);
        $comprasRow = null;
        foreach ($comprasRows as $r) {
            if ((string)($r['ym'] ?? '') === $period) { $comprasRow = $r; break; }
        }
        if (!$comprasRow) $comprasRow = ['ym' => $period];

        $ivaVentas = (int)($ventasRow['iva'] ?? 0);
        $ivaCompras = (int)($comprasRow['iva'] ?? 0);
        $saldo = $ivaVentas - $ivaCompras;

        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $id = $this->closures->create([
            'period' => $period,
            'iva_ventas' => $ivaVentas,
            'iva_compras' => $ivaCompras,
            'saldo_iva' => $saldo,
            'ventas_json' => json_encode($ventasRow, JSON_UNESCAPED_UNICODE),
            'compras_json' => json_encode($comprasRow, JSON_UNESCAPED_UNICODE),
            'closed_by_user_id' => $userId,
            'closed_at' => $now,
            'created_at' => $now,
        ]);

        $row = $this->closures->findByPeriod($period);
        if (!$row) {
            throw new RuntimeException('No se pudo crear el cierre.');
        }
        $row['id'] = $id;
        return $row;
    }

    /**
     * @return array{0:string,1:string} fromDate,toDate (Y-m-d)
     */
    private function monthBounds(string $period): array
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $period . '-01');
        if (!$d) throw new RuntimeException('Período inválido.');

        $from = $d->format('Y-m-01');
        $to = $d->modify('last day of this month')->format('Y-m-d');
        return [$from, $to];
    }
}
