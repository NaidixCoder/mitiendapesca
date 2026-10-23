<?php
namespace App\Repositories\Admin;

use PDO;

final class AuditLoginsRepository
{
    public function __construct(private PDO $db) {}

    public function count(string $whereSql, array $params): int
    {
        $sql = "SELECT COUNT(*)
                FROM auth_login_events e
                LEFT JOIN users u ON u.id = e.user_id
                {$whereSql}";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public function fetch(string $whereSql, array $params, int $limit, int $offset): array
    {
        $sql = "SELECT
                  e.*,
                  u.email AS user_email,
                  u.name AS user_full_name
                FROM auth_login_events e
                LEFT JOIN users u ON u.id = e.user_id
                {$whereSql}
                ORDER BY e.created_at DESC, e.id DESC
                LIMIT :limit OFFSET :offset";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
