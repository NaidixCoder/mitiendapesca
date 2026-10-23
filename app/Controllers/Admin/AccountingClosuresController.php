<?php
namespace App\Controllers\Admin;

use App\Repositories\Accounting\AccountingClosureRepository;
use App\Services\Accounting\MonthlyClosureService;
use App\Services\Accounting\AccountingClosureExportService;
use App\Services\Accounting\ClosurePeriodOptionsService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionCierresMensualesIndexViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionCierresMensualesShowViewModel;
use RuntimeException;
use Throwable;

final class AccountingClosuresController extends BaseController
{
    public function index(): void
    {
        admin_required();
        $repo = new AccountingClosureRepository(db());
        $closures = $repo->listLatest(36);

        $optSvc = new ClosurePeriodOptionsService(db());
        $opt = $optSvc->buildOptions(24);
        $regime = strtolower(trim((string)app_setting('fiscal_seller_tax_regime', 'monotributo')));
        if ($regime === '') {
            $regime = 'monotributo';
        }
        $vm = new FacturacionCierresMensualesIndexViewModel();

        render_page('admin/facturacion/cierres_mensuales/index', $vm->build([
            'closures' => $closures,
            'periodOptions' => $opt['options'] ?? [],
            'defaultPeriod' => (string)($opt['default_period'] ?? ''),
            'is_ri' => ($regime === 'responsable_inscripto'),
        ]), [
            'pageId' => 'admin-facturacion-cierres-mensuales',
        ]);
    }

    public function show(): void
    {
        admin_required();
        $period = trim((string)($_GET['period'] ?? ''));
        if ($period === '' || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $period)) {
            http_response_code(400);
            render_page('errors/400', ['pageId' => 'error-400']);
            return;
        }

        $svc = new MonthlyClosureService(db());
        $closure = $svc->getForPeriod($period);
        if (!$closure) {
            http_response_code(404);
            render_page('errors/404', ['pageId' => 'error-404']);
            return;
        }
        $vm = new FacturacionCierresMensualesShowViewModel();

        render_page('admin/facturacion/cierres_mensuales/show', $vm->build([
            'closure' => $closure,
        ]), [
            'pageId' => 'admin-facturacion-cierres-mensuales',
        ]);
    }

    public function close(): void
    {
        admin_required();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!csrf_check($_POST['_csrf'] ?? null)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => 'CSRF inválido.']);
                return;
            }

            $period = trim((string)($_POST['period'] ?? ''));
            $uid = (int)($_SESSION['uid'] ?? 0);
            if ($uid <= 0) throw new RuntimeException('Sesión inválida.');

            $svc = new MonthlyClosureService(db());
            $closure = $svc->closePeriod($period, $uid);

            echo json_encode([
                'ok' => true,
                'message' => 'Período cerrado correctamente.',
                'period' => (string)($closure['period'] ?? ''),
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            error_log('[Facturacion] Error de cierre mensual: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'message' => 'No se pudo cerrar el período. Revisá los datos o consultá al administrador.']);
        }
    }

    public function export(): void
    {
        admin_required();
        $period = trim((string)($_GET['period'] ?? ''));
        if ($period === '' || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $period)) {
            http_response_code(400);
            echo 'Período inválido.';
            return;
        }

        $svc = new MonthlyClosureService(db());
        $closure = $svc->getForPeriod($period);
        if (!$closure) {
            http_response_code(404);
            echo 'Snapshot no encontrado.';
            return;
        }

        $exp = new AccountingClosureExportService();
        $rows = $exp->toCsvRows($closure);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cierre_iva_' . $period . '.csv"');

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Periodo',
            'IVA Ventas',
            'IVA Compras',
            'Saldo IVA',
            'Ventas Comprobantes',
            'Ventas Neto',
            'Ventas Total',
            'Ventas Factura A Total',
            'Ventas Factura B Total',
            'Ventas Factura C Total',
            'Ventas NC A Total',
            'Ventas NC B Total',
            'Ventas NC C Total',
            'Compras Comprobantes',
            'Compras Neto',
            'Compras Total',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['periodo'] ?? ''),
                (string)($r['iva_ventas'] ?? 0),
                (string)($r['iva_compras'] ?? 0),
                (string)($r['saldo_iva'] ?? 0),

                (string)($r['ventas_comprobantes'] ?? 0),
                (string)($r['ventas_neto'] ?? 0),
                (string)($r['ventas_total'] ?? 0),

                (string)($r['ventas_fa_total'] ?? 0),
                (string)($r['ventas_fb_total'] ?? 0),
                (string)($r['ventas_fc_total'] ?? 0),

                (string)($r['ventas_nca_total'] ?? 0),
                (string)($r['ventas_ncb_total'] ?? 0),
                (string)($r['ventas_ncc_total'] ?? 0),

                (string)($r['compras_comprobantes'] ?? 0),
                (string)($r['compras_neto'] ?? 0),
                (string)($r['compras_total'] ?? 0),
            ]);
        }

        fclose($out);
    }
}

