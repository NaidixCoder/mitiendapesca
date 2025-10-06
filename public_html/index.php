<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
function base_path($p = '') { return BASE_PATH . ($p ? DIRECTORY_SEPARATOR . ltrim($p, '/\\') : ''); }

// === Cargar helpers de vistas (define render_page, section, etc.) ===
$helpersFile = base_path('app/Views/helpers.php');
if (is_file($helpersFile)) {
    require_once $helpersFile;
} else {
    // Fallback super mínimo para no romper (podés quitar esto si ya existe helpers.php)
    function render_page(string $view, array $data = []) {
        extract($data, EXTR_SKIP);
        $vf = base_path('app/Views/' . $view . '.php');
        if (!is_file($vf)) { http_response_code(404); echo "Vista no encontrada: $view"; return; }
        include $vf;
    }
}
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// ===== Bootstrap =====
// Carga todos los bootstraps en orden (00-..,01-.., etc.)
foreach (glob(BASE_PATH . '/config/boot/*.php') as $bootFile) {
    require_once $bootFile;
}

// Método y URI normalizados
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = $GLOBALS['uri'] ?? (function (): string {
    $raw = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($raw, PHP_URL_PATH) ?: '/';
    // Normalizar dobles barras y trailing slash
    $path = preg_replace('#//+#', '/', str_replace('\\','/',$path));
    if ($path !== '/' ) $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
})();

// Cargar rutas
$routesFile = BASE_PATH . '/config/routes.php';
$routes = is_file($routesFile) ? (require $routesFile) : [];

// Resolver handler
$key = strtoupper($method) . ' ' . $uriPath;
$handler = $routes[$key] ?? null;



// Not found → 404
if ($handler === null) {
    http_response_code(404);
    render_page('errors/404');
    exit;
}

// Despachar
try {
    if (is_string($handler)) {
        // Controller "Namespace\\Class@method"  ó  Vista "folder/view"
        if (strpos($handler, '@') !== false || strpos($handler, '\\') !== false) {
            [$class, $action] = array_pad(explode('@', $handler, 2), 2, 'index');
            // Si NO viene con App\ al inicio, agregárselo
            if (str_starts_with($class, '\\')) $class = ltrim($class, '\\');
            if (!str_starts_with($class, 'App\\')) $class = 'App\\Controllers\\' . $class;
            if (!class_exists($class)) { throw new RuntimeException("Controller no encontrado: $class"); }
            $ctrl = new $class();
            if (!method_exists($ctrl, $action)) { throw new RuntimeException("Método no encontrado: $class@$action"); }
            $ctrl->$action();
        } else {
            render_page($handler);
        }
    } else {
        throw new RuntimeException('Handler inválido para la ruta: ' . $key);
    }
} catch (Throwable $e) {
    if (function_exists('log_error')) {
        log_error('HTTP 500: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    }
    http_response_code(500);
    if (function_exists('env') && env('APP_DEBUG','false')==='true') {
        echo '<pre style="padding:16px;margin:16px;background:#111;color:#eee;border-radius:8px;white-space:pre-wrap">';
        echo "Exception: ".$e->getMessage()."\n\n".$e->getTraceAsString();
        echo '</pre>';
    }
    render_page('errors/500');
}

