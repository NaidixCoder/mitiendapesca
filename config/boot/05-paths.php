<?php
// ---------- Paths helpers ----------
if (!function_exists('base_path')) {
  function base_path(string $path=''): string {
    return rtrim(BASE_PATH, '/').'/'.ltrim($path,'/');
  }
}
if (!function_exists('app_path')) {
  function app_path(string $path=''): string {
    return base_path('app/'.ltrim($path,'/'));
  }
}
