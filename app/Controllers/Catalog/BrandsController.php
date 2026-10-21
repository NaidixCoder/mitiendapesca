<?php
namespace App\Controllers\Catalog;

use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Catalog\RecommendedProductsService;

final class BrandsController
{
    public function index(): void
    {
        $db = \db();
        $repo = new BrandRepository($db);
        $brands = $repo->listAllWithProductCount();
        $settings = new AppSettingsRepository($db);
        $showEmptyBrands = ($settings->get('catalog_show_empty_brands', '0') === '1');

        if (!$showEmptyBrands) {
            $brands = array_values(array_filter(
                $brands,
                static fn(array $brand): bool => (int)($brand['product_count'] ?? 0) > 0
            ));
        }

        $seo = [
            'title' => 'Marcas | Mi Pesca & Aventura',
            'description' => 'Explorá marcas de pesca y filtrá productos por tu preferida.',
            'canonical' => url('/marcas'),
            'robots' => 'index,follow',
        ];

        render_page('catalogo/marcas', [
            'seo' => $seo,
            'brands' => $brands,
            'pageId' => 'directory-brands',
            'related' => (new RecommendedProductsService($db))->forGlobal(5, 6),
        ]);
    }
}

