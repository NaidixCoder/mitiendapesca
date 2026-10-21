<?php
namespace App\Controllers\Admin;

use App\Services\Billing\PurchaseInvoiceService;
use App\Services\Billing\PurchaseInvoiceAttachmentService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionComprasFormViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionComprasIndexViewModel;
use Throwable;

final class ComprasController extends BaseController
{
    /**
     * @param array<string,mixed>|null $item
     * @param array<int,array<string,mixed>> $suppliers
     * @param array<int,array<string,mixed>> $attachments
     * @param array<string,string> $errors
     * @param array<string,mixed> $old
     * @return array<string,mixed>
     */
    private function buildFormViewData(?array $item, array $suppliers, array $attachments, array $errors, array $old): array
    {
        return (new FacturacionComprasFormViewModel())->build([
            'item' => $item,
            'suppliers' => $suppliers,
            'attachments' => $attachments,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function service(): PurchaseInvoiceService
    {
        return new PurchaseInvoiceService(db());
    }

    public function index(): void
    {
        $filters = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'from' => trim((string)($_GET['from'] ?? '')),
            'to' => trim((string)($_GET['to'] ?? '')),
            'supplier_id' => (int)($_GET['supplier_id'] ?? 0),
            'status' => trim((string)($_GET['status'] ?? '')),
            'kind' => trim((string)($_GET['kind'] ?? '')),
        ];

        if (!$filters['from']) $filters['from'] = date('Y-m-01');
        if (!$filters['to']) $filters['to'] = date('Y-m-d');

        $items = $this->service()->list($filters);
        $suppliers = $this->service()->suppliersForSelect();
        $vm = new FacturacionComprasIndexViewModel();

        $this->view('admin/facturacion/compras/index', $vm->build([
            'filters' => $filters,
            'items' => $items,
            'suppliers' => $suppliers,
        ]));
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $item = null;
        if ($id > 0) {
            $item = $this->service()->findById($id);
            if (!$item) {
                flash('error', 'Compra no encontrada.');
                redirect('/admin/facturacion/compras');
                return;
            }
        }

        $suppliers = $this->service()->suppliersForSelect();

        $this->view('admin/facturacion/compras/form', $this->buildFormViewData(
            $item,
            $suppliers,
            $id > 0 ? (new PurchaseInvoiceAttachmentService())->listForInvoice($id) : [],
            [],
            []
        ));
    }

    public function save(): void
    {
        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            $res = $this->service()->save($_POST, $id > 0 ? $id : null);

            if (!$res['ok']) {
                $suppliers = $this->service()->suppliersForSelect();
                $this->view('admin/facturacion/compras/form', $this->buildFormViewData(
                    $id > 0 ? $this->service()->findById($id) : null,
                    $suppliers,
                    $id > 0 ? (new PurchaseInvoiceAttachmentService())->listForInvoice($id) : [],
                    $res['errors'],
                    $_POST
                ));
                return;
            }

            flash('ok', $id > 0 ? 'Compra actualizada.' : 'Compra creada.');
            redirect('/admin/facturacion/compras');
        } catch (Throwable $e) {
            error_log($e);
            flash('error', 'No se pudo guardar la compra.');
            redirect('/admin/facturacion/compras');
        }
    }
    public function validate(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Compra inválida.');
            redirect('/admin/facturacion/compras');
            return;
        }

        $this->service()->validate($id);
        flash('ok', 'Compra marcada como validada.');
        redirect('/admin/facturacion/compras');
    }


    public function void(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Compra inválida.');
            redirect('/admin/facturacion/compras');
            return;
        }

        $this->service()->void($id);
        flash('ok', 'Compra anulada.');
        redirect('/admin/facturacion/compras');
    }
    // POST /admin/facturacion/compras/adjunto/subir (id, file=attachment)
    public function uploadAttachment(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Compra inválida.');
            redirect('/admin/facturacion/compras');
            return;
        }
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $file = $_FILES['attachment'] ?? null;
        $svc = new PurchaseInvoiceAttachmentService();
        [$ok, $msg, $name] = $svc->saveForInvoice($id, is_array($file) ? $file : []);
        flash($ok ? 'ok' : 'error', $msg);
        redirect('/admin/facturacion/compras/edit?id=' . $id);
    }

    // GET /admin/facturacion/compras/adjunto/ver?id=..&file=..
    public function viewAttachment(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $file = (string)($_GET['file'] ?? '');
        if ($id <= 0 || trim($file) === '') {
            redirect('/admin/facturacion/compras');
            return;
        }

        $svc = new PurchaseInvoiceAttachmentService();
        $path = $svc->resolvePath($id, $file);
        if (!$path) {
            redirect('/admin/facturacion/compras/edit?id=' . $id);
            return;
        }

        $mime = $svc->detectMime($path);
        if (!$svc->isPreviewableMime($mime)) {
            redirect('/admin/facturacion/compras/adjunto/descargar?id=' . $id . '&file=' . urlencode(basename($path)));
            return;
        }

        $name = basename($path);
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    // GET /admin/facturacion/compras/adjunto/descargar?id=..&file=..
    public function downloadAttachment(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $file = (string)($_GET['file'] ?? '');
        if ($id <= 0 || trim($file) === '') {
            redirect('/admin/facturacion/compras');
            return;
        }

        $svc = new PurchaseInvoiceAttachmentService();
        $path = $svc->resolvePath($id, $file);
        if (!$path) {
            redirect('/admin/facturacion/compras/edit?id=' . $id);
            return;
        }

        $name = basename($path);
        $mime = $svc->detectMime($path);
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    // POST /admin/facturacion/compras/adjunto/eliminar (id, file)
    public function deleteAttachment(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $file = (string)($_POST['file'] ?? '');
        if ($id <= 0 || trim($file) === '') {
            flash('error', 'Adjunto inválido.');
            redirect('/admin/facturacion/compras');
            return;
        }
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $svc = new PurchaseInvoiceAttachmentService();
        $ok = $svc->delete($id, $file);
        flash($ok ? 'ok' : 'error', $ok ? 'Adjunto eliminado.' : 'No se pudo eliminar el adjunto.');
        redirect('/admin/facturacion/compras/edit?id=' . $id);
    }

}

