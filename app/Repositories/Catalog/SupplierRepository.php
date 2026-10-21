<?php
namespace App\Repositories\Catalog;

use PDO;

final class SupplierRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<int,array{id:int,nombre:string,cuit:?string}> */
    public function listActiveForSelect(): array
    {
        $st = $this->db->query("SELECT id, nombre, cuit FROM suppliers WHERE is_active = 1 ORDER BY nombre ASC");
        return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function findByCuit(?string $cuit): ?array
    {
        $cuit = preg_replace('/\D+/', '', (string)$cuit);
        if ($cuit === '' || strlen($cuit) !== 11) return null;

        $st = $this->db->prepare("SELECT id, nombre, cuit FROM suppliers WHERE cuit = ? LIMIT 1");
        $st->execute([$cuit]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function findByNombre(string $nombre): ?array
    {
        $nombre = trim($nombre);
        if ($nombre === '') return null;

        $st = $this->db->prepare("SELECT id, nombre, cuit FROM suppliers WHERE nombre = ? LIMIT 1");
        $st->execute([$nombre]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(string $nombre, ?string $cuit = null): int
    {
        $nombre = trim($nombre);
        $cuit = preg_replace('/\D+/', '', (string)$cuit);
        if ($cuit === '') $cuit = null;

        $st = $this->db->prepare("INSERT INTO suppliers (nombre, cuit, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $st->execute([$nombre, $cuit]);
        return (int)$this->db->lastInsertId();
    }
}

