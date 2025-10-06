<?php
namespace App\Controllers\Catalog;

use App\Controllers\BaseController;
use App\Repositories\ProductRepository;
use Throwable;

class ProductController extends BaseController
{
    public function show(): void
    {
        $slug = (string)($_GET['slug'] ?? '');
        if ($slug === '') { http_response_code(400); echo 'Falta slug'; return; }

        $product = null; $related = []; $trail = [];

        try {
            $repo = new ProductRepository(db());
            $product = $repo->findBySlug($slug);
            if ($product) {
                $related = $repo->related($product, 8);
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Catalog\\ProductController@show: '.$e->getMessage());
        }

        if (!$product) { http_response_code(404); render_page('errors/404'); return; }

        $pageTitle = ($product['title'] ?? 'Producto').' | '.(env('APP_NAME','Mi Pesca'));
        $pageDesc  = $product['short'] ?? ($product['description'] ?? '');
        $ogImage   = $product['thumb'] ?? asset('img/home/featured-1.jpg');

        // migas de pan básicas
        $trail = [
            ['title' => 'Inicio',    'url' => url('/')],
            ['title' => 'Productos', 'url' => url('/productos')],
            ['title' => $product['title'] ?? 'Producto', 'url' => url('/producto?slug='.urlencode($slug))],
        ];

        $extraHead = ''
          . '<meta property="og:image" content="'.e($ogImage).'">'
          . '<meta name="twitter:image" content="'.e($ogImage).'">';

        render_page('catalogo/producto', compact('product','pageTitle','pageDesc','extraHead','related','trail'));
    }
}
