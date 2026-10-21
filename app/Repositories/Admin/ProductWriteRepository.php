<?php
namespace App\Repositories\Admin;

use PDO;

final class ProductWriteRepository
{
    public function __construct(private PDO $db) {}

    public function findProductById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $st = $this->db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findProductColumnsById(int $id, array $columns): array
    {
        if ($id <= 0 || $columns === []) {
            return [];
        }

        $safe = [];
        foreach ($columns as $column) {
            $column = (string)$column;
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
                $safe[] = "`{$column}`";
            }
        }
        if ($safe === []) {
            return [];
        }

        $sql = 'SELECT ' . implode(',', $safe) . ' FROM products WHERE id = :id LIMIT 1';
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $id]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function findProductIdByFieldExcluding(string $field, string $value, int $excludeId): ?int
    {
        $allowed = ['slug', 'sku', 'internal_code'];
        if (!in_array($field, $allowed, true)) {
            return null;
        }

        $sql = "SELECT id FROM products WHERE {$field} = :value AND id <> :id LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':value' => $value, ':id' => $excludeId]);
        $id = $st->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    public function countVariantsByProductId(int $productId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM product_variants WHERE product_id = :id');
        $st->execute([':id' => $productId]);

        return (int)$st->fetchColumn();
    }

    public function insertProduct(array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $columns = array_keys($data);
        $sql = 'INSERT INTO products (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
        $st = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $st->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR));
        }

        if (!$st->execute()) {
            return 0;
        }

        return (int)$this->db->lastInsertId();
    }

    public function updateProduct(int $id, array $data): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $sets = [];
        foreach ($data as $key => $_value) {
            $sets[] = "{$key} = :{$key}";
        }

        $st = $this->db->prepare('UPDATE products SET ' . implode(',', $sets) . ' WHERE id = :id');
        foreach ($data as $key => $value) {
            $st->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR));
        }
        $st->bindValue(':id', $id, PDO::PARAM_INT);

        return $st->execute();
    }

    public function findInventoryByProductId(int $productId): array
    {
        $st = $this->db->prepare('SELECT stock, low_stock_threshold FROM inventory WHERE product_id = :id LIMIT 1');
        $st->execute([':id' => $productId]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: ['stock' => null, 'low_stock_threshold' => null];
    }

    public function upsertInventory(int $productId, int $stock, int $lowStockThreshold): void
    {
        $this->db->prepare(
            'INSERT INTO inventory (product_id, stock, low_stock_threshold)
             VALUES (:id,:s1,:l1)
             ON DUPLICATE KEY UPDATE stock=:s2, low_stock_threshold=:l2'
        )->execute([
            ':id' => $productId,
            ':s1' => $stock,
            ':l1' => $lowStockThreshold,
            ':s2' => $stock,
            ':l2' => $lowStockThreshold,
        ]);
    }

    public function recalcVariantPricesForIva(int $productId, float $newIvaFactor, float $multiplier): void
    {
        $st1 = $this->db->prepare(
            'UPDATE product_variants
             SET
               price = ROUND(cost * (1 + :iva) * (1 + (CASE WHEN margin_pct > 2 THEN (margin_pct/100) ELSE margin_pct END))),
               price_updated_at = UTC_TIMESTAMP()
             WHERE product_id = :pid
               AND cost IS NOT NULL
               AND margin_pct IS NOT NULL'
        );
        $st1->execute([':iva' => $newIvaFactor, ':pid' => $productId]);

        $st2 = $this->db->prepare(
            'UPDATE product_variants
             SET
               price = CASE WHEN price IS NULL THEN NULL ELSE ROUND(price * :mul) END,
               list_price = CASE WHEN list_price IS NULL THEN NULL ELSE ROUND(list_price * :mul) END,
               price_updated_at = UTC_TIMESTAMP()
             WHERE product_id = :pid
               AND cost IS NOT NULL
               AND price IS NOT NULL
               AND (margin_pct IS NULL)'
        );
        $st2->execute([':mul' => $multiplier, ':pid' => $productId]);
    }
}
