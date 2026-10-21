<?php
/**
 * 14-antibot.php — Anti-bot básico (sin JS inline)
 *
 * Objetivo: frenar bots simples en formularios sensibles (login/registro/reenviar).
 * - Honeypot: campo invisible (si viene con contenido, bloquea)
 * - Tiempo mínimo entre render y POST (submit demasiado rápido)
 *
 * Regla de oro del proyecto: este helper NO renderiza vistas ni decide redirects.
 * Solo expone:
 *   - antibot_fields(): imprime campos y guarda timestamp
 *   - antibot_check(): valida y devuelve estado (sin exit)
 */

if (!function_exists('antibot_fields')) {
    function antibot_fields(string $formId): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $_SESSION['_antibot_ts'] = $_SESSION['_antibot_ts'] ?? [];
        $_SESSION['_antibot_ts'][$formId] = time();

        // Nota: NO usar nombres comunes tipo "website" porque algunos navegadores/autofill pueden completarlo
        // y provocar falsos positivos. Usamos un nombre poco probable.
        return
            "\n" .
            '<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">' .
                '<label>Dejá esto vacío</label>' .
                '<input type="text" name="hp_token" value="" autocomplete="off" tabindex="-1" aria-hidden="true">' .
            '</div>' . "\n" .
            '<input type="hidden" name="_form_id" value="' . e($formId) . '">' . "\n";
    }
}

if (!function_exists('antibot_check')) {
  /**
   * Valida honeypot + tiempo mínimo.
   *
   * @return string|false
   *   - false: OK
   *   - 'invalid': honeypot o form_id incorrecto
   *   - 'fast_submit': envío demasiado rápido
   */
  function antibot_check(string $formId, int $minSeconds = 2) {
    // Honeypot
    $hp = trim((string)($_POST['hp_token'] ?? ''));
    // Backward-compat: si quedó algún form viejo publicando "website"
    $hpLegacy = trim((string)($_POST['website'] ?? ''));
    if ($hp !== '' || $hpLegacy !== '') {
      return 'invalid';
    }

    // Form ID (permisivo): si no viene, no bloqueamos (evita falsos positivos por forms/cache/extensiones)
    // Si viene y es distinto, ahí sí bloqueamos.
    $postedId = (string)($_POST['_form_id'] ?? '');
    if ($postedId !== '' && $postedId !== $formId) {
      return 'invalid';
    }


    $ts  = (int)($_SESSION['_antibot_ts'][$formId] ?? 0);
    $now = time();

    // Si no hay timestamp (sesión perdida / bots raros), no bloqueamos
    if ($ts > 0 && ($now - $ts) < $minSeconds) {
      return 'fast_submit';
    }

    // Evitar reuso infinito (best-effort)
    unset($_SESSION['_antibot_ts'][$formId]);
    return false;
  }
}

// Backward-compat (por si quedó alguna llamada vieja)
if (!function_exists('antibot_verify_or_abort')) {
  function antibot_verify_or_abort(string $formId, int $minSeconds = 2): void {
    $res = antibot_check($formId, $minSeconds);
    if ($res === false) return;

    if ($res === 'fast_submit') {
      http_response_code(429);
      header('Retry-After: ' . $minSeconds);
      exit;
    }

    http_response_code(400);
    exit;
  }
}
