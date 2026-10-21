<?php
// --- Métricas de autenticación (login) ---
// Objetivo: contar éxitos/fallos y razones sin guardar PII sensible.

if (!function_exists('auth_metrics_enabled')) {
  function auth_metrics_enabled(): bool {
    return (string)env('AUTH_METRICS_ENABLED', 'true') === 'true';
  }
}

if (!function_exists('auth_metrics_email_hash')) {
  function auth_metrics_email_hash(string $email): string {
    $email = mb_strtolower(trim($email));
    return hash('sha256', $email);
  }
}

if (!function_exists('auth_metrics_ua_hash')) {
  function auth_metrics_ua_hash(): string {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    return hash('sha256', $ua);
  }
}

if (!function_exists('auth_metrics_log')) {
  /**
   * Registra un evento de auth (best-effort, nunca rompe UX).
   *
   * @param string      $event   login_success | login_failed | logout
   * @param string      $method  password | google | magic_link (futuro)
   * @param string|null $reason  invalid_credentials | rate_limited | csrf | antibot | error | ...
   * @param int|null    $userId
   * @param string|null $email   email en claro (se hashea internamente)
   */
  function auth_metrics_log(string $event, string $method, ?string $reason = null, ?int $userId = null, ?string $email = null): void {
    if (!auth_metrics_enabled()) return;
    if (!function_exists('db')) return;

    try {
      $emailHash = null;
      if (is_string($email) && trim($email) !== '') {
        $emailHash = auth_metrics_email_hash($email);
      }

      $ip = function_exists('client_ip') ? client_ip() : (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
      $uaHash = auth_metrics_ua_hash();

      $stmt = db()->prepare(
        'INSERT INTO auth_login_events (event, method, reason, user_id, email_hash, ip, ua_hash, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
      );
      $stmt->execute([
        $event,
        $method,
        $reason,
        $userId,
        $emailHash,
        $ip,
        $uaHash,
      ]);
    } catch (\Throwable $e) {
      // silencioso: métricas no deben afectar login
    }
  }
}
