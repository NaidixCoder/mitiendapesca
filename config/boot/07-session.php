<?php
// ---------- Sesión endurecida (Hostinger / HTTPS / OAuth OK) ----------
if (session_status() === PHP_SESSION_NONE) {

    // Detectar HTTPS correctamente (proxy-friendly)
    $secure =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');

    session_name(env('SESSION_NAME', 'MISESSID'));

    // --- Domain dinámico (local vs producción) ---
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host); // quita :8080

    $domain = env('SESSION_DOMAIN', ''); // si está seteado, manda

    if ($domain === '') {
        // Local: no seteamos domain (cookie host-only)
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            $domain = '';
        } else {
            // Producción: compartir entre www y no-www
            $domain = '.' . $host; // ej: .mitiendapesca.com
        }
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax', // requerido para Google OAuth
    ]);

    session_start();
}

// ---------- Caducidad de sesión por inactividad (configurable) ----------
if (!empty($_SESSION['uid'])) {
    $now  = time();
    $idle = (int)($_SESSION['_last_activity'] ?? $now);

    // Configurable por .env (minutos). Default: 120 min.
    $idleMax = (int)env('SESSION_IDLE_MINUTES', 120) * 60;
    if ($idleMax <= 0) { $idleMax = 120 * 60; }

    if (($now - $idle) > $idleMax) {
        // Guardar retorno ANTES de destruir (para volver al lugar tras re-login)
        $backTo = (string)($_SERVER['REQUEST_URI'] ?? '/cuenta');
        $keepBack = (function_exists('wants_html') && wants_html()) && (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET');

        session_unset();
        session_destroy();

        // Arrancar una sesión nueva (para poder persistir back_to)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if ($keepBack) {
            $_SESSION['back_to'] = $backTo;
            $_SESSION['_session_expired'] = 1;
        }

        // Ya no hay sesión válida; evitamos seguir actualizando _last_activity.
        return;
    } else {
        $_SESSION['_last_activity'] = $now;
    }
}
