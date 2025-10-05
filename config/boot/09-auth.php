<?php
// ---------- Auth helpers ----------
function is_admin(): bool {
  return ($_SESSION['urole'] ?? '') === 'admin';
}
function require_admin(): void {
  if (empty($_SESSION['uid'])) {
    $intended = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . base_url('/login?intended='.$intended));
    exit;
  }
  if (!is_admin()) {
    http_response_code(403);
    $file403 = BASE_PATH.'/app/Views/pages/errors/403.php';
    if (is_file($file403)) { include $file403; }
    else { echo 'Forbidden'; }
    exit;
  }
}
