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
            // 0) LEGACY redirect (solo cuando la landing pública de destino
            // existe y está habilitada; no generar 301 -> 404).
            $get = $_GET ?? [];
            $legacyCatRaw = $get['categoria'] ?? null;
            $legacyBrandRaw = $get['marca'] ?? null;
            if ($legacyCatRaw !== null || $legacyBrandRaw !== null) {
                // Rechazar arrays y claves manipuladas, sin normalizarlas a string.
                // marca[]=slug es un filtro múltiple del catálogo, NO un
                // slug legacy para 301. Conservarlo sin reinterpretarlo.
                $brandFilter = is_array($legacyBrandRaw);
                if ($brandFilter) {
                    if (!array_is_list($legacyBrandRaw)) {
                        http_response_code(404);
                        render_page('errors/404');
                        return;
                    }
                    foreach ($legacyBrandRaw as $filterBrand) {
                        if (!is_string($filterBrand) ||
                            !preg_match('/^[\\pL\\pN][\\pL\\pN._-]{0,159}$/u', $filterBrand)) {
                            http_response_code(404);
                            render_page('errors/404');
                            return;
                        }
                    }
                }
                if ($legacyCatRaw !== null && !is_string($legacyCatRaw)) {
                    http_response_code(404);
                    render_page('errors/404');
                    return;
                }
                $legacyCat = $brandFilter ? '' : trim((string)($legacyCatRaw ?? ''));
                $legacyBrand = $brandFilter ? '' : trim((string)($legacyBrandRaw ?? ''));
                if ($legacyCat !== '' || $legacyBrand !== '') {
                    $isCat = $legacyCat !== '';
                    $params = ['slug' => $isCat ? $legacyCat : $legacyBrand];
                    if ($isCat && isset($get['sub'])) $params['sub'] = $get['sub'];

                    // Solo filtros de catálogo conocidos; no transportar
                    // arbitrariamente URLs ni parámetros de redirección.
                    foreach (['q','min','max','orden','page','perPage','per_page'] as $k) {
                        if (isset($get[$k]) && $get[$k] !== '') $params[$k] = $get[$k];
                    }
                    $to = ($isCat ? '/categoria' : '/marca') .
                        '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
                    $resolver = new \App\Services\Seo\PublicRedirectTargetResolver(db());
                    $safeTarget = $resolver->resolveDirect($to);
                    if ($safeTarget === null) {
                        http_response_code(404);
                        render_page('errors/404');
                        return;
                    }
                    header('Location: ' . \url($safeTarget), true, 301);
                    exit;
                }
            }

            // 1) Filtros normalizados
            $filterSvc = new FilterService();
            $filters   = $filterSvc->fromRequest($_GET ?? []);

            // B1 se impone siempre desde ProductRepository en SQL para todos los
            // consumidores públicos; el filtro legacy se conserva solo como contexto.
            $filters['enforce_active_category'] = (new \App\Services\Catalog\ProductPublicVisibilityService(db()))
                ->hidesHiddenCategoryProducts() ? '1' : '0';

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

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' &&
                $uidTrack > 0 && $qTrack !== '' && mb_strlen($qTrack, 'UTF-8') >= 2 && $canLog) {
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
