<?php
namespace App\Controllers\Catalog;

use App\Controllers\BaseController;
use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Catalog\ProductRepository;
use App\Services\Catalog\EditorialBlockViewModelService;
use App\Services\Catalog\FacetService;
use App\Services\Catalog\FilterChipService;
use App\Services\Catalog\FilterAsideViewModelService;
use App\Services\Catalog\FilterService;
use App\Services\Catalog\ProductService;
use App\Services\Seo\CategorySeoBlocksService;
use App\Services\Seo\SeoFrontService;
use Throwable;

final class CategoryController extends BaseController
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

            $categoryRepo = new CategoryRepository($db);
            $category = $categoryRepo->findActiveBySlug($slug);
            if (!$category) {
                render_page('errors/404');
                return;
            }

            $blocks = (new CategorySeoBlocksService($db))->getForCategory((int)($category['id'] ?? 0));
            $category['seo_bullets'] = $blocks['bullets'] ?? [];
            $category['seo_specs'] = $blocks['specs'] ?? [];
            $category['seo_faq'] = $blocks['faq'] ?? [];

            $filterSvc = new FilterService();
            $filters = $filterSvc->fromRequest($_GET ?? []);
            $isChildCategory = (int)($category['parent_id'] ?? 0) > 0;
            if ($isChildCategory) {
                $filters['category_slug'] = $slug;
            } else {
                $filters['categoria'] = $slug;
                unset($filters['category_slug']);
            }
            $filters['slug'] = $slug;

            $productRepo = new ProductRepository($db);
            $productSvc = new ProductService($productRepo);
            $pager = $productSvc->paginate($filters);
            $pager['items'] = $productSvc->buildCardModels((array)($pager['items'] ?? []));
            $sortOptions = $productSvc->sortOptions();
            $priceBounds = $productSvc->priceBounds($filters);

            if (!empty($pager['items']) && is_array($pager['items'][0])) {
                $first = $pager['items'][0];
                $hasId = array_key_exists('id', $first) && (int)$first['id'] > 0;
                $hasName = array_key_exists('name', $first) && trim((string)$first['name']) !== '';
                if (!$hasId || !$hasName) {
                    $keys = implode(',', array_keys($first));
                    log_error('Catalog guardrail: productos sin claves esperadas en CategoryController (keys=' . $keys . ')');
                }
            }

            $facetSvc = new FacetService($categoryRepo);
            $facets = $facetSvc->categoriesWithCounts($filters);
            $products = $pager['items'] ?? [];

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
            $qsBuilder = function (array $add = [], array $drop = []) {
                $q = array_merge($_GET ?? [], $add);
                foreach ($drop as $key) {
                    unset($q[$key]);
                }
                $q = array_filter($q, fn ($value) => $value !== null && $value !== '' && $value !== []);
                return $q ? ('?' . http_build_query($q)) : '';
            };
            $labelResolver = function (string $value) use ($labelMap): string {
                return isset($labelMap[$value]) ? (string)$labelMap[$value] : $value;
            };
            $chipLabels = [
                'by_slug' => $labelMap,
                'tag_group_labels' => $tagGroupLabelMap,
                'tag_labels' => $tagLabelMap,
            ];
            $chipSvc = new FilterChipService('/categoria', $qsBuilder, $chipLabels, $labelResolver);
            $chipsData = $chipSvc->make($filters);
            $filterAside = (new FilterAsideViewModelService())->build(
                $filters,
                $facets,
                $chipsData['chips'],
                $chipsData['clearAll'],
                '/categoria'
            );

            $trail = [
                ['name' => 'Inicio', 'url' => url('/')],
                ['name' => 'Categorías', 'url' => url('/categorias')],
                ['name' => (string)($category['name'] ?? 'Categoría'), 'url' => url('/categoria') . '?slug=' . urlencode($slug)],
            ];
            $seo = (new SeoFrontService($db))->buildCategory($category, $trail);
            $editorial = (new EditorialBlockViewModelService())->build(
                'Sobre ' . (string)($category['name'] ?? 'esta categoria'),
                $category,
                $blocks
            );

            $pageNow = (int)($pager['page'] ?? $filters['page'] ?? 1);
            $perNow = (int)($pager['per_page'] ?? $filters['perPage'] ?? 12);
            $ordenNow = (string)($filters['orden'] ?? 'relevancia');
            $hasQuery = trim((string)($filters['q'] ?? '')) !== '';
            $hasRange = trim((string)($filters['min'] ?? '')) !== '' || trim((string)($filters['max'] ?? '')) !== '';
            $hasTags = !empty($filters['tags'] ?? []);
            $isClean = ($pageNow === 1) && !$hasQuery && !$hasRange && !$hasTags && ($ordenNow === 'relevancia') && ($perNow === 12);
            if (!$isClean) {
                $seo['robots'] = 'noindex,follow';
            }

            render_page('catalogo/categoria', [
                'pageId' => 'catalog',
                'category' => $category,
                'blocks' => $blocks,
                'editorial' => $editorial,
                'facets' => $facets,
                'products' => $products,
                'pager' => $pager,
                'filters' => $filters,
                'sortOptions' => $sortOptions,
                'currentSortLabel' => $productSvc->currentSortLabel((string)($filters['orden'] ?? 'relevancia')),
                'priceBounds' => $priceBounds,
                'whatsUrl' => trim((string)env('CONTACT_WHATSAPP_URL', '')),
                'chips' => $chipsData['chips'],
                'clearAll' => $chipsData['clearAll'],
                'filterAside' => $filterAside,
                'trail' => $trail,
                'seo' => $seo,
            ]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('CategoryController@show: ' . $e->getMessage());
            }
            http_response_code(500);
            render_page('errors/500');
        }
    }
}
