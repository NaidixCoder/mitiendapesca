<?php
// --- Rate limit minimalista (archivo por clave) ---

if (!function_exists('client_ip')) {
  function client_ip(): string {
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($xff) return trim(explode(',', $xff)[0]);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }
}

if (!function_exists('rate_limit_check')) {
  /**
   * @return int|false  retry_after en segundos o false si OK
   */
  function rate_limit_check(string $key, int $max, int $windowSec) {
    $dir = BASE_PATH . '/storage/ratelimits';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = $dir . '/' . sha1($key) . '.json';
    $now  = time();

    $bucket = ['win' => $windowSec, 'hits' => []];
    if (is_file($file)) {
      $loaded = json_decode((string)file_get_contents($file), true);
      if (is_array($loaded)) {
        $bucket = $loaded + $bucket;
      }
    }

    // limpiar hits fuera de ventana
    $bucket['hits'] = array_values(array_filter($bucket['hits'], fn($t) => ($now - (int)$t) < $windowSec));

    // límite alcanzado
    if (count($bucket['hits']) >= $max) {
      return max(1, $windowSec - ($now - (int)($bucket['hits'][0] ?? $now)));
    }

    // registrar intento actual y guardar
    $bucket['hits'][] = $now;
    $bucket['win']    = $windowSec;
    file_put_contents($file, json_encode($bucket, JSON_UNESCAPED_SLASHES), LOCK_EX);
    return false;
  }
}

if (!function_exists('rate_limit_or_fail')) {
  /**
   * Wrapper compatible con código existente.
   * - HTML: renderiza errors/429 con layout (como querías)
   * - JSON: responde JSON estándar
   */
  function rate_limit_or_fail(string $key, int $max, int $windowSec): void {
    $retry = rate_limit_check($key, $max, $windowSec);
    if ($retry === false) return;

    header('Retry-After: ' . (int)$retry);

    // Métricas: login rate-limited (best-effort)
    if (function_exists('auth_metrics_log') && str_starts_with($key, 'auth:login:')) {
      $email = (string)($_POST['email'] ?? '');
      auth_metrics_log('login_failed', 'password', 'rate_limited', null, $email);
    }

    if (wants_html()) {
      http_response_code(429);
      if (function_exists('flash')) {
        // Mensaje genérico (rate-limit real)
        flash('warn', 'Demasiados intentos. Probá nuevamente en unos minutos.');
      }
      if (function_exists('render_page')) {
        render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'rate_limit']);
      } else {
        echo '<main style="max-width:640px;margin:3rem auto;padding:1rem;text-align:center">'
          . '<h1>Demasiados intentos</h1>'
          . '<p>Probá nuevamente en unos minutos.</p>'
          . '</main>';
      }
    } else {
      http_response_code(429);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'too_many_requests', 'retry_after' => (int)$retry]);
    }
    exit;
  }
}
