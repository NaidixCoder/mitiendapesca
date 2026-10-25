<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Audit\Audit;
use App\Http\Requests\Admin\ProductRequest;
use App\Repositories\Admin\AdminProductRepository;
use App\Repositories\Catalog\SupplierRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Admin\Products\ProductFormService;
use App\Services\Admin\Products\ProductPricingService;
use App\Services\Admin\Products\ProductImagesService;
use App\Services\Admin\Products\ProductSaveService;
use App\Services\Admin\Variants\VariantReadService;
use App\Services\Admin\ProductWizardService;
use App\Services\Admin\Products\ViewModels\ProductIndexViewModel;
use App\Services\Admin\Products\ViewModels\ProductFormPageViewModel;
use App\Services\Seo\SeoManagerService;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\ProductSeoBlocksService;
use App\Queries\Catalog\ProductQuery;
use PDO;
use Throwable;

class ProductsController extends BaseController
{
    /** Listado de productos admin */
    public function index(): void
    {
        $qstr = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per  = min(100, max(1, (int)($_GET['per'] ?? 20)));

        $db = db();

        $where  = '';
        $params = [];
        if ($qstr !== '') {
            $where = "WHERE (p.name LIKE :q1 OR p.sku LIKE :q2 OR EXISTS (
                SELECT 1 FROM product_variants pvq
                WHERE pvq.product_id = p.id AND pvq.sku LIKE :q3
            ))";
            $like = '%'.$qstr.'%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        try {
            // Total
            $stTotal = $db->prepare("SELECT COUNT(*) FROM products p $where");
            foreach ($params as $k => $v) { $stTotal->bindValue($k, $v, \PDO::PARAM_STR); }
            $stTotal->execute();
            $total = (int)$stTotal->fetchColumn();
            $pages = max(1, (int)ceil($total / $per));
            $page = min($page, $pages);
            $off = ($page - 1) * $per;

            // Filas (ojo: LIMIT con enteros ya saneados)
            $sql = "
                SELECT
                    p.*,
                    b.name AS brand_name,
                    c.name AS category_name,
                    inv.stock AS stock_simple,
                    pv.variants_count,
                    pv.vendible_variants_count,
                    pv.any_sku AS variant_sku_any,
                    pv.max_price_updated_at AS variant_price_updated_at
                FROM products p
                LEFT JOIN brands b     ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN inventory inv ON inv.product_id = p.id
                LEFT JOIN (
                    SELECT
                        product_id,
                        COUNT(*) AS variants_count,
                        SUM(
                            CASE
                                WHEN COALESCE(is_active, 1) = 1
                                 AND COALESCE(price, 0) > 0
                                 AND COALESCE(iv.stock, 0) > 0
                                THEN 1
                                ELSE 0
                            END
                        ) AS vendible_variants_count,
                        MIN(sku) AS any_sku,
                        MAX(price_updated_at) AS max_price_updated_at
                    FROM product_variants pv2
                    LEFT JOIN inventory_variants iv ON iv.variant_id = pv2.id
                    GROUP BY product_id
                ) pv ON pv.product_id = p.id
                $where
                ORDER BY p.created_at DESC
                LIMIT {$off}, {$per}
            ";
            $st = $db->prepare($sql);
            foreach ($params as $k => $v) { $st->bindValue($k, $v, \PDO::PARAM_STR); }
            $st->execute();
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $pages = max(1, (int)ceil($total / max(1, $per)));
            $indexVm = (new ProductIndexViewModel())->build($rows, $qstr, $page, $pages, $total);

            render_page('admin/products/index', [
                'pageId' => 'admin-products',
                'rows'   => $rows,
                'q'      => $qstr,
                'page'   => $page,
                'pages'  => $pages,
                'total'  => $total,
                'products_index' => $indexVm,
            ]);
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Admin\\ProductsController@index: '.$e->getMessage());
            }
            // No mostramos mensajes flash en el listado para evitar notificaciones duplicadas tras la redirección.
            http_response_code(500);
            render_page('errors/500', ['message' => 'No se pudo cargar el listado de productos.']);
            return;
        }
    }
    /** Form de alta/edicion */
    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ctx = (new ProductFormService(db()))->buildContext($id, $_GET);
        $ctx['pageId'] = 'admin-product-form';
        $ctx = array_merge($ctx, (new ProductFormPageViewModel())->build(
            $ctx,
            is_array($_GET) ? $_GET : [],
            (string)flash('error')
        ));
        render_page('admin/products/form', $ctx);
    }

    /** Estado del wizard (JSON) */
    public function wizardStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>false, 'message'=>'ID inválido.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            return;
        }
        try {
            $status = (new ProductWizardService(db()))->evaluate($id);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>true, 'status'=>$status], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\ProductsController@wizardStatus: '.$e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>false, 'message'=>'No se pudo obtener el estado'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
    }

    /** Guardar (alta/edición) */
    /** Guardar (alta/edicion) */
    public function save(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();
        (new ProductSaveService(db()))->handle();
    }

    /**
     * POST /admin/products/update-iva.json
     * IVA (Regla de Oro): el proveedor pasa COSTO SIN IVA + alícuota.
     * Guardamos iva_rate como factor (0.21 / 0.105) y, cuando cambia, recalculamos precios
     * para mantener el mismo markup sobre costo final.
     */
    public function updateIvaJson(): void
    {
        require_admin();
        if (!is_post()) json(['ok' => false, 'message' => 'Método no permitido.'], 405);

        verify_csrf_or_abort();

        $productId = (int)($_POST['id'] ?? $_POST['product_id'] ?? 0);
        $raw = (string)($_POST['iva_rate'] ?? '');

        $svc = new ProductPricingService(db());
        $result = $svc->updateIvaAndReprice($productId, $raw);

        json(
            $result['body'] ?? ['ok' => false, 'message' => 'Error al guardar IVA.'],
            (int)($result['status'] ?? 500)
        );
    }


    /** Activar/desactivar */
    public function toggle(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { flash('error','ID inválido.'); redirect('/admin/products'); }

        $db  = db();
        $cur = (int)$db->query("SELECT is_active FROM products WHERE id={$id}")->fetchColumn();
        $new = $cur ? 0 : 1;

        if ($new === 1) {
            $status = (new ProductWizardService($db))->evaluate($id);
            $blockers = (array)($status['blockers_globales'] ?? []);
            if (!empty($blockers)) {
                $msg = 'No se puede publicar desde el listado hasta completar los pendientes.';
                $details = implode(' ', array_slice($blockers, 0, 3));
                if ($details !== '') {
                    $msg .= ' Faltan: ' . $details;
                }
                flash('error', $msg);
                redirect('/admin/products');
                return;
            }
        }

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

    /** Subir imagen */
    public function imageUpload(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);

        $result = (new ProductImagesService(db()))->upload($productId, $variantId, $_FILES['file'] ?? null);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'Error subiendo imagen'), (array)($result['data'] ?? []));
    }

    /** Eliminar imagen (promueve nueva portada si hacia falta) */
    public function imageDelete(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);

        $result = (new ProductImagesService(db()))->delete($productId, $imageId, $variantId);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'No se pudo eliminar'), (array)($result['data'] ?? []));
    }

    /** Marcar como portada (transaccional: garantiza 1 sola) */
    public function imageCover(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);

        $result = (new ProductImagesService(db()))->cover($productId, $imageId, $variantId);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'No se pudo marcar la portada'), (array)($result['data'] ?? []));
    }

    /** Asignar/mover imagen a un alcance (General o Variante) */
    public function imageAssign(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);

        $result = (new ProductImagesService(db()))->assign($productId, $imageId, $variantId);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'No se pudo asignar la imagen'), (array)($result['data'] ?? []));
    }

    /** Asignacion masiva de imagenes a un alcance (General/Variante) */
    public function imageBulkAssign(): void
    {
        verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $targetVariantId = (int)($_POST['variant_id'] ?? 0);
        $idsRaw = $_POST['image_ids'] ?? '';

        $result = (new ProductImagesService(db()))->bulkAssign($productId, $targetVariantId, $idsRaw);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'No se pudo asignar'), (array)($result['data'] ?? []));
    }

    /** Eliminacion masiva de imagenes (respeta portada por alcance) */
    public function imageBulkDelete(): void
    {
        verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $idsRaw = $_POST['image_ids'] ?? '';

        $result = (new ProductImagesService(db()))->bulkDelete($productId, $idsRaw);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'No se pudo eliminar'), (array)($result['data'] ?? []));
    }

    public function imageSort(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $productId = (int)($_POST['product_id'] ?? 0);
        $order = $_POST['order'] ?? [];
        $variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;

        $result = (new ProductImagesService(db()))->sort($productId, (array)$order, $variantId);
        if (!empty($result['status'])) {
            http_response_code((int)$result['status']);
        }
        $this->jsonOrRedirect((bool)($result['ok'] ?? false), (string)($result['message'] ?? 'DB_ERROR'), (array)($result['data'] ?? []));
    }

    /* ===== helpers ===== */

    
private function wantsJson(): bool
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    private function jsonOrRedirect(bool $ok, string $message, array $data = []): void
    {
        if ($this->wantsJson()) {
            json(['ok'=>$ok, 'message'=>$message] + $data, $ok ? 200 : 400);
            return;
        }
        $next = $_POST['next'] ?? null;
        if (!is_string($next) || strlen($next) > 1024
            || !preg_match('~\A/admin/products(?:/edit|/variants)?(?:\?[a-zA-Z0-9_=&-]*)?\z~D', $next)) {
            $next = '/admin/products';
        }
        flash($ok ? 'ok' : 'error', $message);
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


