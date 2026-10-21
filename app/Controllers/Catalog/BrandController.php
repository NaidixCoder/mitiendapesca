<?php
namespace App\Controllers\Catalog;

use App\Controllers\BaseController;
use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Catalog\ProductRepository;
use App\Services\Catalog\EditorialBlockViewModelService;
use App\Services\Catalog\FilterService;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\FacetService;
use App\Services\Catalog\FilterChipService;
use App\Services\Catalog\FilterAsideViewModelService;
use App\Services\Seo\BrandSeoBlocksService;
use App\Services\Seo\SeoFrontService;
use Throwable;

final class BrandController extends BaseController
{
    public function show(): void
    {
        try {
            $slug = trim((string)($_GET['slug'] ?? ''));
            if ($slug === '') {
                render_page('errors/404');
                return;
            }

            $db = db();

            $brandRepo = new BrandRepository($db);
            $brand = $brandRepo->findBySlug($slug);
            if (!$brand) {
                render_page('errors/404');
                return;
            }

            // SEO blocks (Bullets/Specs/FAQ)
            $blocks = (new BrandSeoBlocksService($db))->getForBrand((int)$brand['id']);
            $brand['seo_bullets'] = $blocks['bullets'] ?? [];
            $brand['seo_specs']   = $blocks['specs'] ?? [];
            $brand['seo_faq']     = $blocks['faq'] ?? [];

            // Productos filtrados por marca (respeta page/perPage/orden)
            $filterSvc = new FilterService();
            $filters   = $filterSvc->fromRequest($_GET ?? []);
            $filters['brand_slug'] = $slug;
	            // helper para construir URLs/paginación en el template
	            $filters['slug'] = $slug;

            $productRepo = new ProductRepository($db);
            $productSvc  = new ProductService($productRepo);
            $pager       = $productSvc->paginate($filters);
            $pager['items'] = $productSvc->buildCardModels((array)($pager['items'] ?? []));
            $sortOptions = $productSvc->sortOptions();
            $priceBounds = $productSvc->priceBounds($filters);
	            // Facetas de categorías para sidebar (mismo componente que /productos)
	            $catRepo  = new CategoryRepository($db);
	            $facetSvc = new FacetService($catRepo);
	            $facets   = $facetSvc->categoriesWithCounts($filters);
            $products    = $pager['items'] ?? [];

            $labelMap = $facetSvc->labelMap()['by_slug'] ?? [];
            $tagGroupLabelMap = [];
            $tagLabelMap = [];
            foreach (($facets['tags'] ?? []) as $tagFacet) {
                $groupSlug = trim((string)($tagFacet['slug'] ?? ''));
                $groupName = trim((string)($tagFacet['name'] ?? ''));
                if ($groupSlug !== '' && $groupName !== '') {
                    $tagGroupLabelMap[$groupSlug] = $groupName;
                }
                foreach (($tagFacet['options'] ?? []) as $option) {
                    $tagSlug = trim((string)($option['slug'] ?? ''));
                    $tagName = trim((string)($option['name'] ?? ''));
                    if ($groupSlug !== '' && $tagSlug !== '' && $tagName !== '') {
                        $tagLabelMap[$groupSlug . ':' . $tagSlug] = $tagName;
                    }
                }
            }
            $qsBuilder = function(array $add = [], array $drop = []) {
                $q = array_merge($_GET ?? [], $add);
                foreach ($drop as $k) {
                    unset($q[$k]);
                }
                $q = array_filter($q, fn($v) => $v !== null && $v !== '' && $v !== []);
                return $q ? ('?' . http_build_query($q)) : '';
            };
            $labelResolver = function(string $slug) use ($labelMap): string {
                return isset($labelMap[$slug]) ? (string)$labelMap[$slug] : $slug;
            };
            $chipLabels = [
                'by_slug' => $labelMap,
                'tag_group_labels' => $tagGroupLabelMap,
                'tag_labels' => $tagLabelMap,
            ];
            $chipSvc = new FilterChipService('/marca', $qsBuilder, $chipLabels, $labelResolver);
            $chipsData = $chipSvc->make($filters);
            $filterAside = (new FilterAsideViewModelService())->build(
                $filters,
                $facets,
                $chipsData['chips'],
                $chipsData['clearAll'],
                '/marca'
            );

            // Breadcrumbs + SEO
            $trail = [
                ['name' => 'Inicio', 'url' => url('/')],
                ['name' => 'Marcas', 'url' => url('/marcas')],
                ['name' => (string)($brand['name'] ?? 'Marca'), 'url' => url('/marca') . '?slug=' . urlencode($slug)],
            ];
            $seo = (new SeoFrontService($db))->buildBrand($brand, $trail);
            $editorial = (new EditorialBlockViewModelService())->build(
                'Sobre ' . (string)($brand['name'] ?? 'esta marca'),
                $brand,
                $blocks
            );

            // Robots (SEO pro): la landing /marca es indexable solo en su versión "limpia".
            // Si hay paginación/filtros/presentación, evitamos duplicados.
            $pageNow  = (int)($pager['page'] ?? $filters['page'] ?? 1);
            $perNow   = (int)($pager['per_page'] ?? $filters['perPage'] ?? 12);
            $ordenNow = (string)($filters['orden'] ?? 'relevancia');
            $hasQuery = trim((string)($filters['q'] ?? '')) !== '';
            $hasRange = trim((string)($filters['min'] ?? '')) !== '' || trim((string)($filters['max'] ?? '')) !== '';
            $hasTags = !empty($filters['tags'] ?? []);
            $isClean  = ($pageNow === 1) && !$hasQuery && !$hasRange && !$hasTags && ($ordenNow === 'relevancia') && ($perNow === 12);
            if (!$isClean) {
                $seo['robots'] = 'noindex,follow';
            }

            render_page('catalogo/marca', [
                'pageId'   => 'catalog',
                'brand'    => $brand,
                'blocks'   => $blocks,
                'editorial' => $editorial,
                'facets'   => $facets,
                'products' => $products,
                'pager'    => $pager,
                'filters'  => $filters,
                'sortOptions' => $sortOptions,
                'currentSortLabel' => $productSvc->currentSortLabel((string)($filters['orden'] ?? 'relevancia')),
                'priceBounds' => $priceBounds,
                'whatsUrl' => trim((string)env('CONTACT_WHATSAPP_URL', '')),
                'chips'    => $chipsData['chips'],
                'clearAll' => $chipsData['clearAll'],
                'filterAside' => $filterAside,
                'trail'    => $trail,
                'seo'      => $seo,
            ]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('BrandController@show: ' . $e->getMessage());
            }
            http_response_code(500);
            render_page('errors/500');
        }
    }
}

