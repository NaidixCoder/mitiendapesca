<?php
// CSRF bootstrap (simple y compatible con helpers.php)
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

// Generar UNA VEZ por sesión (no regenerar en cada request)
if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

// Mantener compat con código legacy que usa $_SESSION['csrf']
$_SESSION['csrf'] = $_SESSION['_csrf_token'];

// Nota: NO hacer validación acá. La validación se hace explícitamente
// con verify_csrf_or_abort() desde los controladores en los POST.
