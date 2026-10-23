<?php
namespace App\Repositories\Suppliers;

use PDO;

final class ProveedorRepository
{
    public function __construct(private PDO $db) {}

    private function filterSql(array $filters, array &$params): string
    {
        $status = (string)($filters['status'] ?? 'active');
        $where = match ($status) {
            'inactive' => 'WHERE is_active = 0',
            'all' => 'WHERE 1=1',
            default => 'WHERE is_active = 1',
        };
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (nombre LIKE :q_nombre OR cuit LIKE :q_cuit)';
            $params[':q_nombre'] = '%' . $q . '%';
            $params[':q_cuit'] = '%' . $q . '%';
        }
        return $where;
    }

    public function count(array $filters = []): int
    {
        $params = [];
        $where = $this->filterSql($filters, $params);
        $st = $this->db->prepare("SELECT COUNT(*) FROM suppliers $where");
        foreach ($params as $key => $value) $st->bindValue($key, $value, PDO::PARAM_STR);
        $st->execute();
        return (int)$st->fetchColumn();
    }

    public function list(array $filters = []): array
    {
        $params = [];
        $where = $this->filterSql($filters, $params);
        $limit = max(1, min(200, (int)($filters['limit'] ?? 50)));
        $offset = max(0, (int)($filters['offset'] ?? 0));
        $sql = "SELECT id, nombre, cuit, email, telefono, condicion_iva, is_active, created_at, updated_at
                FROM suppliers $where ORDER BY nombre ASC, id ASC LIMIT :limit OFFSET :offset";
        $st = $this->db->prepare($sql);
        foreach ($params as $key => $value) $st->bindValue($key, $value, PDO::PARAM_STR);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM suppliers WHERE id = :id LIMIT 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByCuit(string $cuitDigits11): ?array
    {
        $st = $this->db->prepare("SELECT * FROM suppliers WHERE cuit = :cuit LIMIT 1");
        $st->bindValue(':cuit', $cuitDigits11, PDO::PARAM_STR);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function insert(array $data): int
    {
        $sql = "INSERT INTO suppliers (nombre, cuit, email, telefono, condicion_iva, is_active, created_at, updated_at)
                VALUES (:nombre, :cuit, :email, :telefono, :condicion_iva, :is_active, NOW(), NOW())";
        $st = $this->db->prepare($sql);
        $this->bindFields($st, $data);
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE suppliers SET nombre=:nombre, cuit=:cuit, email=:email, telefono=:telefono,
                    condicion_iva=:condicion_iva, is_active=:is_active, updated_at=NOW() WHERE id=:id";
        $st = $this->db->prepare($sql);
        $this->bindFields($st, $data);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    private function bindFields(\PDOStatement $st, array $data): void
    {
        foreach (['nombre', 'cuit', 'email', 'telefono', 'condicion_iva'] as $field) {
            $st->bindValue(':' . $field, (string)($data[$field] ?? ''), PDO::PARAM_STR);
        }
        $st->bindValue(':is_active', (int)($data['is_active'] ?? 1), PDO::PARAM_INT);
    }

    public function softDelete(int $id): bool
    {
        $st = $this->db->prepare("UPDATE suppliers SET is_active = 0, updated_at = NOW() WHERE id = :id AND is_active = 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        return $st->rowCount() > 0;
    }
}
