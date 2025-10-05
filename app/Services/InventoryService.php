<?php
namespace App\Services;

use PDO;

class InventoryService
{
    public function __construct(private PDO $db) {}

    public function get(int $productId): array {
        if ($productId <= 0) return ['stock'=>0,'low_stock_threshold'=>3];
        $st = $this->db->prepare("SELECT stock, low_stock_threshold FROM inventory WHERE product_id=?");
        $st->execute([$productId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: ['stock'=>0,'low_stock_threshold'=>3];
    }

    public function upsert(int $productId, int $stock, int $low): void {
        $st = $this->db->prepare("SELECT COUNT(*) FROM inventory WHERE product_id=?");
        $st->execute([$productId]);
        if ((int)$st->fetchColumn() > 0) {
            $this->db->prepare("UPDATE inventory SET stock=?, low_stock_threshold=? WHERE product_id=?")
                     ->execute([$stock, $low, $productId]);
        } else {
            $this->db->prepare("INSERT INTO inventory (product_id, stock, low_stock_threshold) VALUES (?,?,?)")
                     ->execute([$productId, $stock, $low]);
        }
    }

    public function adjust(int $productId, int $delta): void {
        $this->db->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id=?")
                 ->execute([$delta, $productId]);
    }

    public function setStock(int $productId, int $stock): void {
        $this->db->prepare("UPDATE inventory SET stock=? WHERE product_id=?")
                 ->execute([$stock, $productId]);
    }

    public function lowStockList(int $threshold = 3, int $limit = 10): array {
        $st = $this->db->prepare("""
          SELECT i.product_id, p.sku, p.name, i.stock, i.low_stock_threshold
          FROM inventory i
          JOIN products p ON p.id = i.product_id
          WHERE i.stock <= i.low_stock_threshold AND i.low_stock_threshold >= 0
          ORDER BY i.stock ASC, p.created_at DESC
          LIMIT ?
        """);
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
