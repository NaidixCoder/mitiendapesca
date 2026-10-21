<?php
namespace App\Controllers\Admin\Concerns;

use App\Services\Admin\Variants\VariantReadService;
use PDO;
use Throwable;

trait VariantReadActions
{
    public function index(): void
    {
        $productId = (int)($_GET['id'] ?? $_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->json(['ok' => false, 'message' => 'Producto invalido.'], 400);
            return;
        }

        try {
            $data = (new VariantReadService(db()))->getData($productId);
            $this->json(['ok' => true, 'data' => $data]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Admin\\ProductVariantsController@index: ' . $e->getMessage());
            }
            $this->json(['ok' => false, 'message' => 'No se pudieron cargar las variantes.'], 500);
        }
    }

    public function listPage(): void
    {
        $productId = (int)($_GET['id'] ?? $_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->redirect('/admin/products');
            return;
        }

        $db = db();
        $product = null;
        try {
            $st = $db->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
            $st->execute([$productId]);
            $product = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Admin\\ProductVariantsController@listPage product: ' . $e->getMessage());
            }
        }

        if (!is_array($product)) {
            $this->redirect('/admin/products');
            return;
        }

        try {
            $variantsCtx = (new VariantReadService($db))->getData($productId);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Admin\\ProductVariantsController@listPage ctx: ' . $e->getMessage());
            }
            $variantsCtx = [];
        }

        $this->view('admin/products/variants', [
            'productId' => $productId,
            'product' => $product,
            'variantsCtx' => $variantsCtx,
        ]);
    }

    public function fragment(): void
    {
        $this->renderFragment('sale_fragment.php');
    }

    private function renderFragment(string $filename): void
    {
        $productId = (int)($_GET['id'] ?? $_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            http_response_code(400);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<div class="text-sm text-red-700">Producto invalido.</div>';
            return;
        }

        try {
            $variantsCtx = (new VariantReadService(db()))->getData($productId);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Admin\\ProductVariantsController@renderFragment: ' . $e->getMessage());
            }
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<div class="text-sm text-red-700">No se pudo cargar el fragmento.</div>';
            return;
        }

        $file = base_path('app/Views/partials/admin/products/variants/' . $filename);
        header('Content-Type: text/html; charset=UTF-8');
        if (!is_file($file)) {
            http_response_code(404);
            echo '<div class="text-sm text-red-700">Fragmento no encontrado.</div>';
            return;
        }

        include $file;
    }
}
