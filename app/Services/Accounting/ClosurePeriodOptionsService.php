<?php
namespace App\Services\Accounting;

use App\Repositories\Accounting\AccountingClosureRepository;
use App\Repositories\Billing\ReporteMensualIvaRepository;
use App\Repositories\Billing\PurchaseInvoiceRepository;
use DateInterval;
use DateTimeImmutable;
use PDO;

/**
 * Arma opciones de período para la UI de cierres.
 * - Evita formato libre y guía al usuario.
 * - Incluye estado: cerrado / con movimientos.
 * - No duplica lógica contable (solo detecta si hay registros en el mes).
 */
final class ClosurePeriodOptionsService
{
    private AccountingClosureRepository $closures;
    private ReporteMensualIvaRepository $ventasRepo;
    private PurchaseInvoiceRepository $comprasRepo;

    public function __construct(private PDO $db)
    {
        $this->closures = new AccountingClosureRepository($db);
        $this->ventasRepo = new ReporteMensualIvaRepository($db);
        $this->comprasRepo = new PurchaseInvoiceRepository($db);
    }

    /**
     * @return array{options:array<int,array{period:string,label:string,has_movements:bool,is_closed:bool}>, default_period:string}
     */
    public function buildOptions(int $monthsBack = 24): array
    {
        // En lugar de generar meses “hacia atrás”, armamos el select con *períodos existentes* en DB
        // (meses que tienen movimientos en ventas o compras) + los meses ya cerrados.
        // $monthsBack se usa como “máximo de períodos a mostrar” (capado a 120).

        $limit = max(6, min(120, $monthsBack));

        $now = new DateTimeImmutable('now');

        // 1) Detectar el rango real (desde el primer movimiento) para no listar meses vacíos.
        $ventasMin = $this->scalarMinDateVentas();
        $comprasMin = $this->scalarMinDateCompras();

        $min = null;
        if ($ventasMin) $min = $ventasMin;
        if ($comprasMin && (!$min || $comprasMin < $min)) $min = $comprasMin;

        // Si no hay movimientos aún, mostramos solo el mes actual.
        if (!$min) {
            $ym = $now->format('Y-m');
            return [
                'options' => [[
                    'period' => $ym,
                    'label' => $this->labelEs($ym),
                    'has_movements' => false,
                    'is_closed' => (bool)$this->closures->findByPeriod($ym),
                ]],
                'default_period' => $ym,
            ];
        }

        $fromDate = $min->format('Y-m-01');
        $toDate = $now->format('Y-m-t');

        // 2) Meses con movimientos (solo presencia, no cálculo contable)
        $ventas = $this->ventasRepo->monthlySummary($fromDate, $toDate, '');
        $compras = $this->comprasRepo->monthlySummary($fromDate, $toDate, []);

        $movements = []; // period => true
        foreach ($ventas as $r) {
            $ym = (string)($r['ym'] ?? '');
            if ($ym !== '') $movements[$ym] = true;
        }
        foreach ($compras as $r) {
            $ym = (string)($r['ym'] ?? '');
            if ($ym !== '') $movements[$ym] = true;
        }

        // 3) Sumar cierres ya existentes aunque no haya movimientos (por seguridad operativa)
        $closedRows = $this->closures->listLatest(120);
        $closedPeriods = [];
        foreach ($closedRows as $r) {
            $p = (string)($r['period'] ?? '');
            if ($p !== '') $closedPeriods[$p] = true;
        }

        $allPeriods = array_keys($movements + $closedPeriods);
        rsort($allPeriods); // más recientes primero

        // Aplicar límite
        if (count($allPeriods) > $limit) {
            $allPeriods = array_slice($allPeriods, 0, $limit);
        }

        $closuresByPeriod = $this->closures->findByPeriods($allPeriods);

        $options = [];
        foreach ($allPeriods as $ym) {
            $isClosed = isset($closuresByPeriod[$ym]);
            $hasMov = isset($movements[$ym]);
            $options[] = [
                'period' => $ym,
                'label' => $this->labelEs($ym),
                'has_movements' => $hasMov,
                'is_closed' => $isClosed,
            ];
        }

        // Default: primer período “abierto” con movimientos. Si no hay, el más reciente.
        $default = $options[0]['period'] ?? $now->format('Y-m');
        foreach ($options as $opt) {
            if (!empty($opt['has_movements']) && empty($opt['is_closed'])) {
                $default = (string)$opt['period'];
                break;
            }
        }

        return ['options' => $options, 'default_period' => $default];
    }

    private function scalarMinDateVentas(): ?DateTimeImmutable
    {
        $st = $this->db->query("SELECT MIN(issued_at) AS min_dt FROM comprobantes_fiscales WHERE status='issued' AND issued_at IS NOT NULL");
        $r = $st ? ($st->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        $v = (string)($r['min_dt'] ?? '');
        if ($v === '') return null;
        try {
            return new DateTimeImmutable($v);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function scalarMinDateCompras(): ?DateTimeImmutable
    {
        $st = $this->db->query("SELECT MIN(issued_at) AS min_dt FROM purchase_invoices WHERE status <> 'void' AND issued_at IS NOT NULL");
        $r = $st ? ($st->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        $v = (string)($r['min_dt'] ?? '');
        if ($v === '') return null;
        try {
            return new DateTimeImmutable($v);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function labelEs(string $ym): string
    {
        // ym = YYYY-MM
        [$y, $m] = explode('-', $ym);
        $m = (int)$m;
        $names = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $mn = $names[$m] ?? $ym;
        return $mn . ' ' . $y . ' (' . $ym . ')';
    }
}
