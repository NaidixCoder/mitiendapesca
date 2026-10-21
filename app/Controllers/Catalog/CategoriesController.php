<?php
namespace App\Controllers\Catalog;

use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Catalog\FacetService;
use App\Services\Catalog\RecommendedProductsService;

final class CategoriesController
{
    public function index(): void
    {
        $db = \db();

        $categoryRepo = new CategoryRepository($db);
        $facetSvc = new FacetService($categoryRepo);
        $facets = $facetSvc->categoriesWithCounts([]);
        $settings = new AppSettingsRepository($db);
        $showEmptyCategories = ($settings->get('catalog_show_empty_categories', '0') === '1');

        if (!$showEmptyCategories) {
            $facets['categories'] = array_values(array_filter(
                (array)($facets['categories'] ?? []),
                static fn(array $category): bool => (int)($category['count'] ?? 0) > 0
            ));
        }

        $seo = [
            'title' => 'Categorías | Mi Pesca & Aventura',
            'description' => 'Explorá todas las categorías de pesca y encontrá tu próximo equipo.',
            'canonical' => url('/categorias'),
            'robots' => 'index,follow',
        ];

        render_page('catalogo/categorias', [
            'seo' => $seo,
            'facets' => $facets,
            'pageId' => 'directory-categories',
            'related' => (new RecommendedProductsService($db))->forGlobal(5, 6),
        ]);
    }
}
