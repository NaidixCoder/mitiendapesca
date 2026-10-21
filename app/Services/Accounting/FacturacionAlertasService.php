<?php
namespace App\Services\Accounting;

use App\Repositories\Accounting\FacturacionAlertasRepository;
use App\Services\Billing\PurchaseInvoiceAttachmentService;
use PDO;

final class FacturacionAlertasService
{
    private FacturacionAlertasRepository $repo;
    private PurchaseInvoiceAttachmentService $attachments;

    public function __construct(private PDO $db)
    {
        $this->repo = new FacturacionAlertasRepository($db);
        $this->attachments = new PurchaseInvoiceAttachmentService();
    }

    /**
     * @return array{loaded_not_validated:array, purchases_missing_attachments:array, unclosed_periods:string[]}
     */
    public function build(): array
    {
        $loaded = $this->repo->listComprasByStatus('loaded', 200);
        $validated = $this->repo->listComprasByStatus('validated', 200);

        // Compras sin adjuntos: aplicamos sobre loaded + validated (no void). Para performance, limitamos el set.
        $missing = [];
        foreach (array_merge($loaded, $validated) as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) continue;
            $list = $this->attachments->listForInvoice($id);
            if (count($list) === 0) {
                $row['__missing_attachments'] = true;
                $missing[] = $row;
            }
        }

        // Períodos con movimientos sin cierre.
        $periods = $this->repo->listPeriodsWithMovements(60);
        $closed = $this->repo->listClosedPeriods(200);
        $closedMap = array_fill_keys($closed, true);

        $unclosed = [];
        foreach ($periods as $p) {
            if (!isset($closedMap[$p])) $unclosed[] = $p;
        }

        return [
            'loaded_not_validated' => $loaded,
            'purchases_missing_attachments' => $missing,
            'unclosed_periods' => $unclosed,
        ];
    }
}
