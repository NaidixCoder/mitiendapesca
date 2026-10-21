<?php
/**
 * Sync del contador de carrito en sesión para que el badge del header sea consistente.
 * - DB es la fuente de verdad.
 * - Evita el "contador viejo" tras login/logout hasta que el usuario entra a /carrito.
 *
 * Regla: solo intentamos sincronizar si hay señales de carrito (uid, cookie cart_token o cart_count).
 */

use App\Services\Cart\CartReadService;

if (PHP_SAPI !== 'cli') {
    $hasSignal = !empty($_SESSION['uid'])
        || !empty($_COOKIE['cart_token'])
        || array_key_exists('cart_count', $_SESSION);

    if ($hasSignal) {
        try {
            // Si no hay cookie ni usuario, no forzamos crear token.
            if (empty($_SESSION['uid']) && empty($_COOKIE['cart_token'])) {
                $_SESSION['cart_count'] = 0;
            } else {
                $svc = new CartReadService(db());
                $svc->syncSessionCount();
            }
        } catch (\Throwable $e) {
            // En caso de error, no rompemos el request.
            // Dejar el contador en 0 evita inconsistencias "fantasma".
            $_SESSION['cart_count'] = (int)($_SESSION['cart_count'] ?? 0);
        }
    }
}
