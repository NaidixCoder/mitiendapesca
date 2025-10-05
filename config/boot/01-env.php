<?php
// ---------- ENV loader mínimo ----------
if (!function_exists('env')) {
  function env(string $key, $default=null) {
    static $ENV=null;
    if ($ENV===null) {
      $ENV=[];
      $envPath = BASE_PATH.'/.env';
      if (is_file($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
          $line = trim($line);
          if ($line==='' || str_starts_with($line, '#')) continue;
          [$k,$v] = array_pad(explode('=', $line, 2), 2, '');
          $ENV[trim($k)] = trim($v);
        }
      }
    }
    return $ENV[$key] ?? $default;
  }
}
