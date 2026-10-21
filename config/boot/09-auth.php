<?php
// ---------- Auth helpers ----------
// Nota: auth_required/admin_required viven en app/Support/helpers.php (autoload via Composer files).
// Acá dejamos helpers legacy con guards para evitar redeclare.

if (!function_exists('is_admin')) {
  function is_admin(): bool {
    return (string)($_SESSION['urole'] ?? '') === 'admin';
  }
}

if (!function_exists('require_admin')) {
  function require_admin(): void {
    // Preferir admin_required si existe (incluye session_version)
    if (function_exists('admin_required')) {
      admin_required('/admin');
      return;
    }

    // Fallback legacy (por si el helper global no está cargado)
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
}

function enforce_password_freshness(int $checkEverySec = 300): void {
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid <= 0) return;

    $now = time();
    $next = (int)($_SESSION['_pwd_chk_next'] ?? 0);
    if ($next > $now) return;

    $_SESSION['_pwd_chk_next'] = $now + $checkEverySec;

    try {
      $stmt = db()->prepare('SELECT password_changed_at FROM users WHERE id=? LIMIT 1');
      $stmt->execute([$uid]);
      $row = $stmt->fetch(\PDO::FETCH_ASSOC);
      $dbVal = $row['password_changed_at'] ?? null;
      $dbVal = $dbVal ? (string)$dbVal : '';

      $sessVal = (string)($_SESSION['_pwd_changed_at'] ?? '');
      if ($sessVal === '') {
        $_SESSION['_pwd_changed_at'] = $dbVal;
        return;
      }

      if ($dbVal !== '' && $sessVal !== '' && $dbVal !== $sessVal) {
        // Revocado
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
          @session_destroy();
        }

        if (function_exists('flash')) {
          flash('warn', 'Tu contraseña fue actualizada. Por seguridad, volvé a iniciar sesión.');
        }

        if (function_exists('wants_html') && wants_html()) {
          redirect('/login');
        } else {
          json(['ok' => false, 'error' => 'session_revoked'], 401);
        }
      }
    } catch (\Throwable $e) {
      // Si no existe el campo aún, o DB no disponible, ignoramos.
      return;
    }
  }

// Ejecutar en cada request (best-effort)
if (function_exists('enforce_password_freshness')) {
  enforce_password_freshness();
}
