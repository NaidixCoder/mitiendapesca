<?php
namespace App\Controllers\Admin;

use App\Repositories\Suppliers\ProveedorRepository;
use App\Services\Admin\Proveedores\ViewModels\ProveedoresFormViewModel;
use App\Services\Suppliers\ProveedorService;
use Throwable;

final class ProveedoresController extends BaseController
{
    private function service(): ProveedorService
    {
        return new ProveedorService(new ProveedorRepository(db()));
    }

    private function buildFormViewData(?array $item, array $errors, array $old): array
    {
        return (new ProveedoresFormViewModel())->build(['item' => $item, 'errors' => $errors, 'old' => $old]);
    }

    public function index(): void
    {
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
        $status = (string)($_GET['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive', 'all'], true)) $status = 'active';
        $per = (int)($_GET['per'] ?? 25);
        if (!in_array($per, [25, 50], true)) $per = 25;
        $page = max(1, min(100000, (int)($_GET['page'] ?? 1)));
        $service = $this->service();
        $filters = ['q' => $q, 'status' => $status];
        $total = $service->count($filters);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($page, $pages);
        $items = $service->list($filters + ['limit' => $per, 'offset' => ($page - 1) * $per]);
        $baseQuery = ['q' => $q, 'status' => $status, 'per' => $per];
        $prevUrl = url('/admin/proveedores?' . http_build_query($baseQuery + ['page' => max(1, $page - 1)]));
        $nextUrl = url('/admin/proveedores?' . http_build_query($baseQuery + ['page' => min($pages, $page + 1)]));
        $this->view('admin/proveedores/index', compact('q', 'status', 'per', 'page', 'pages', 'total', 'items', 'prevUrl', 'nextUrl'));
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $item = $id > 0 ? $this->service()->findById($id) : null;
        if ($id > 0 && !$item) {
            flash('error', 'Proveedor no encontrado.');
            redirect('/admin/proveedores');
            return;
        }
        $this->view('admin/proveedores/form', $this->buildFormViewData($item, [], []));
    }

    public function save(): void
    {
        verify_csrf_or_abort();
        $id = (int)($_POST['id'] ?? 0);
        try {
            $service = $this->service();
            $item = $id > 0 ? $service->findById($id) : null;
            if ($id > 0 && !$item) {
                flash('error', 'Proveedor no encontrado.');
                redirect('/admin/proveedores');
                return;
            }
            $res = $service->save($_POST, $id > 0 ? $id : null);
            if (!$res['ok']) {
                $this->view('admin/proveedores/form', $this->buildFormViewData(
                    $item,
                    is_array($res['errors'] ?? null) ? $res['errors'] : [],
                    $_POST
                ));
                return;
            }
            flash('ok', $id > 0 ? 'Proveedor actualizado.' : 'Proveedor creado.');
            redirect('/admin/proveedores');
        } catch (Throwable $e) {
            error_log((string)$e);
            flash('error', 'No se pudo guardar el proveedor. Verificá que el CUIT no esté duplicado.');
            redirect('/admin/proveedores');
        }
    }

    public function delete(): void
    {
        verify_csrf_or_abort();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Proveedor inválido.');
            redirect('/admin/proveedores');
            return;
        }
        try {
            $changed = $this->service()->delete($id);
            flash($changed ? 'ok' : 'error', $changed ? 'Proveedor desactivado.' : 'El proveedor ya estaba inactivo o no existe.');
        } catch (Throwable $e) {
            error_log((string)$e);
            flash('error', 'No se pudo desactivar el proveedor.');
        }
        redirect('/admin/proveedores');
    }
}
