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

    /**
     * @param array<string,mixed>|null $item
     * @param array<string,mixed> $errors
     * @param array<string,mixed> $old
     * @return array<string,mixed>
     */
    private function buildFormViewData(?array $item, array $errors, array $old): array
    {
        return (new ProveedoresFormViewModel())->build([
            'item' => $item,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $items = $this->service()->list([
            'q' => $q,
            'limit' => 200,
        ]);

        $this->view('admin/proveedores/index', [
            'q' => $q,
            'items' => $items,
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $item = null;
        if ($id > 0) {
            $item = $this->service()->findById($id);
            if (!$item) {
                flash('error', 'Proveedor no encontrado.');
                redirect('/admin/proveedores');
                return;
            }
        }

        $this->view('admin/proveedores/form', $this->buildFormViewData($item, [], []));
    }

    public function save(): void
    {
        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

            $res = $this->service()->save($_POST, $id && $id > 0 ? $id : null);

            if (!$res['ok']) {
                $this->view('admin/proveedores/form', $this->buildFormViewData(
                    $id ? $this->service()->findById((int)$id) : null,
                    is_array($res['errors'] ?? null) ? $res['errors'] : [],
                    is_array($_POST) ? $_POST : []
                ));
                return;
            }

            flash('ok', $id ? 'Proveedor actualizado.' : 'Proveedor creado.');
            redirect('/admin/proveedores');
        } catch (Throwable $e) {
            error_log($e);
            flash('error', 'No se pudo guardar el proveedor.');
            redirect('/admin/proveedores');
        }
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Proveedor inválido.');
            redirect('/admin/proveedores');
            return;
        }

        $this->service()->delete($id);
        flash('ok', 'Proveedor eliminado.');
        redirect('/admin/proveedores');
    }
}

