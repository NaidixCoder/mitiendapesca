<?php
namespace App\Controllers\Catalog;

use App\Controllers\BaseController;
use App\Queries\ProductQuery;
use Throwable;

class ProductsController extends BaseController
{
    public function index(): void
    {
        $filters = [
            'q'         => $_GET['q']         ?? '',
            'categoria' => $_GET['categoria'] ?? '',
            'marca'     => $_GET['marca']     ?? '',
            'sort'      => $_GET['sort']      ?? 'created_at',
            'order'     => $_GET['order']     ?? 'desc',
            'page'      => max(1, (int)($_GET['page'] ?? 1)),
            'per'       => 24,
        ];

        $items = [];
        $meta  = ['total'=>0,'page'=>$filters['page'],'per'=>$filters['per']];

        try {
            $query = new ProductQuery(db());
            $res   = $query->search($filters);
            $items = $res['items'] ?? [];
            $meta  = $res['meta']  ?? $meta;
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Catalog\\ProductsController@index: '.$e->getMessage());
        }

        // Compat: algunas vistas esperan $productos
        $productos = $items;

        // 👇 Vista correcta en TU estructura
        render_page('catalogo/productos', [
            'pageId'    => 'catalog',
            'items'     => $items,
            'productos' => $productos,
            'meta'      => $meta,
            'filters'   => $filters,
            'pageTitle' => 'Catálogo',
        ]);
    }
}
