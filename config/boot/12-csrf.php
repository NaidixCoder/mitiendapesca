<?php
// --- CSRF helpers ---
if (!function_exists('csrf_field')) {
  function csrf_field(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';
  }
}
if (!function_exists('csrf_verify')) {
  function csrf_verify(string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
  }
}
