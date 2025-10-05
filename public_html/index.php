<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// Autoload (Composer si existe) + PSR-4 App\
$autoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoload)) require $autoload;
spl_autoload_register(function ($class) {
    $p = 'App\\'; $len = strlen($p);
    if (strncmp($p, $class, $len) !== 0) return;
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_file($file)) require $file;
});

// Bootstrap común
require_once BASE_PATH . '/config/bootstrap.php';

// Autologin si aplica
try_autologin_from_cookie();

// Rutas
$routes = require BASE_PATH . '/config/routes.php';

// --- Método y path normalizados --- //
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1) Si bootstrap ya normalizó, úsalo
if (!empty($GLOBALS['uri'])) {
    $uriPath = $GLOBALS['uri']; // esperado tipo "/login"
} else {
    // 2) Calcular sin querystring
    $uriRaw  = $_SERVER['REQUEST_URI'] ?? '/';
    $uriPath = parse_url($uriRaw, PHP_URL_PATH) ?: '/';

    // 2.a) Quitar base de APP_URL (si tiene subcarpeta)
    $envBase = parse_url(env('APP_URL', ''), PHP_URL_PATH) ?: '';
    $envBase = rtrim($envBase, '/');
    if ($envBase && strncmp($uriPath, $envBase, strlen($envBase)) === 0) {
        $uriPath = substr($uriPath, strlen($envBase)) ?: '/';
    }

    // 2.b) Quitar directorio del script (/mi-pesca/public_html)
    $scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir && $scriptDir !== '/' && strncmp($uriPath, $scriptDir, strlen($scriptDir)) === 0) {
        $uriPath = substr($uriPath, strlen($scriptDir)) ?: '/';
    }

    // Normalizar vacío
    if ($uriPath === '') $uriPath = '/';
}

// Clave: "METHOD /ruta"
$key = $method . ' ' . $uriPath;

// Fallback HEAD→GET
if (!isset($routes[$key]) && $method === 'HEAD') {
    $altKey = 'GET ' . $uriPath;
    if (isset($routes[$altKey])) $key = $altKey;
}

// Resolver handler
$handler = $routes[$key] ?? null;

// Handlers RAW (sin layout)
$rawHandlers = [
  'auth/oauth_google',
  'auth/logout',
  'cuenta/sessions_clear',
];

try {
    ob_start();

    if ($handler === null) {
        http_response_code(404);
        render_page('errors/404');
        ob_end_flush(); return;
    }

    // === Controllers: "Class@method" (PSR-4 App\Controllers\...) ===
    if (is_string($handler) && strpos($handler, '@') !== false) {
        [$class, $methodName] = explode('@', $handler, 2);
        $fqcn = '\\App\\Controllers\\' . ltrim($class, '\\');

        // Gate admin si es un controller en el namespace Admin\
        if (str_starts_with($class, 'Admin\\')) {
            require_admin();
        }

        // Autoload PSR-4 (ya registrado) + protección
        if (!class_exists($fqcn) || !method_exists($fqcn, $methodName)) {
            http_response_code(500); exit('Controller o método no encontrado');
        }
        $ctrl = new $fqcn();
        $result = $ctrl->$methodName();

        // Si el método devolvió algo imprimible
        if (is_string($result)) echo $result;

        ob_end_flush(); return;
    }

    // === Handlers "puros" (RAW) ===
    if (in_array($handler, $rawHandlers, true)) {
        $file = BASE_PATH . '/app/Views/pages/' . $handler . '.php';
        if (is_file($file)) { require $file; ob_end_flush(); return; }
        http_response_code(500); exit('RAW handler no encontrado');
    }

    // === Gate admin para vistas bajo admin/* ===
    if (is_string($handler) && str_starts_with($handler, 'admin/')) {
        require_admin();
    }

    // === Vistas "folder/view" con layout ===
    render_page($handler);
    ob_end_flush();

} catch (Throwable $e) {
    if (function_exists('log_error')) {
        log_error('HTTP 500: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    }
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    render_page('errors/500');
}
