<?php
// Auditoría opcional
function audit(string $action, string $etype=null, int $eid=null, array $meta=[]): void {
  if (!filter_var(env('AUDIT_ENABLED', false), FILTER_VALIDATE_BOOL)) return;
  try {
    $pdo = db();
    $stmt = $pdo->prepare("
      INSERT INTO admin_audit_log (user_id, action, entity_type, entity_id, metadata, ip, user_agent)
      VALUES (:uid, :action, :etype, :eid, :meta, :ip, :ua)
    ");
    $stmt->execute([
      ':uid'    => (int)($_SESSION['uid'] ?? 0),
      ':action' => $action,
      ':etype'  => $etype,
      ':eid'    => $eid,
      ':meta'   => json_encode($meta, JSON_UNESCAPED_UNICODE),
      ':ip'     => inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
      ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 255),
    ]);
  } catch (\Throwable $e) {
    // si falla, silenciar (no romper UX)
  }
}
