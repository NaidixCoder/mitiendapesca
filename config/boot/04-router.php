<?php
/**
 * 04-router.php — Normalización de URI y helpers de request
 * Cargado temprano por config/bootstrap.php
 */

// Normaliza la ruta pedida (sin script base ni /index.php)
$scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$reqPath   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri       = $reqPath;
if ($scriptDir && str_starts_with($uri, $scriptDir)) $uri = substr($uri, strlen($scriptDir));
$uri = preg_replace('#//+#', '/', str_replace('\\','/',$uri));
$uri = '/' . ltrim($uri, '/');
if (strcasecmp($uri, '/index.php') === 0) $uri = '/';
if ($uri !== '/') $uri = rtrim($uri, '/');

// Helpers de request
if (!function_exists('request_uri')) {
  function request_uri(): string {
    global $uri;
    return $uri ?? '/';
  }
}
if (!function_exists('request_method')) {
  function request_method(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
  }
}
if (!function_exists('uri_segment')) {
  function uri_segment(int $i, ?string $default=null): ?string {
    $u = request_uri();
    $parts = array_values(array_filter(explode('/', $u)));
    return $parts[$i-1] ?? $default;
  }
}
if (!function_exists('is_api')) {
  function is_api(): bool {
    $u = request_uri();
    return str_starts_with($u, '/api/');
  }
}
