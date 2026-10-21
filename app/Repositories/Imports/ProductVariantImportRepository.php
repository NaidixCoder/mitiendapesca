<?php
namespace App\Repositories\Imports;

use PDO;

/**
 * Repo específico para importación (upsert por SKU).
 * Mantener SQL fuera de services.
 */
final class ProductVariantImportRepository
{
    public function __construct(private PDO $db) {}

    /** Cache simple de columnas existentes (por proceso). */
    private static array $colCache = [];

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;
        if (array_key_exists($key, self::$colCache)) return (bool)self::$colCache[$key];

        try {
            $st = $this->db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $st->execute([$column]);
            $ok = (bool)($st->fetch(PDO::FETCH_ASSOC) ?: false);
            self::$colCache[$key] = $ok;
            return $ok;
        } catch (\Throwable $e) {
            self::$colCache[$key] = false;
            return false;
        }
    }

    /** @return array<string,mixed>|null */
    public function findBySku(string $sku): ?array
    {
        $sku = trim($sku);
        if ($sku === '') return null;

        $cols = "id, product_id, sku, internal_code, title, price, list_price, cost, is_active, ship_weight, ship_height, ship_width, ship_length";
        if ($this->hasColumn('product_variants','price_wh1_override')) $cols .= ", price_wh1_override";
        if ($this->hasColumn('product_variants','price_wh2_override')) $cols .= ", price_wh2_override";

        $st = $this->db->prepare(
            "SELECT {$cols}
               FROM product_variants
              WHERE sku = ?
              LIMIT 1"
        );
        $st->execute([$sku]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insert(int $productId, array $fields): int
    {
        $productId = (int)$productId;
        if ($productId <= 0) return 0;

        $sku = trim((string)($fields['sku'] ?? ''));
        if ($sku === '') return 0;

        // IMPORTANTE: mantener cantidad de columnas = cantidad de placeholders.
        $sql = "INSERT INTO product_variants
                    (product_id, sku, internal_code, title, price, list_price, price_updated_at, cost, cost_currency_id, margin_pct, ship_weight, ship_height, ship_width, ship_length, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $st = $this->db->prepare($sql);
        $price = array_key_exists('price', $fields) ? (int)$fields['price'] : null;
        $listPrice = $fields['list_price'] ?? null;
        $priceUpdatedAt = ($price !== null || $listPrice !== null) ? gmdate('Y-m-d H:i:s') : null;

        $ok = $st->execute([
            $productId,
            $sku,
            $fields['internal_code'] ?? null,
            $fields['title'] ?? null,
            $price,
            $fields['list_price'] ?? null,
            $priceUpdatedAt,
            $fields['cost'] ?? null,
            $fields['cost_currency_id'] ?? null,
            $fields['margin_pct'] ?? null,
            $fields['ship_weight'] ?? null,
            $fields['ship_height'] ?? null,
            $fields['ship_width'] ?? null,
            $fields['ship_length'] ?? null,
            isset($fields['is_active']) ? (int)$fields['is_active'] : 1,
        ]);

        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    /** @param array<string,mixed> $fields */
    public function updateById(int $variantId, array $fields): bool
    {
        $variantId = (int)$variantId;
        if ($variantId <= 0) return false;

        $allowed = ['sku','internal_code','title','price','list_price','cost','cost_currency_id','margin_pct','is_active','ship_weight','ship_height','ship_width','ship_length','price_wh1_override','price_wh2_override'];

        if (!$this->hasColumn('product_variants','price_wh1_override')) {
            $allowed = array_values(array_filter($allowed, fn($c) => $c !== 'price_wh1_override'));
        }
        if (!$this->hasColumn('product_variants','price_wh2_override')) {
            $allowed = array_values(array_filter($allowed, fn($c) => $c !== 'price_wh2_override'));
        }

        $set = [];
        $params = [];

        // Registrar fecha de modificación de precio (solo si cambia price/list_price)
        $trackPrice = array_key_exists('price', $fields) || array_key_exists('list_price', $fields);
        if ($trackPrice) {
            $set[] = "price_updated_at = ?";
            $params[] = gmdate('Y-m-d H:i:s');
        }

        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;

            $set[] = "{$k} = ?";
            $params[] = $v;
        }

        if (!$set) return true; // nada que actualizar

        $params[] = $variantId;
        $sql = "UPDATE product_variants SET ".implode(', ', $set)." WHERE id = ? LIMIT 1";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute($params);
    }
}

