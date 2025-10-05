<?php
// Handler puro: POST /logout
header('Content-Type: text/plain; charset=utf-8'); // sin HTML

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo 'Method not allowed'; exit;
}
if (!function_exists('verify_csrf') || !verify_csrf()) {
  http_response_code(419); echo 'CSRF token mismatch'; exit;
}

// 1) Revocar “recordarme” (cookie + token en DB)
if (function_exists('clear_remember_me')) {
  clear_remember_me();
}

// 2) Vaciar y destruir sesión + cookie de sesión
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}

// 3) Evitar cache del back button
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// 4) Redirigir
header('Location: ' . base_url('/'));
exit;
