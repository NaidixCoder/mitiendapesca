<?php
// --- Rate limit minimalista (archivo por clave) ---
function client_ip(): string {
  $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
  if ($xff) return trim(explode(',', $xff)[0]);
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function rate_limit_or_fail(string $key, int $max, int $windowSec): void {
  $dir = BASE_PATH . '/storage/ratelimits';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $file = $dir . '/' . sha1($key) . '.json';
  $now  = time();

  $bucket = ['win' => $windowSec, 'hits' => []];
  if (is_file($file)) {
    $loaded = json_decode((string)file_get_contents($file), true);
    if (is_array($loaded)) {
      $bucket = $loaded + $bucket; // conserva hits y win si existen
    }
  }

  // limpiar hits fuera de ventana
  $bucket['hits'] = array_values(array_filter($bucket['hits'], fn($t) => ($now - (int)$t) < $windowSec));

  // si no quedan hits, eliminar archivo viejo (limpieza vaga)
  if (empty($bucket['hits'])) {
    @unlink($file);
  }

  // límite alcanzado
  if (count($bucket['hits']) >= $max) {
    $retry = max(1, $windowSec - ($now - (int)($bucket['hits'][0] ?? $now)));
    header('Retry-After: ' . $retry);

    if (wants_html()) {
      http_response_code(429);
      // Importante: incluir SOLO el cuerpo (sin head/header/footer) para evitar duplicados
      $file429 = BASE_PATH . '/app/Views/pages/errors/429.php';
      if (is_file($file429)) {
        require $file429;
      } else {
        echo '<main style="max-width:640px;margin:3rem auto;padding:1rem;text-align:center">'
          . '<h1>Demasiados intentos</h1>'
          . '<p>Probá nuevamente en unos minutos.</p>'
          . '</main>';
      }
    } else {
      http_response_code(429);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'too_many_requests', 'retry_after' => $retry]);
    }
    exit;
  }

  // registrar intento actual y guardar
  $bucket['hits'][] = $now;
  $bucket['win']    = $windowSec; // asegura ventana actual
  file_put_contents($file, json_encode($bucket, JSON_UNESCAPED_SLASHES), LOCK_EX);
}
