<?php

declare(strict_types=1);

namespace App\Repositories\Orders;

use PDO;

final class OrderFiscalRequestRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = \DB::pdo();
    }

    public function tableExists(): bool
    {
        try {
            $st = $this->db->query("SHOW TABLES LIKE 'order_fiscal_requests'");
            return (bool)($st && $st->fetchColumn());
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,mixed>|null */
    public function findByOrderId(int $orderId): ?array
    {
        if ($orderId <= 0 || !$this->tableExists()) return null;
        $st = $this->db->prepare("SELECT * FROM order_fiscal_requests WHERE order_id = ? LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

    /** @param array<string,mixed> $data */
    public function upsertForOrder(int $orderId, array $data): void
    {
        if ($orderId <= 0 || !$this->tableExists()) return;

        $existing = $this->findByOrderId($orderId);

        $fields = [
            'requested_type' => (string)($data['requested_type'] ?? 'A'),
            'status' => (string)($data['status'] ?? 'pending'),
            'customer_tax_condition' => (string)($data['customer_tax_condition'] ?? ''),
            'customer_cuit' => (string)($data['customer_cuit'] ?? ''),
            'customer_legal_name' => (string)($data['customer_legal_name'] ?? ''),
            'notes' => $data['notes'] ?? null,
        ];

        if ($existing) {
            $st = $this->db->prepare("UPDATE order_fiscal_requests
                SET requested_type=:requested_type,
                    status=:status,
                    customer_tax_condition=:customer_tax_condition,
                    customer_cuit=:customer_cuit,
                    customer_legal_name=:customer_legal_name,
                    notes=:notes,
                    updated_at=NOW()
                WHERE order_id=:order_id");
            $st->execute([
                ':requested_type' => $fields['requested_type'],
                ':status' => $fields['status'],
                ':customer_tax_condition' => $fields['customer_tax_condition'],
                ':customer_cuit' => $fields['customer_cuit'],
                ':customer_legal_name' => $fields['customer_legal_name'],
                ':notes' => $fields['notes'],
                ':order_id' => $orderId,
            ]);
            return;
        }

        $st = $this->db->prepare("INSERT INTO order_fiscal_requests
            (order_id, requested_type, status, customer_tax_condition, customer_cuit, customer_legal_name, notes, created_at)
            VALUES (:order_id, :requested_type, :status, :customer_tax_condition, :customer_cuit, :customer_legal_name, :notes, NOW())");
        $st->execute([
            ':order_id' => $orderId,
            ':requested_type' => $fields['requested_type'],
            ':status' => $fields['status'],
            ':customer_tax_condition' => $fields['customer_tax_condition'],
            ':customer_cuit' => $fields['customer_cuit'],
            ':customer_legal_name' => $fields['customer_legal_name'],
            ':notes' => $fields['notes'],
        ]);
    }

    public function updateStatus(int $orderId, string $status, ?string $notes = null): void
    {
        if ($orderId <= 0 || !$this->tableExists()) return;
        $status = trim($status);
        if ($status === '') return;

        $st = $this->db->prepare("UPDATE order_fiscal_requests SET status=:status, notes=:notes, updated_at=NOW() WHERE order_id=:order_id");
        $st->execute([
            ':status' => $status,
            ':notes' => $notes,
            ':order_id' => $orderId,
        ]);
    }
}

