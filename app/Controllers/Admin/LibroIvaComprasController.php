<?php
namespace App\Controllers\Admin;

use App\Services\Billing\LibroIvaComprasService;
use App\Services\Billing\PurchaseInvoiceService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionLibroIvaComprasViewModel;

final class LibroIvaComprasController
{
    private function normalizeFilters(array $src): array
    {
        return [
            'from' => (string)($src['from'] ?? ''),
            'to' => (string)($src['to'] ?? ''),
            'supplier_id' => (int)($src['supplier_id'] ?? 0),
            'status' => (string)($src['status'] ?? ''),
            'kind' => (string)($src['kind'] ?? ''),
        ];
    }

    public function index(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new LibroIvaComprasService(db());
        $data = $svc->build($filters);

        // Para filtro de proveedor
        $suppliers = (new PurchaseInvoiceService(db()))->suppliersForSelect();
        $data['suppliers'] = $suppliers;
        $regime = strtolower(trim((string)app_setting('fiscal_seller_tax_regime', 'monotributo')));
        if ($regime === '') {
            $regime = 'monotributo';
        }
        $data['is_ri'] = ($regime === 'responsable_inscripto');
        $vm = new FacturacionLibroIvaComprasViewModel();

        render_page('admin/facturacion/libro_iva_compras', $vm->build($data), [
            'pageId' => 'admin-facturacion-libro-iva-compras',
        ]);
    }

    public function export(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $from = trim((string)($filters['from'] ?? ''));
        $to   = trim((string)($filters['to'] ?? ''));
        if (!$from) $from = date('Y-m-01');
        if (!$to)   $to   = date('Y-m-d');

        $svc = new LibroIvaComprasService(db());
        $rows = $svc->exportRows($from, $to, $filters);

        $nowTag = date('Ymd_His');
        $filename = 'libro_iva_compras_' . $from . '_a_' . $to . '_' . $nowTag . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','issued_at','kind','cbte_tipo','pto_vta','cbte_nro','supplier_id','supplier_nombre','supplier_cuit','imp_neto','imp_iva','imp_total','status','notes']);

        $toArs = static function (int $pesos): string {
            return number_format($pesos, 2, '.', '');
        };

        foreach ($rows as $r) {
            fputcsv($out, [
                (int)($r['id'] ?? 0),
                (string)($r['issued_at'] ?? ''),
                (string)($r['kind'] ?? ''),
                (int)($r['cbte_tipo'] ?? 0),
                (int)($r['pto_vta'] ?? 0),
                (int)($r['cbte_nro'] ?? 0),
                (int)($r['supplier_id'] ?? 0),
                (string)($r['supplier_nombre'] ?? ''),
                (string)($r['supplier_cuit'] ?? ''),
                $toArs((int)($r['imp_neto'] ?? 0)),
                $toArs((int)($r['imp_iva'] ?? 0)),
                $toArs((int)($r['imp_total'] ?? 0)),
                (string)($r['status'] ?? ''),
                (string)($r['notes'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }
}

