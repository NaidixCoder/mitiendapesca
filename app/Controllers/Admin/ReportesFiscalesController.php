<?php
namespace App\Controllers\Admin;

use App\Services\Billing\FiscalExportGuard;

use App\Services\Billing\ReportesFiscalesService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesDesviosViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesResumenViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesPedidosSinFacturaViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesComprobantesPendientesViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesDuplicadosExactosViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesPedidosNoFacturablesViewModel;

final class ReportesFiscalesController
{
    private function normalizeFilters(array $src): array
    {
        return [
            'from' => (string)($src['from'] ?? ''),
            'to' => (string)($src['to'] ?? ''),
            'group' => (string)($src['group'] ?? 'day'),
            'arca_env' => (string)($src['arca_env'] ?? ''),
            'tab' => (string)($src['tab'] ?? 'resumen'),
        ];
    }

    /**
     * @return array{from:string,to:string}
     */
    private function periodBounds(string $group, string $period, string $fallbackFrom, string $fallbackTo): array
    {
        $group = ($group === 'month') ? 'month' : 'day';
        $period = trim($period);

        if ($group === 'month' && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $y = (int)substr($period, 0, 4);
            $m = (int)substr($period, 5, 2);
            $from = sprintf('%04d-%02d-01', $y, $m);
            $to = date('Y-m-t', strtotime($from));
            return ['from' => $from, 'to' => $to];
        }

        if ($group === 'day' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
            return ['from' => $period, 'to' => $period];
        }

        return ['from' => $fallbackFrom, 'to' => $fallbackTo];
    }

    public function index(): void
    {
        admin_required();

        $regime = strtolower(trim((string)app_setting('fiscal_seller_tax_regime', 'monotributo')));
        if ($regime === '') $regime = 'monotributo';
        $isRI = ($regime === 'responsable_inscripto');

        render_page('admin/facturacion/reportes/home', [
            'isRI' => $isRI,
        ], [
            'pageId' => 'admin-facturacion-reportes-home',
        ]);
    }

    public function resumen(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionReportesResumenViewModel())->build($data);

        render_page('admin/facturacion/reportes/resumen', $view, [
            'pageId' => 'admin-facturacion-reportes',
        ]);
    }

    /**
     * Drill-down: abre la lista de comprobantes filtrada por período/tipo.
     * Redirige a /admin/facturacion/comprobantes (evita duplicar UI).
     */
    public function drillComprobantes(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);
        $period = (string)($_GET['period'] ?? '');
        $kind = trim((string)($_GET['kind'] ?? ''));

        $svc = new ReportesFiscalesService(db());
        $built = $svc->build($filters);
        $f = $built['filters'] ?? $filters;

        $bounds = $this->periodBounds((string)($f['group'] ?? 'day'), $period, (string)($f['from'] ?? ''), (string)($f['to'] ?? ''));

        $qs = [
            'status' => 'issued',
            'kind' => $kind,
            'arca_env' => (string)($f['arca_env'] ?? ''),
            'issued_from' => (string)($bounds['from'] ?? ''),
            'issued_to' => (string)($bounds['to'] ?? ''),
        ];
        foreach ($qs as $k => $v) {
            if ($v === '') unset($qs[$k]);
        }

        redirect('/admin/facturacion/comprobantes?' . http_build_query($qs));
    }

    public function pedidosSinFactura(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionReportesPedidosSinFacturaViewModel())->build($data);

        render_page('admin/facturacion/reportes/pedidos_sin_factura', $view, [
            'pageId' => 'admin-facturacion-reportes-pedidos-sin-factura',
        ]);
    }

    public function comprobantesPendientes(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionReportesComprobantesPendientesViewModel())->build($data);

        render_page('admin/facturacion/reportes/comprobantes_pendientes', $view, [
            'pageId' => 'admin-facturacion-reportes-comprobantes-pendientes',
        ]);
    }

    public function desvios(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionReportesDesviosViewModel())->build($data);

        render_page('admin/facturacion/reportes/desvios', $view, [
            'pageId' => 'admin-facturacion-reportes-desvios',
        ]);
    }



    public function duplicadosExactos(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionReportesDuplicadosExactosViewModel())->build($data);

        render_page('admin/facturacion/reportes/duplicados_exactos', $view, [
            'pageId' => 'admin-facturacion-reportes-duplicados-exactos',
        ]);
    }

    public function pedidosNoFacturables(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);
        $view = (new FacturacionReportesPedidosNoFacturablesViewModel())->build($data);

        render_page('admin/facturacion/reportes/pedidos_no_facturables', $view, [
            'pageId' => 'admin-facturacion-reportes-pedidos-no-facturables',
        ]);
    }

    public function export(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);
        $filters['from'] = trim((string)($filters['from'] ?? '')) ?: date('Y-m-01');
        $filters['to'] = trim((string)($filters['to'] ?? '')) ?: date('Y-m-d');
        try {
            FiscalExportGuard::requireDateRange($filters['from'], $filters['to']);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo 'Rango de fechas inválido.';
            return;
        }
        $mode = trim((string)($_GET['mode'] ?? 'period'));
        $kind = trim((string)($_GET['kind'] ?? ''));

        $svc = new ReportesFiscalesService(db());
        $data = $svc->build($filters);

        $from = (string)($data['filters']['from'] ?? date('Y-m-01'));
        $to   = (string)($data['filters']['to'] ?? date('Y-m-d'));
        $env  = (string)($data['filters']['arca_env'] ?? '');
        $group = (string)($data['filters']['group'] ?? 'day');

        $suffixEnv = $env ? ('_' . $env) : '';
        $nowTag = date('Ymd_His');

        if ($mode === 'comprobantes') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="comprobantes_detalle.csv"');
            $out = fopen('php://output', 'w');
            // BOM para Excel
            fwrite($out, "\xEF\xBB\xBF");
            FiscalExportGuard::writeCsv($out, [
                'id','issued_at','kind','pto_vta','cbte_tipo','cbte_nro',
                'order_id','doc_tipo','doc_nro',
                'imp_total','imp_neto','imp_iva',
                'cae','cae_vto','arca_env','status',
                'related_comprobante_id','original_pto_vta','original_cbte_tipo','original_cbte_nro'
            ]);
            $repo = new \App\Repositories\Billing\ReportesFiscalesRepository(db());
            $rows = $repo->issuedComprobantesDetailed($from, $to, $env, $kind);
            foreach ($rows as $row) {
                FiscalExportGuard::writeCsv($out, [
                    $row['id'] ?? null,
                    $row['issued_at'] ?? null,
                    $row['kind'] ?? null,
                    $row['pto_vta'] ?? null,
                    $row['cbte_tipo'] ?? null,
                    $row['cbte_nro'] ?? null,
                    $row['order_id'] ?? null,
                    $row['doc_tipo'] ?? null,
                    $row['doc_nro'] ?? null,
                    $row['imp_total'] ?? null,
                    $row['imp_neto'] ?? null,
                    $row['imp_iva'] ?? null,
                    $row['cae'] ?? null,
                    $row['cae_vto'] ?? null,
                    $row['arca_env'] ?? null,
                    $row['status'] ?? null,
                    $row['related_comprobante_id'] ?? null,
                    $row['original_pto_vta'] ?? null,
                    $row['original_cbte_tipo'] ?? null,
                    $row['original_cbte_nro'] ?? null,
                ]);
            }
            fclose($out);
            exit;
        }

                if ($mode === 'conciliacion_pedidos') {
            $filename = 'conciliacion_pedidos_sin_factura_' . $from . '_a_' . $to . $suffixEnv . '_' . $nowTag . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

            $rows = (array)($data['conciliation']['orders_missing_invoice'] ?? []);

            $out = fopen('php://output', 'w');
            // BOM para Excel
            fwrite($out, "\xEF\xBB\xBF");
            FiscalExportGuard::writeCsv($out, ['order_id', 'created_at', 'order_status', 'total', 'customer_tax_condition', 'customer_cuit', 'comprobante_id', 'cf_status']);
            foreach ($rows as $r) {
                FiscalExportGuard::writeCsv($out, [
                    (int)($r['order_id'] ?? 0),
                    (string)($r['created_at'] ?? ''),
                    order_status_label_admin((string)($r['order_status'] ?? '')),
                    (int)($r['total'] ?? 0),
                    (string)($r['customer_tax_condition'] ?? ''),
                    (string)($r['customer_cuit'] ?? ''),
                    (int)($r['comprobante_id'] ?? 0),
                    (string)($r['cf_status'] ?? ''),
                ]);
            }
            fclose($out);
            exit;
        }

                if ($mode === 'conciliacion_comprobantes') {
            $filename = 'conciliacion_comprobantes_pendientes_error_' . $from . '_a_' . $to . $suffixEnv . '_' . $nowTag . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

            $rows = (array)($data['conciliation']['comprobantes_not_issued'] ?? []);

            $out = fopen('php://output', 'w');
            // BOM para Excel
            fwrite($out, "\xEF\xBB\xBF");
            FiscalExportGuard::writeCsv($out, ['id', 'created_at', 'order_id', 'kind', 'pto_vta', 'cbte_tipo', 'cbte_nro', 'status', 'arca_env']);
            foreach ($rows as $r) {
                FiscalExportGuard::writeCsv($out, [
                    (int)($r['id'] ?? 0),
                    (string)($r['created_at'] ?? ''),
                    (int)($r['order_id'] ?? 0),
                    (string)($r['kind'] ?? ''),
                    (int)($r['pto_vta'] ?? 0),
                    (int)($r['cbte_tipo'] ?? 0),
                    (int)($r['cbte_nro'] ?? 0),
                    (string)($r['status'] ?? ''),
                    (string)($r['arca_env'] ?? ''),
                ]);
            }
            fclose($out);
            exit;
        }

        
if ($mode === 'conciliacion_desvios') {
    $filename = 'conciliacion_desvios_' . $from . '_a_' . $to . $suffixEnv . '_' . $nowTag . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $rows = (array)($data['conciliation']['deviations'] ?? []);

    $out = fopen('php://output', 'w');
    FiscalExportGuard::writeCsv($out, ['order_id','order_status','order_total','invoices_total','credit_total','net_issued','resultado','env_min','env_max','last_issued_at']);
    foreach ($rows as $r) {
        FiscalExportGuard::writeCsv($out, [
            (int)($r['order_id'] ?? 0),
            order_status_label_admin((string)($r['order_status'] ?? '')),
            (int)($r['order_total'] ?? 0),
            (int)($r['invoices_total'] ?? 0),
            (int)($r['credit_total'] ?? 0),
            (int)($r['net_issued'] ?? 0),
            (string)($r['resultado_label'] ?? ''),
            (string)($r['env_min'] ?? ''),
            (string)($r['env_max'] ?? ''),
            (string)($r['last_issued_at'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}


                if ($mode === 'conciliacion_duplicados') {
            $filename = 'conciliacion_duplicados_exactos_' . $from . '_a_' . $to . $suffixEnv . '_' . $nowTag . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

            $rows = (array)($data['conciliation']['duplicates_exact'] ?? []);

            $out = fopen('php://output', 'w');
            // BOM para Excel
            fwrite($out, "\xEF\xBB\xBF");
            FiscalExportGuard::writeCsv($out, ['doc_tipo', 'doc_nro', 'pto_vta', 'cbte_tipo', 'cbte_nro', 'issued_at_min', 'issued_at_max', 'cantidad']);
            foreach ($rows as $r) {
                FiscalExportGuard::writeCsv($out, [
                    (int)($r['doc_tipo'] ?? 0),
                    (string)($r['doc_nro'] ?? ''),
                    (int)($r['pto_vta'] ?? 0),
                    (int)($r['cbte_tipo'] ?? 0),
                    (int)($r['cbte_nro'] ?? 0),
                    (string)($r['issued_at_min'] ?? ''),
                    (string)($r['issued_at_max'] ?? ''),
                    (int)($r['cantidad'] ?? 0),
                ]);
            }
            fclose($out);
            exit;
        }

                if ($mode === 'conciliacion_no_facturables') {
            $filename = 'conciliacion_pedidos_no_facturables_con_factura_' . $from . '_a_' . $to . $suffixEnv . '_' . $nowTag . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

            $rows = (array)($data['conciliation']['non_billable_with_invoice'] ?? []);

            $out = fopen('php://output', 'w');
            // BOM para Excel
            fwrite($out, "\xEF\xBB\xBF");
            FiscalExportGuard::writeCsv($out, ['order_id', 'created_at', 'order_status', 'total', 'comprobante_id', 'cf_status', 'issued_at']);
            foreach ($rows as $r) {
                FiscalExportGuard::writeCsv($out, [
                    (int)($r['order_id'] ?? 0),
                    (string)($r['created_at'] ?? ''),
                    order_status_label_admin((string)($r['order_status'] ?? '')),
                    (int)($r['total'] ?? 0),
                    (int)($r['comprobante_id'] ?? 0),
                    (string)($r['cf_status'] ?? ''),
                    (string)($r['issued_at'] ?? ''),
                ]);
            }
            fclose($out);
            exit;
        }


        // Default: serie por período
        $filename = 'reportes_fiscales_' . $from . '_a_' . $to . '_' . $group . $suffixEnv . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        FiscalExportGuard::writeCsv($out, ['periodo','facturas_cantidad','facturas_total','nc_cantidad','nc_total','neto']);

        foreach (($data['series'] ?? []) as $row) {
            $invTotal = (int)($row['invoices_total'] ?? 0);
            $cnTotal  = (int)($row['credit_notes_total'] ?? 0);
            FiscalExportGuard::writeCsv($out, [
                (string)($row['period'] ?? ''),
                (int)($row['invoices_count'] ?? 0),
                $invTotal,
                (int)($row['credit_notes_count'] ?? 0),
                $cnTotal,
                $invTotal - $cnTotal,
            ]);
        }

        fclose($out);
        exit;
    }
}

