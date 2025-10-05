<?php
namespace App\Services;

class Audit {
  /** Registra un evento de auditoría (silencioso si falla). */
  public static function log(string $action, ?string $entityType=null, ?int $entityId=null, array $meta=[]): void {
    try {
      $db = db();
      $stmt = $db->prepare("
        INSERT INTO admin_audit_log (user_id, action, entity_type, entity_id, metadata, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, INET6_ATON(:ip), :ua)
      ");
      $stmt->execute([
        $_SESSION['uid'] ?? 0,
        $action,
        $entityType,
        $entityId,
        $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
      ]);
    } catch (\Throwable $e) {
      // opcional: log interno, pero jamás romper UX del admin
    }
  }
}
