<?php
namespace App\Controllers\Admin;

use App\Services\Billing\LibroIvaService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionLibroIvaViewModel;

final class LibroIvaController
{
    private function normalizeFilters(array $src): array
    {
        return [
            'from' => (string)($src['from'] ?? ''),
            'to' => (string)($src['to'] ?? ''),
            'arca_env' => (string)($src['arca_env'] ?? ''),
            'kind' => (string)($src['kind'] ?? ''),
        ];
    }

    public function index(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new LibroIvaService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionLibroIvaViewModel())->build($data);

        render_page('admin/facturacion/libro_iva', $view, [
            'pageId' => 'admin-facturacion-libro-iva',
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

        $env = trim((string)($filters['arca_env'] ?? ''));
        $kind = trim((string)($filters['kind'] ?? ''));

        $svc = new LibroIvaService(db());
        $rows = $svc->exportRows($from, $to, $env, $kind);

        $suffixEnv = $env ? ('_' . $env) : '';
        $kindTag = $kind ? ('_' . $kind) : '';
        $nowTag = date('Ymd_His');
        $filename = 'libro_iva_ventas_' . $from . '_a_' . $to . $kindTag . $suffixEnv . '_' . $nowTag . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','issued_at','kind','cbte_tipo','pto_vta','cbte_nro','order_id','doc_tipo','doc_nro','imp_neto','imp_iva','imp_total','cae','cae_vto','arca_env']);
        $toArs = static function (int $pesos): string {
            // Importes guardados en pesos (ARS). Exportamos con punto decimal para CSV.
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
                (int)($r['order_id'] ?? 0),
                (int)($r['doc_tipo'] ?? 0),
                (string)($r['doc_nro'] ?? ''),
                $toArs((int)($r['imp_neto'] ?? 0)),
                $toArs((int)($r['imp_iva'] ?? 0)),
                $toArs((int)($r['imp_total'] ?? 0)),
                (string)($r['cae'] ?? ''),
                (string)($r['cae_vto'] ?? ''),
                (string)($r['arca_env'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }
}

