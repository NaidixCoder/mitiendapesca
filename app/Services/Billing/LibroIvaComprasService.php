<?php
namespace App\Services\Billing;

use App\Repositories\Billing\PurchaseInvoiceRepository;
use PDO;

final class LibroIvaComprasService
{
    private PurchaseInvoiceRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new PurchaseInvoiceRepository($db);
    }

    /**
     * @param array{from?:string,to?:string,supplier_id?:int,status?:string,kind?:string} $filters
     * @return array{filters:array,rows:array,totals:array,totals_by_type:array,suppliers:array}
     */
    public function build(array $filters): array
    {
        $from = trim((string)($filters['from'] ?? ''));
        $to   = trim((string)($filters['to'] ?? ''));

        if ($from === '') $from = date('Y-m-01');
        if ($to === '')   $to   = date('Y-m-d');

        $rows = $this->repo->libroRows($from, $to, $filters);
        $totals = $this->repo->libroTotals($from, $to, $filters);
        $byType = $this->repo->libroTotalsByType($from, $to, $filters);

        return [
            'filters' => array_merge($filters, ['from'=>$from,'to'=>$to]),
            'rows' => $rows,
            'totals' => $totals,
            'totals_by_type' => $byType,
        ];
    }

    public function exportRows(string $from, string $to, array $filters = []): array
    {
        return $this->repo->libroRows($from, $to, $filters);
    }
}
