<?php
namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseController;
use App\Repositories\Admin\AuditSearchRepository;
use App\Services\Admin\Audit\AuditEnrichmentService;
use App\Services\Admin\Audit\ProductBusinessGroupingService;
use App\Services\Admin\Audit\AuditIndexService;
use App\Services\Admin\Audit\AuditShowHydratorService;
use App\Services\Admin\Audit\AuditSnapshotService;
use App\Services\Admin\Audit\AuditShowService;
use App\Services\Admin\Audit\AuditLoginsService;
use App\Services\Admin\Audit\AuditViewPresenterService;
use App\Services\Admin\Audit\ViewModels\AuditLoginsViewModel;
use App\Services\Admin\Audit\ViewModels\AuditShowViewModel;

final class AuditController extends BaseController
{
    public function index(): void
    {
        $db = db();

        $repo = new AuditSearchRepository($db);
        $enricher = new AuditEnrichmentService();
        $grouping = new ProductBusinessGroupingService();
        $svc = new AuditIndexService($repo, $enricher, $grouping);
        $presenter = new AuditViewPresenterService();

        $view = $svc->buildViewData($db, $_GET);
        $view = array_merge($view, $presenter->presentIndex($view, $_SERVER));
        $view['extraHead'] = $this->auditCss();
        render_page('admin/audit/index', $view);
    }

    public function logins(): void
    {
        $db = db();

        $svc = new AuditLoginsService();
        $presenter = new AuditViewPresenterService();
        $view = $svc->buildViewData($db, $_GET);
        $view = array_merge($view, $presenter->presentLogins($view));
        $vm = (new AuditLoginsViewModel())->build($view);
        $view['extraHead'] = $this->auditCss();

        render_page('admin/audit/logins', array_merge($view, $vm));
    }

    /**
     * Limpieza manual (admin) de auth_login_events.
     * Seguridad:
     * - Requiere admin (BaseController)
     * - Requiere CSRF
     */
    public function loginsCleanup(): void
    {
        if (!$this->isPost()) {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        if (!function_exists('verify_csrf_or_abort')) {
            http_response_code(403);
            echo 'Protección CSRF no disponible.';
            return;
        }
        verify_csrf_or_abort();

        // En producción NO ofrecemos simulación: sólo ejecución real (borra registros).
        if ((string)($_POST['dry_run'] ?? '') === '1') {
            flash('error', 'La simulación de limpieza no está disponible.');
            $back = (string)($_POST['_back'] ?? '/admin/audit/logins');
            redirect($back);
            return;
        }

        if ((string)($_POST['confirm'] ?? '') !== '1') {
            flash('warn', 'Para ejecutar la limpieza tenés que confirmar la acción.');
            $back = (string)($_POST['_back'] ?? '/admin/audit/logins');
            redirect($back);
            return;
        }

        try {
            $db = db();
            $svc = new AuditLoginsService();
            $res = $svc->cleanup($db, $_POST);
            if (!empty($res['ok'])) {
                flash('ok', (string)($res['message'] ?? 'Limpieza OK.'));
            } else {
                flash('error', (string)($res['message'] ?? 'No se pudo ejecutar la limpieza.'));
            }
        } catch (\Throwable $e) {
            flash('error', 'No se pudo ejecutar la limpieza. Revisá logs.');
        }

        $back = (string)($_POST['_back'] ?? '/admin/audit/logins');
        if (!preg_match('~^/admin/audit(?:/logins)?(?:\?[a-zA-Z0-9_=&%+.-]*)?$~D', $back)) {
            $back = '/admin/audit/logins';
        }
        redirect($back);
    }

    public function show(): void
    {
        $rawId = $_GET['id'] ?? null;
        $id = (is_scalar($rawId) && ctype_digit((string)$rawId)) ? (int)$rawId : 0;
        if ($id <= 0) {
            http_response_code(404);
            echo 'No encontrado';
            return;
        }

        $db = db();

        $repo = new AuditSearchRepository($db);
        $enricher = new AuditEnrichmentService();
        $grouping = new ProductBusinessGroupingService();
        $hydrator = new AuditShowHydratorService($repo, $enricher, $grouping);
        $snapshot = new AuditSnapshotService();
        $svc = new AuditShowService($repo, $enricher, $hydrator, $snapshot);
        $presenter = new AuditViewPresenterService();

        $back = (string)($_GET['back'] ?? '');
        $backUrl = $this->sanitizeBackUrl($back);

        $view = $svc->buildViewData($db, $id, $backUrl);
        if (!empty($view['__NOT_FOUND__'])) {
            http_response_code(404);
            echo 'No encontrado';
            return;
        }

        $view = array_merge($view, $presenter->presentShow($view));
        $vm = (new AuditShowViewModel())->build($view);
        $view['extraHead'] = $this->auditCss();
        render_page('admin/audit/show', array_merge($view, $vm));
    }

    private function auditCss(): string
    {
        $basePath = rtrim((string)(parse_url(base_url(), PHP_URL_PATH) ?: ''), '/');
        $href = $basePath . '/assets/css/admin-v3/pages/audit.css';
        return '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '?v=' . (int)filemtime(BASE_PATH . '/public_html/assets/css/admin-v3/pages/audit.css') . '">';
    }

    private function sanitizeBackUrl(string $back): string
    {
        $back = trim($back);
        if ($back === '') return base_url('/admin/audit');

        // Evitar open redirect / URLs absolutas
        if (str_contains($back, '://') || str_starts_with($back, '//')) {
            return base_url('/admin/audit');
        }

        // Aceptamos solo volver a auditoría (listado o tabs)
        if (!preg_match('~^/admin/audit(?:/logins)?(?:\?[a-zA-Z0-9_=&%+.-]*)?$~D', $back)) {
            return base_url('/admin/audit');
        }

        // Si ya viene con base path (ej. /mi-pesca/public_html/admin/audit), lo dejamos.
        if (!str_starts_with($back, '/')) {
            $back = '/' . $back;
        }
        return $back;
    }
}
