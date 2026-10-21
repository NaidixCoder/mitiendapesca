<?php
namespace App\Repositories\Admin;

use PDO;

final class AdminAuditRepository
{
    public function __construct(private PDO $db) {}

    public function log(int $userId, string $action, ?string $entityType, ?int $entityId, array $metadata = [], ?string $requestId = null): void
    {
        // IP en texto (seguimos el schema: VARBINARY(16) pero guardamos NULL si no podemos)
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ipBin = null;
        if (is_string($ip) && $ip !== '') {
            $packed = @inet_pton($ip);
            if ($packed !== false) $ipBin = $packed;
        }

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metaJson = !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        try {
            $st = $this->db->prepare("
                INSERT INTO admin_audit_log (user_id, request_id, action, entity_type, entity_id, metadata, ip, user_agent)
                VALUES (?,?,?,?,?,?,?,?)
            ");

            $st->execute([
                $userId > 0 ? $userId : null,
                $requestId,
                $action,
                $entityType,
                $entityId,
                $metaJson,
                $ipBin,
                $ua,
            ]);
        } catch (\Throwable $e) {
            // Compat: si la columna request_id no existe aún, insertar sin ella.
            $st2 = $this->db->prepare("
                INSERT INTO admin_audit_log (user_id, action, entity_type, entity_id, metadata, ip, user_agent)
                VALUES (?,?,?,?,?,?,?)
            ");
            $st2->execute([
                $userId > 0 ? $userId : null,
                $action,
                $entityType,
                $entityId,
                $metaJson,
                $ipBin,
                $ua,
            ]);
        }
    }
}
