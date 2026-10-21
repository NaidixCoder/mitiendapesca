<?php
namespace App\Repositories\Catalog;

use App\Services\Catalog\ProductPublicationSyncService;
use PDO;

final class InventoryRepository
{
    public function __construct(private PDO $db) {}

    public function getByProductId(int $pid): ?array
    {
        $st = $this->db->prepare("SELECT * FROM inventory WHERE product_id=?");
        $st->execute([$pid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateByProductId(int $pid, int $stock, int $threshold): bool
    {
        $sql = "UPDATE inventory SET stock=?, low_stock_threshold=? WHERE product_id=?";
        $ok = (bool) $this->db->prepare($sql)->execute([$stock,$threshold,$pid]);
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncProduct($pid);
        }
        return $ok;
    }

    public function insert(int $pid, int $stock, int $threshold): bool
    {
        $sql = "INSERT INTO inventory (product_id, stock, low_stock_threshold) VALUES (?,?,?)";
        $ok = (bool) $this->db->prepare($sql)->execute([$pid,$stock,$threshold]);
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncProduct($pid);
        }
        return $ok;
    }

    public function getByVariantId(int $variantId): ?array
    {
        $variantId = (int)$variantId;
        if ($variantId <= 0) return null;
        $st = $this->db->prepare("SELECT * FROM inventory_variants WHERE variant_id=?");
        $st->execute([$variantId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateByVariantId(int $variantId, int $stock, int $threshold): bool
    {
        $sql = "UPDATE inventory_variants SET stock=?, low_stock_threshold=? WHERE variant_id=?";
        $ok = (bool)$this->db->prepare($sql)->execute([$stock,$threshold,(int)$variantId]);
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncByVariant($variantId);
        }
        return $ok;
    }

    public function insertVariant(int $variantId, int $stock, int $threshold): bool
    {
        $sql = "INSERT INTO inventory_variants (variant_id, stock, low_stock_threshold) VALUES (?,?,?)";
        $ok = (bool)$this->db->prepare($sql)->execute([(int)$variantId,$stock,$threshold]);
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncByVariant($variantId);
        }
        return $ok;
    }

    /**
     * Lockea la fila de inventario (FOR UPDATE) y devuelve stock actual.
     * Si no existe fila, devuelve null.
     */
    public function lockStockForUpdate(int $pid): ?int
    {
        $st = $this->db->prepare("SELECT stock FROM inventory WHERE product_id = ? FOR UPDATE");
        $st->execute([$pid]);
        $v = $st->fetchColumn();
        return ($v === false) ? null : (int)$v;
    }
    /**
     * Lockea el inventario de una variante (FOR UPDATE) y devuelve stock actual.
     * Si no existe fila, devuelve null.
     */
    public function lockVariantStockForUpdate(int $variantId): ?int
    {
        if ($variantId <= 0) return null;
        $st = $this->db->prepare("SELECT stock FROM inventory_variants WHERE variant_id = ? FOR UPDATE");
        $st->execute([$variantId]);
        $v = $st->fetchColumn();
        return ($v === false) ? null : (int)$v;
    }

    public function decrementVariantStock(int $variantId, int $qty): bool
    {
        $variantId = (int)$variantId;
        $qty = max(0, (int)$qty);
        if ($variantId <= 0) return false;
        $st = $this->db->prepare("UPDATE inventory_variants SET stock = stock - ? WHERE variant_id = ?");
        $st->execute([$qty, $variantId]);
        $ok = $st->rowCount() > 0;
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncByVariant($variantId);
        }
        return $ok;
    }

    public function incrementVariantStock(int $variantId, int $qty): bool
    {
        $variantId = (int)$variantId;
        $qty = max(0, (int)$qty);
        if ($variantId <= 0) return false;
        $st = $this->db->prepare("UPDATE inventory_variants SET stock = stock + ? WHERE variant_id = ?");
        $st->execute([$qty, $variantId]);
        $ok = $st->rowCount() > 0;
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncByVariant($variantId);
        }
        return $ok;
    }


    public function decrementStock(int $pid, int $qty): bool
    {
        $qty = max(0, (int)$qty);
        $st = $this->db->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ?");
        $st->execute([$qty, $pid]);
        $ok = $st->rowCount() > 0;
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncProduct($pid);
        }
        return $ok;
    }

    public function incrementStock(int $pid, int $qty): bool
    {
        $qty = max(0, (int)$qty);
        $st = $this->db->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ?");
        $st->execute([$qty, $pid]);
        $ok = $st->rowCount() > 0;
        if ($ok) {
            (new ProductPublicationSyncService($this->db))->syncProduct($pid);
        }
        return $ok;
    }


public function getProductStock(int $pid): ?int
{
    if ($pid <= 0) return null;
    $st = $this->db->prepare("SELECT stock FROM inventory WHERE product_id=?");
    $st->execute([$pid]);
    $v = $st->fetchColumn();
    return ($v === false) ? null : (int)$v;
}

public function getVariantStock(int $variantId): ?int
{
    if ($variantId <= 0) return null;
    $st = $this->db->prepare("SELECT stock FROM inventory_variants WHERE variant_id=?");
    $st->execute([$variantId]);
    $v = $st->fetchColumn();
    return ($v === false) ? null : (int)$v;
}

}

