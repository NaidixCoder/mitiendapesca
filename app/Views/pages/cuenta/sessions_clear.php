<?php
// Handler puro: POST /cuenta/sessions/clear
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; exit; }
if (empty($_SESSION['uid'])) { http_response_code(401); echo 'Unauthorized'; exit; }
if (!function_exists('verify_csrf') || !verify_csrf()) { http_response_code(419); echo 'CSRF'; exit; }

$uid = (int)$_SESSION['uid'];

// Revocar TODOS los tokens persistentes del usuario
$del = db()->prepare('DELETE FROM user_tokens WHERE user_id=?');
$del->execute([$uid]);

// Revocar cookie local de “recordarme” (si existe)
if (function_exists('clear_remember_me')) clear_remember_me();

// Endurecer la sesión actual (sigue logueado en este dispositivo)
session_regenerate_id(true);

// Mensaje y redirect a /cuenta
flash('ok', 'Cerraste todas las sesiones en otros dispositivos.');
header('Location: ' . url('/cuenta'));
exit;
