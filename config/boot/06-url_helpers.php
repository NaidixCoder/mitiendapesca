<?php
/**
 * 06-url_helpers.php — Helpers de URL/HTML
 */

if (!function_exists('is_https')) {
  function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  }
}

if (!function_exists('base_url')) {
  function base_url(string $path=''): string {
    $scheme = is_https() ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base   = rtrim(str_replace('\\','/', dirname($script)), '/');
    if ($base === '\\') $base = ''; // normaliza edge-case en Windows
    $base   = rtrim("$scheme://$host$base", '/');
    return $base . '/' . ltrim($path, '/');
  }
}

if (!function_exists('url')) {
  function url(string $path=''): string { return base_url($path); }
}

if (!function_exists('asset')) {
  function asset(string $path): string { return base_url('assets/' . ltrim($path,'/')); }
}

if (!function_exists('public_url')) {
  function public_url(string $path): string { return base_url(ltrim($path,'/')); }
}

if (!function_exists('e')) {
  function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
  }
}

if (!function_exists('wants_html')) {
  function wants_html(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $ajax   = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
    return !$ajax && stripos($accept, 'text/html') !== false;
  }
}
