<?php
declare(strict_types=1);

// Buffer para permitir headers en endpoints (XML/JSON) sin 'headers already sent'
ob_start();

/**
 * Front Controller
 * - Normaliza entorno y paths
 * - Carga autoload y bootstraps
 * - Resuelve rutas contra config/routes.php
 * - Despacha Controller@method o Vista
 * - Maneja 404/500
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
function base_path(string $p = ''): string {
    return BASE_PATH . ($p ? DIRECTORY_SEPARATOR . ltrim($p, '/\\') : '');
}

/* Autoload (Composer) */
$composer = base_path('vendor/autoload.php');
if (is_file($composer)) { require_once $composer; }

/* Bootstraps */
foreach (glob(base_path('config/boot/*.php')) as $bootFile) {
    require_once $bootFile;
}

/* Helpers de vistas (obligatorio) */
$helpersFile = base_path('app/Views/helpers.php');
if (!is_file($helpersFile)) { throw new RuntimeException('Falta app/Views/helpers.php'); }
require_once $helpersFile;

/* Método HTTP con override via _method */
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'POST' && !empty($_POST['_method'])) {
    $ov = strtoupper((string)$_POST['_method']);
    if (in_array($ov, ['PUT','PATCH','DELETE'], true)) $method = $ov;
}

// HEAD nunca entrega cuerpo, incluso si render_page genera HTML.
if ($method === 'HEAD') ob_start(static fn(string $body): string => '');

/* Normalizar path solicitado y recortar subcarpeta */
$rawUri  = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($rawUri, PHP_URL_PATH) ?: '/';
$uriQuery = (string)(parse_url($rawUri, PHP_URL_QUERY) ?? '');
$uriPath = preg_replace('#//+#', '/', str_replace('\\','/',$uriPath));
$scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir && $scriptDir !== '/') {
    $uriPath = preg_replace('#^'.preg_quote($scriptDir,'#').'#','', $uriPath);
    if ($uriPath === '') $uriPath = '/';
}
if ($uriPath !== '/') $uriPath = rtrim($uriPath, '/');

/* Rutas dinámicas mínimas (sin romper el router por tabla) */
// /promo/{slug} (links cortos de campañas internas)
if (in_array($method, ['GET','HEAD'], true)) {
    if (preg_match('#^/promo/([A-Za-z0-9-_.]+)$#', $uriPath, $m)) {
        $_GET['slug'] = $m[1];
        $uriPath = '/promo';
    }
}



/* SEO: Limpieza de tracking validada (GET/HEAD).
 * La atribución first-touch conserva prioridad GET, pero solo valores
 * escalares seguros. No hacer jamás 301 previo a un destino 404.
 */
if (in_array($method, ['GET','HEAD'], true) && $uriQuery !== '') {
    // parse_str() pierde claves duplicadas y transforma brackets en arrays:
    // inspeccionar el query ORIGINAL antes de asignarle significado.
    $validQuery = strlen($uriQuery) <= 2048;
    $seenQueryKeys = [];
    $hasStructuredFilters = false;
    foreach (explode('&', $uriQuery) as $part) {
        if (!$validQuery) break;
        if ($part === '' || !str_contains($part, '=')) {
            $validQuery = false;
            break;
        }
        [$rawKey,$rawValue] = explode('=', $part, 2);
        $decodedKey = urldecode($rawKey);
        $decodedValue = urldecode($rawValue);
        // Filtros públicos legítimos: marca[]=..., tag[grupo][]=...
        // No canonicalizar sus arrays, para no romper el listado existente.
        $structured = preg_match('/^(?:marca\\[[0-9]*\\]|tag\\[[A-Za-z0-9_-]{1,80}\\]\\[[0-9]*\\])$/', $decodedKey) === 1
            && in_array($uriPath, ['/productos','/categoria','/marca'], true);
        if ($structured) $hasStructuredFilters = true;
        $repeatable = $structured && str_ends_with($decodedKey, '[]');
        if ($rawKey === '' || preg_match('/%(?![0-9a-f]{2})/i', $part) ||
            (!$structured && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $decodedKey)) ||
            (isset($seenQueryKeys[$decodedKey]) && !$repeatable) ||
            strlen($decodedValue) > 512 ||
            !preg_match('//u', $decodedValue) ||
            preg_match('/[\x00-\x1f\x7f]/', $decodedValue) ||
            str_contains($decodedValue, '%')) {
            $validQuery = false;
            break;
        }
        $seenQueryKeys[$decodedKey] = true;
    }

    if ($validQuery) {
        parse_str($uriQuery, $q);
        if (is_array($q) && $q) {
            $orig = $q;

            // First-touch SOLO GET: HEAD de robots no atribuye campañas.
            if ($method === 'GET' && session_status() === PHP_SESSION_ACTIVE) {
                $utmSource = $q['utm_source'] ?? null;
                if (is_string($utmSource) && trim($utmSource) !== '' &&
                    empty($_SESSION['analytics_utm'])) {
                    $_SESSION['analytics_utm'] = [
                        'utm_source' => trim($utmSource),
                        'utm_medium' => is_string($q['utm_medium'] ?? null) ?
                            trim($q['utm_medium']) : '',
                        'utm_campaign' => is_string($q['utm_campaign'] ?? null) ?
                            trim($q['utm_campaign']) : '',
                        'captured_at' => time(),
                    ];
                }
            }

            $dropExact = ['fbclid','gclid','mc_cid','mc_eid','ref','ref_src','igshid','mkt_tok'];
            foreach ($q as $k => $v) {
                if (str_starts_with((string)$k, 'utm_') || in_array((string)$k, $dropExact, true)) {
                    unset($q[$k]);
                } elseif (is_string($v) && trim($v) === '') {
                    unset($q[$k]);
                }
            }
            if (isset($q['per_page']) && !isset($q['perPage'])) {
                $q['perPage'] = $q['per_page'];
            }
            unset($q['per_page']);
            if (isset($q['page']) && is_scalar($q['page']) &&
                ctype_digit((string)$q['page']) && (int)$q['page'] <= 1) unset($q['page']);

            if ($q !== $orig && !$hasStructuredFilters) {
                $qs = http_build_query($q, '', '&', PHP_QUERY_RFC3986);
                $cleaned = $uriPath . ($qs === '' ? '' : '?' . $qs);
                try {
                    $resolver = new \App\Services\Seo\PublicRedirectTargetResolver(db());
                    $resolved = $resolver->resolveCleanedRequest($cleaned);
                    // HEAD solo puede apuntar a recursos cuyo despacho HEAD
                    // está garantizado sin escrituras de otros controladores.
                    $headTargetPath = $resolved === null ? '' :
                        (string)(parse_url($resolved['target'], PHP_URL_PATH) ?: '');
                    $headSafe = in_array($headTargetPath,
                        ['/', '/producto', '/categoria', '/marca', '/categorias', '/marcas',
                         '/terminos', '/privacidad', '/productos'], true);
                    if ($resolved !== null && ($method === 'GET' || $headSafe)) {
                        header('Location: ' . url($resolved['target']), true, (int)$resolved['status']);
                        if ($method === 'GET' && !empty($resolved['historical'])) {
                            try {
                                (new \App\Repositories\Seo\SeoRedirectRepository(db()))
                                    ->registerHit($cleaned);
                            } catch (\Throwable $hitError) {
                                if (function_exists('log_error')) log_error('SEO redirect hit no registrado');
                            }
                        }
                        exit;
                    }
                } catch (\Throwable $e) {
                    if (function_exists('log_error')) log_error('Tracking canonical: destino no verificable');
                }
                // Destino inválido u oculto: nunca Location hacia un 404.
                // La ruta original será despachada como corresponda.
            }
        }
    } elseif (in_array($uriPath,
        ['/','/producto','/categoria','/marca','/productos','/categorias',
         '/marcas','/terminos','/privacidad'], true)) {
        http_response_code(404);
        render_page('errors/404');
        exit;
    }
}

/* SEO: Redirects históricos de GET y HEAD públicos seguros. */
if (in_array($method, ['GET','HEAD'], true)) {
    try {
        $from = $uriPath . ($uriQuery !== '' ? ('?'.$uriQuery) : '');
        $resolver = new \App\Services\Seo\PublicRedirectTargetResolver(db());
        $resolved = $resolver->resolve($from);
        if ($resolved !== null) {
            $destPath = (string)(parse_url($resolved['target'], PHP_URL_PATH) ?: '');
            $headAllowed = in_array($destPath,
                ['/', '/producto', '/categoria', '/marca', '/categorias', '/marcas',
                 '/terminos', '/privacidad', '/productos'], true);
            if ($method === 'GET' || $headAllowed) {
                header('Location: ' . url($resolved['target']), true, $resolved['status']);
                // HEAD no es una visita, no contar un hit comercial/SEO.
                if ($method === 'GET') {
                    try {
                        (new \App\Repositories\Seo\SeoRedirectRepository(db()))
                            ->registerHit($from);
                    } catch (\Throwable $e) {
                        if (function_exists('log_error')) log_error('SEO redirect hit no registrado');
                    }
                }
                exit;
            }
        }
    } catch (\Throwable $e) {
        // No bloqueo del dispatcher ni redirect si falla la resolución.
    }
}

/* Cargar rutas */
$routesFile = base_path('config/routes.php');
$routes = is_file($routesFile) ? (require $routesFile) : [];
if (!is_array($routes)) $routes = [];

// HEAD se despacha como GET SOLO para rutas públicas SEO de la whitelist.
 // Los registros ProductController::show y ProductsController::index son GET-only.
$headReadOnlyPaths = ['/', '/producto', '/categoria', '/marca', '/categorias', '/marcas',
                      '/terminos', '/privacidad', '/productos'];
$headReadOnly = $method === 'HEAD' && in_array($uriPath, $headReadOnlyPaths, true);
$key = ($headReadOnly ? 'GET' : $method) . ' ' . $uriPath;
$handler = $routes[$key] ?? null;

// Nota: validaciones específicas (ej. /registro) viven en sus Controllers.
// Esto evita romper el flujo JSON/AJAX (progressive enhancement).

/* 404 si no hay handler */
if ($handler === null) {
    http_response_code(404);
    render_page('errors/404', ['path'=>$uriPath, 'method'=>$method]);
    exit;
}

/* Guards por prefijo de ruta (evita duplicar lógica en cada controller) */
try {
    if (function_exists('admin_required') && ($uriPath === '/admin' || strpos($uriPath, '/admin/') === 0)) {
        admin_required();
    } elseif (function_exists('auth_required') && ($uriPath === '/cuenta' || strpos($uriPath, '/cuenta/') === 0)) {
        // Para cuenta: exige sesión válida (incluye verificación session_version)
        auth_required('/cuenta');
    }
} catch (Throwable $e) {
    // Si el guard corta con redirect/exit, no llegamos acá.
    // Si lanza excepción, delegamos al handler 500.
    throw $e;
}


/* Analíticas: page_view (GET/HEAD, no-AJAX) */
try {
    if ($method === 'GET') {
        $xhr = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $wantsJson = (stripos($accept, 'application/json') !== false) || (stripos($accept, 'text/event-stream') !== false);
        if (!$xhr && !$wantsJson) {
            $pathForAnalytics = $uriPath . ($uriQuery !== '' ? ('?' . $uriQuery) : '');
            if (class_exists(\App\Services\Analytics\AnalyticsService::class)) {
                (new \App\Services\Analytics\AnalyticsService(db()))->trackPageView($pathForAnalytics, (string)($_SERVER['HTTP_REFERER'] ?? ''));
            }
        }
    }
} catch (\Throwable $e) {
    // No bloqueante
}

/* Despacho */
try {
    if (!is_string($handler)) {
        throw new RuntimeException('Handler inválido para la ruta: ' . $key);
    }
    $isControllerLike = (strpos($handler,'@') !== false) || (strpos($handler,'\\') !== false);
    if ($isControllerLike) {
        [$class, $action] = array_pad(explode('@', $handler, 2), 2, 'index');
        $class = ltrim($class, '\\');
        if (strpos($class, 'App\\') !== 0) $class = 'App\\Controllers\\' . $class;
        if (!class_exists($class)) throw new RuntimeException("Controller no encontrado: {$class}");
        $ctrl = new $class();
        if (!method_exists($ctrl, $action)) throw new RuntimeException("Método no encontrado: {$class}@{$action}");
        $ctrl->{$action}();
    } else {
        render_page($handler);
    }
} catch (Throwable $e) {
    if (function_exists('log_error')) log_error('HTTP 500: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    http_response_code(500);
    $debug = function_exists('env') ? (env('APP_DEBUG','false')==='true') : false;
    if ($debug) {
        echo '<pre style="padding:16px;margin:16px;background:#111;color:#eee;border-radius:8px;white-space:pre-wrap">';
        echo "Exception: ".$e->getMessage()."\n\n".$e->getTraceAsString();
        echo '</pre>';
    }
    render_page('errors/500', ['exception'=>$e]);
}
