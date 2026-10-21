<?php
// Auditoría opcional
function audit(string $action, string $etype=null, int $eid=null, array $meta=[]): void {
  if (!filter_var(env('AUDIT_ENABLED', false), FILTER_VALIDATE_BOOL)) return;

  try {
    $pdo = db();

    // Si no hay usuario logueado (session), usamos el entity_id cuando es un user
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid <= 0 && $etype === 'user' && !empty($eid)) {
      $uid = (int)$eid;
    }

    $stmt = $pdo->prepare("
      INSERT INTO admin_audit_log (user_id, action, entity_type, entity_id, metadata, ip, user_agent)
      VALUES (:uid, :action, :etype, :eid, :meta, :ip, :ua)
    ");
    $stmt->execute([
      ':uid'    => $uid, // ✅ ahora nunca es 0 si tenemos entity_id
      ':action' => $action,
      ':etype'  => $etype,
      ':eid'    => $eid,
      ':meta'   => json_encode($meta, JSON_UNESCAPED_UNICODE),
      ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
      ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 255),
    ]);
  } catch (\Throwable $e) {
    // si falla, silenciar (no romper UX)
    // TIP debug temporal:
    // if (env('APP_DEBUG', false)) { error_log('[AUDIT_FAIL] '.$e->getMessage()); }
  }
}
