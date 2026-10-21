<?php
namespace App\Controllers\Catalog;

use App\Controllers\BaseController;
use App\Repositories\Catalog\ProductRepository;
use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Catalog\FilterService;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\FacetService;
use App\Services\Catalog\FilterChipService;
use App\Services\Catalog\FilterAsideViewModelService;
use App\Services\Catalog\SeoService;
use App\Services\Audit\Audit;
use Throwable;

final class ProductsController extends BaseController
{
    public function suggest(): void
    {
        try {
            $q = trim((string)($_GET['q'] ?? ''));
            $repo = new ProductRepository(db());
            $items = $repo->searchPreview($q, 7);

            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'ok' => true,
                'items' => $items,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductsController@suggest: ' . $e->getMessage());
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'items' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    public function suggestFragment(): void
    {
        try {
            $q = trim((string)($_GET['q'] ?? ''));
            $repo = new ProductRepository(db());
            $items = $repo->searchPreview($q, 7);

            header('Content-Type: text/html; charset=UTF-8');
            $resultsQuery = $q;
            $resultsItems = $items;
            ob_start();
            require BASE_PATH . '/app/Views/partials/header/_mobile_search_results.php';
            echo (string)ob_get_clean();
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductsController@suggestFragment: ' . $e->getMessage());
            }
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo '';
        }
    }

    public function index(): void
    {
        try {
            // 0) LEGACY redirect (compatibilidad): /productos?categoria=... o /productos?marca=...
            // Decisión UX/SEO: las landings correctas son /categoria?slug=... y /marca?slug=...
            $get = $_GET ?? [];
            $legacyCat   = trim((string)($get['categoria'] ?? ''));
            $legacyBrand = is_array($get['marca'] ?? null) ? '' : trim((string)($get['marca'] ?? ''));
            if ($legacyCat !== '' || $legacyBrand !== '') {
                $isCat = ($legacyCat !== '');
                $base  = $isCat ? \url('/categoria') : \url('/marca');
                $params = [
                    'slug' => $isCat ? $legacyCat : $legacyBrand,
                ];
                if ($isCat && !empty($get['sub'])) {
                    $params['sub'] = trim((string)$get['sub']);
                }
                // Preservar filtros útiles (sin mantener categoria/marca legacy)
                foreach (['q','min','max','orden','page','perPage','per_page'] as $k) {
                    if (isset($get[$k]) && $get[$k] !== '') {
                        $params[$k] = $get[$k];
                    }
                }
                $qs = http_build_query($params);
                $to = $base . ($qs ? ('?' . $qs) : '');
                header('Location: ' . $to, true, 301);
                exit;
            }

            // 1) Filtros normalizados
            $filterSvc = new FilterService();
            $filters   = $filterSvc->fromRequest($_GET ?? []);

            // Ajuste (modo híbrido): si está ON, ocultar en el sitio los productos cuya categoría esté inactiva.
            $settingsRepo = new AppSettingsRepository(db());
            $hideInactiveCat = ($settingsRepo->get('catalog_hide_inactive_category_products', '0') === '1');
            $filters['enforce_active_category'] = $hideInactiveCat ? '1' : '0';

            // 2) Paginación
            $productRepo = new ProductRepository(db());
            $productSvc  = new ProductService($productRepo);
            $pager       = $productSvc->paginate($filters);
            $pager['items'] = $productSvc->buildCardModels((array)($pager['items'] ?? []));
            $sortOptions = $productSvc->sortOptions();
            $priceBounds = $productSvc->priceBounds($filters);

            // 2b) Tracking de búsqueda (solo logueados): útil para recomendaciones/analítica.
            // - No rompe UX si falla (Audit::log es best-effort).
            $qTrack = (string)($filters['q'] ?? '');
            $qTrack = trim($qTrack);
            $uidTrack = (int)($_SESSION['uid'] ?? 0);
            // Throttle simple para no spamear: mismo término dentro de 60s no se registra.
            $lastQ  = (string)($_SESSION['_search_last_q'] ?? '');
            $lastTs = (int)($_SESSION['_search_last_ts'] ?? 0);
            $nowTs  = time();

            $canLog = true;
            if ($lastQ !== '' && $lastQ === $qTrack && ($nowTs - $lastTs) < 60) {
                $canLog = false;
            }

            if ($uidTrack > 0 && $qTrack !== '' && mb_strlen($qTrack, 'UTF-8') >= 2 && $canLog) {
                Audit::log('catalog_search', 'user', $uidTrack, [
                    'q'         => $qTrack,
                    'categoria' => (string)($filters['categoria'] ?? ''),
                    'sub'       => (string)($filters['sub'] ?? ''),
                    'min'       => (string)($filters['min'] ?? ''),
                    'max'       => (string)($filters['max'] ?? ''),
                    'orden'     => (string)($filters['orden'] ?? ''),
                    'total'     => (int)($pager['total'] ?? 0),
                    'page'      => (int)($pager['page'] ?? 1),
                    'per_page'  => (int)($pager['per_page'] ?? 0),
                ]);

                $_SESSION['_search_last_q'] = $qTrack;
                $_SESSION['_search_last_ts'] = $nowTs;
            }

            // Guardarrail de diagnostico (app.log): si el listado llega sin
            // claves esperadas para la card final, lo registramos para encontrar el origen rapido.
            if (!empty($pager['items']) && is_array($pager['items'][0])) {
                $first = $pager['items'][0];
                $hasId = array_key_exists('id', $first) && (int)($first['id'] ?? 0) > 0;
                $hasName = array_key_exists('name', $first) && trim((string)($first['name'] ?? '')) !== '';
                $hasHref = array_key_exists('href', $first) && trim((string)($first['href'] ?? '')) !== '';
                if (!$hasId || !$hasName || !$hasHref) {
                    log_error('Catalogo products: items sin claves esperadas. keys='.implode(',', array_map('strval', array_keys($first))));
                }
            }

            // 3) Facetas + mapa de labels
            $catRepo   = new CategoryRepository(db());
            $facetSvc  = new FacetService($catRepo);
            $facets    = $facetSvc->categoriesWithCounts($filters);
            $labelMap  = $facetSvc->labelMap()['by_slug'] ?? [];
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

            // 4) Chips
            $qsBuilder = function(array $add = [], array $drop = []) {
                $q = array_merge($_GET ?? [], $add);
                foreach ($drop as $k) unset($q[$k]);
                $q = array_filter($q, fn($v) => $v !== null && $v !== '' && $v !== []);
                return $q ? ('?'.http_build_query($q)) : '';
            };
            $labelResolver = function(string $slug) use ($labelMap): string {
                return isset($labelMap[$slug]) ? (string)$labelMap[$slug] : $slug;
            };
            $chipLabels = [
                'by_slug' => $labelMap,
                'tag_group_labels' => $tagGroupLabelMap,
                'tag_labels' => $tagLabelMap,
            ];
            $chipSvc   = new FilterChipService('/productos', $qsBuilder, $chipLabels, $labelResolver);
            $chipsData = $chipSvc->make($filters);
            $filterAside = (new FilterAsideViewModelService())->build(
                $filters,
                $facets,
                $chipsData['chips'],
                $chipsData['clearAll'],
                '/productos'
            );

            // 5) Vista
            $filtersForView = [
                'q'         => $filters['q']         ?? '',
                'categoria' => $filters['categoria'] ?? '',
                'sub'       => $filters['sub']       ?? '',
                'marca'     => $filters['marca']     ?? [],
                'tags'      => $filters['tags']      ?? [],
                'min'       => $filters['min']       ?? '',
                'max'       => $filters['max']       ?? '',
                'orden'     => $productSvc->normalizeOrden((string)($filters['orden'] ?? 'relevancia')),
            ];

            
            // 5) SEO dinámico para listados
            $seoSvc = new SeoService();
            $seo    = $seoSvc->buildListSeo($filters, $pager, $labelResolver);
            render_page('catalogo/productos', ['pageId'   => 'catalog',
                'filters'  => $filtersForView,
                'facets'   => $facets,
                'products' => $pager['items'],
                'total'    => $pager['total'],
                'page'     => $pager['page'],
                'perPage'  => $pager['per_page'],
                'sortOptions' => $sortOptions,
                'currentSortLabel' => $productSvc->currentSortLabel((string)($filters['orden'] ?? 'relevancia')),
                'priceBounds' => $priceBounds,
                'whatsUrl' => trim((string)env('CONTACT_WHATSAPP_URL', '')),
                'chips'    => $chipsData['chips'],
                'clearAll' => $chipsData['clearAll'],
                'filterAside' => $filterAside,
                'seo'      => $seo]);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductsController@index: '.$e->getMessage());
            }
            http_response_code(500);
            render_page('errors/500');
        }
    }
}
