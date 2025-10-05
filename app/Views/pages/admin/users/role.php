<?php
// POST /admin/users/role  (gatillado desde el select+botón)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
if (!function_exists('verify_csrf') || !verify_csrf()) { http_response_code(419); exit('CSRF'); }

// Gate adicional por seguridad (el index.php ya lo hace, pero doble check)
if (!function_exists('is_admin') || !is_admin()) { http_response_code(403); exit('Forbidden'); }

$userId = (int)($_POST['user_id'] ?? 0);
$role   = $_POST['role'] ?? '';
$next   = $_POST['next'] ?? '/admin/users';

if (!in_array($role, ['admin','customer'], true) || $userId < 1) {
  flash('error', 'Datos inválidos.');
  redirect($next);
}

// (Opcional) impedir auto-degradarte: descomenta si lo querés
// if ($userId === (int)($_SESSION['uid'] ?? 0) && $role !== 'admin') {
//   flash('error', 'No podés quitarte permisos de admin a vos mismo.');
//   redirect($next);
// }

$st = db()->prepare("UPDATE users SET role=?, updated_at=NOW() WHERE id=? LIMIT 1");
$st->execute([$role, $userId]);

flash('ok', 'Rol actualizado.');
redirect($next);
