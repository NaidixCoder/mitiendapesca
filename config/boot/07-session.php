<?php
// ---------- Sesión endurecida ----------
if (session_status() === PHP_SESSION_NONE) {
  $secure = is_https();
  session_name(env('SESSION_NAME','MISESSID'));
  session_set_cookie_params([
    'lifetime'=>0,'path'=>'/','domain'=>'',
    'secure'=>$secure,'httponly'=>true,'samesite'=>'Lax'
  ]);
  session_start();
}

// --- Caducidad de sesión por inactividad (60 min) ---
if (!empty($_SESSION['uid'])) {
  $now = time();
  $idle = (int)($_SESSION['_last_activity'] ?? $now);
  if (($now - $idle) > 3600) { // 60 * 60
    session_unset(); session_destroy();
  } else {
    $_SESSION['_last_activity'] = $now;
  }
}
