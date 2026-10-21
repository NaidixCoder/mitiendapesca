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



/* SEO: Limpieza de parámetros de tracking (canonicalización) */
if (in_array($method, ['GET','HEAD'], true) && $uriQuery !== '') {
    parse_str($uriQuery, $q);
    if (is_array($q) && $q) {
        $orig = $q;

        // Analíticas: capturar UTMs (first-touch por sesión) ANTES de canonicalizar.
        // Esto permite 301 sin perder la campaña en reportes.
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $utmSource = trim((string)($q['utm_source'] ?? ''));
                if ($utmSource !== '' && empty($_SESSION['analytics_utm'])) {
                    $_SESSION['analytics_utm'] = [
                        'utm_source' => $utmSource,
                        'utm_medium' => trim((string)($q['utm_medium'] ?? '')),
                        'utm_campaign' => trim((string)($q['utm_campaign'] ?? '')),
                        'captured_at' => time(),
                    ];
                }
            }
        } catch (Throwable $e) {
            // No bloqueante
        }

        // Quitar tracking / ruido
        $dropExact = ['fbclid','gclid','mc_cid','mc_eid','ref','ref_src','igshid','mkt_tok'];
        foreach ($q as $k => $v) {
            $ks = (string)$k;
            if (str_starts_with($ks, 'utm_') || in_array($ks, $dropExact, true)) {
                unset($q[$k]);
                continue;
            }
            // normalizar empty
            if (is_string($v) && trim($v) === '') unset($q[$k]);
        }

        // Normalizar per_page -> perPage (si viene de legacy)
        if (isset($q['per_page']) && !isset($q['perPage'])) {
            $q['perPage'] = $q['per_page'];
        }
        unset($q['per_page']);

        // page=1 no aporta
        if (isset($q['page']) && (int)$q['page'] <= 1) unset($q['page']);

        // Si cambió, redirigimos 301 al mismo path con query limpia
        if ($q !== $orig) {
            $qs = http_build_query($q);
            $target = $uriPath . ($qs !== '' ? ('?' . $qs) : '');
            $location = function_exists('url') ? url($target) : $target;
            header('Location: ' . $location, true, 301);
            exit;
        }
    }
}

/* SEO: Redirects 301/302 por historial de slugs (GET/HEAD) */
if (in_array($method, ['GET','HEAD'], true)) {
    try {
        $from = $uriPath . ($uriQuery !== '' ? ('?'.$uriQuery) : '');
        $repo = new \App\Repositories\Seo\SeoRedirectRepository(db());
        $row  = $repo->findTargetByFrom($from);
        if ($row && !empty($row['to_path'])) {
            $repo->registerHit($from);
            $status = (int)($row['status_code'] ?? 301);
            if ($status < 300 || $status > 399) $status = 301;
            $target = (string)$row['to_path'];
            // to_path se guarda como ruta app (ej: /producto?slug=nuevo)
            $location = function_exists('url') ? url($target) : $target;
            header('Location: '.$location, true, $status);
            exit;
        }
    } catch (Throwable $e) {
        // No bloqueante: si falla DB, continuamos normal.
    }
}

/* Cargar rutas */
$routesFile = base_path('config/routes.php');
$routes = is_file($routesFile) ? (require $routesFile) : [];
if (!is_array($routes)) $routes = [];

$key = $method . ' ' . $uriPath;
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
    if ($method === 'GET' || $method === 'HEAD') {
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
