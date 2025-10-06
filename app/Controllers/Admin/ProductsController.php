<?php
namespace App\Controllers\Admin;

use App\Services\Audit;
use App\Http\Requests\ProductRequest;
use App\Repositories\AdminProductRepository;
use App\Services\ProductImageService;
use App\Services\InventoryService;
use App\Queries\ProductQuery;
use PDO;

class ProductsController extends BaseController
{
    public function index(): void
    {
        $db = db();
        $query = new ProductQuery($db);

        $filters = [
            'q'           => $_GET['q']           ?? '',
            'brand_id'    => $_GET['brand_id']    ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'active'      => $_GET['active']      ?? null,
            'sort'        => $_GET['sort']        ?? 'created_at',
            'order'       => $_GET['order']       ?? 'desc',
            'page'        => $_GET['page']        ?? 1,
            'per'         => 20,
        ];

        $res = $query->search($filters);
        render_page('admin/products/index', $res);
    }

    public function form(): void
    {
        $db   = db();
        $repo = new AdminProductRepository($db);
        $id   = (int)($_GET['id'] ?? 0);

        $p      = $repo->load($id);
        $brands = $repo->brands();
        $cats   = $repo->categories();
        $inv    = (new InventoryService($db))->get($id);
        $imgs   = $repo->images($id);

        render_page('admin/products/form', ['pageId' => 'admin-product-form', 'product'=>$p, 'brands'=>$brands, 'cats'=>$cats, 'inv'=>$inv, 'imgs'=>$imgs]);
    }

    public function save(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':product_save', 20, 60);
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $db   = db();
        $repo = new AdminProductRepository($db);
        $req  = new ProductRequest();

        $id   = (int)($_POST['id'] ?? 0);
        $data = $req->collect();
        $errs = $req->validate($db, $data, $id);

        if ($errs) { flash('error', implode(' · ', $errs)); redirect($id?'/admin/products/edit?id='.$id:'/admin/products/new'); }

        if ($id > 0) {
            $ok = $repo->update($id, $data);
            if ($ok) { Audit::log('product.save','product',$id,['op'=>'update']); flash('ok','Producto actualizado.'); }
            else { flash('error','No se pudo actualizar.'); redirect('/admin/products/edit?id='.$id); }
        } else {
            $id = $repo->insert($data);
            if ($id <= 0) { flash('error','No se pudo crear.'); redirect('/admin/products/new'); }
            Audit::log('product.save','product',$id,['op'=>'insert']);
            flash('ok','Producto creado.');
        }

        $stock = (int)($_POST['stock'] ?? 0);
        $low   = (int)($_POST['low_stock_threshold'] ?? 3);
        (new InventoryService($db))->upsert($id, $stock, $low);

        $this->bustDashboardCaches();
        redirect('/admin/products/edit?id='.$id);
    }

    public function toggle(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':product_toggle', 20, 60);
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { flash('error','ID inválido'); redirect('/admin/products'); }

        $db  = db();
        $cur = (int)$db->query("SELECT is_active FROM products WHERE id={$id}")->fetchColumn();
        $new = $cur ? 0 : 1;

        $ok = $db->prepare("UPDATE products SET is_active=? WHERE id=?")->execute([$new, $id]);
        if ($ok) {
            Audit::log('product.toggle','product',$id,['from'=>$cur,'to'=>$new]);
            $this->bustDashboardCaches();
            flash('ok','Producto actualizado.');
        } else {
            flash('error','No se pudo actualizar.');
        }
        redirect('/admin/products');
    }

    public function imageUpload(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':product_image_upload', 20, 60);
        if (!verify_csrf()) { $this->jsonOrRedirect(false,'CSRF'); return; }

        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId<=0) { $this->jsonOrRedirect(false,'Producto inválido'); return; }

        if (empty($_FILES['image']) || $_FILES['image']['error']!==UPLOAD_ERR_OK) {
            $this->jsonOrRedirect(false,'Archivo no recibido'); return;
        }

        $svc = new ProductImageService(db());
        [$ok, $payload, $cover] = $svc->upload($productId, $_FILES['image']);
        if (!$ok) { $this->jsonOrRedirect(false, $payload); return; }

        Audit::log('product.image_upload','product',$productId,['file'=>$payload,'cover'=>$cover]);
        // devolver path relativo correcto (con subcarpeta del producto)
        $this->jsonOrRedirect(true,'Imagen subida',['path'=>$payload]);
    }

    public function imageDelete(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':product_image_delete', 20, 60);
        if (!verify_csrf()) { $this->jsonOrRedirect(false,'CSRF'); return; }

        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId   = (int)($_POST['image_id'] ?? 0);
        if ($productId<=0 || $imageId<=0) { $this->jsonOrRedirect(false,'Parámetros inválidos'); return; }

        $svc = new ProductImageService(db());
        [$ok, $msg] = $svc->delete($productId, $imageId);
        if ($ok) { Audit::log('product.image_delete','product',$productId,['image_id'=>$imageId]); }
        $this->jsonOrRedirect($ok, $msg);
    }

    public function imageCover(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':product_image_cover', 20, 60);
        if (!verify_csrf()) { $this->jsonOrRedirect(false,'CSRF'); return; }

        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId   = (int)($_POST['image_id'] ?? 0);
        if ($productId<=0 || $imageId<=0) { $this->jsonOrRedirect(false,'Parámetros inválidos'); return; }

        $svc = new ProductImageService(db());
        [$ok, $msg] = $svc->makeCover($productId, $imageId);
        if ($ok) { Audit::log('product.image_cover','product',$productId,['image_id'=>$imageId]); }
        $this->jsonOrRedirect($ok, $msg);
    }

    private function wantsJson(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'],'application/json'));
    }

    private function jsonOrRedirect(bool $ok, string $msg, array $extra=[]): void
    {
        if ($this->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            $payload = ['ok'=>$ok] + ($ok ? ['msg'=>$msg] : ['msg'=>$msg,'error'=>$msg]) + $extra;
            echo json_encode($payload);
            return;
        }
        $next = $_POST['next'] ?? ($_SERVER['HTTP_REFERER'] ?? url('/admin/products'));
        flash($ok ? 'ok' : 'error', $msg);
        redirect($next);
    }

    private function bustDashboardCaches(): void
    {
        @cache_forget('kpi:prod_activos');
        @cache_forget('kpi:inactivos');
        @cache_forget('list:featured_recent5');
        @cache_forget('kpi:stock_bajo');
        @cache_forget('list:low_stock_top10');
    }
}
