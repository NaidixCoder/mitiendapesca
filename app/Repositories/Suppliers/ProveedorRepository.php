<?php
namespace App\Repositories\Suppliers;

use PDO;

final class ProveedorRepository
{
    public function __construct(private PDO $db) {}

    public function list(array $filters = []): array
    {
        $q = trim((string)($filters['q'] ?? ''));
        $limit = (int)($filters['limit'] ?? 50);
        if ($limit < 1) $limit = 50;
        if ($limit > 200) $limit = 200;

        $where = 'WHERE is_active = 1';
        $params = [];

        if ($q !== '') {
            $where .= ' AND (nombre LIKE :q OR cuit LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT id, nombre, cuit, email, telefono, condicion_iva, is_active, created_at, updated_at
                FROM suppliers
                $where
                ORDER BY nombre ASC
                LIMIT $limit";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM suppliers WHERE id = :id LIMIT 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCuit(string $cuitDigits11): ?array
    {
        $st = $this->db->prepare("SELECT * FROM suppliers WHERE cuit = :cuit LIMIT 1");
        $st->bindValue(':cuit', $cuitDigits11, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $sql = "INSERT INTO suppliers (nombre, cuit, email, telefono, condicion_iva, is_active, created_at, updated_at)
                VALUES (:nombre, :cuit, :email, :telefono, :condicion_iva, :is_active, NOW(), NOW())";
        $st = $this->db->prepare($sql);
        $st->bindValue(':nombre', (string)$data['nombre'], PDO::PARAM_STR);
        $st->bindValue(':cuit', (string)$data['cuit'], PDO::PARAM_STR);
        $st->bindValue(':email', (string)($data['email'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':telefono', (string)($data['telefono'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':condicion_iva', (string)($data['condicion_iva'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':is_active', (int)($data['is_active'] ?? 1), PDO::PARAM_INT);
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE suppliers
                SET nombre = :nombre,
                    cuit = :cuit,
                    email = :email,
                    telefono = :telefono,
                    condicion_iva = :condicion_iva,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->bindValue(':nombre', (string)$data['nombre'], PDO::PARAM_STR);
        $st->bindValue(':cuit', (string)$data['cuit'], PDO::PARAM_STR);
        $st->bindValue(':email', (string)($data['email'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':telefono', (string)($data['telefono'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':condicion_iva', (string)($data['condicion_iva'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':is_active', (int)($data['is_active'] ?? 1), PDO::PARAM_INT);
        $st->execute();
    }

    public function softDelete(int $id): void
    {
        $st = $this->db->prepare("UPDATE suppliers SET is_active = 0, updated_at = NOW() WHERE id = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }
}
