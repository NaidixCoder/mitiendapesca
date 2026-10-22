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

        $cols = "id, product_id, sku, internal_code, title, price, list_price, cost, cost_currency_id, margin_pct, is_active";
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

        // Únicamente columnas reales de product_variants. Overrides opcionales
        // se insertan si existen físicamente (sin inventar ship_* de productos).
        $price = array_key_exists('price', $fields) && $fields['price'] !== null ? (int)$fields['price'] : null;
        $listPrice = $fields['list_price'] ?? null;
        $cols = ['product_id','sku','internal_code','title','price','list_price','price_updated_at',
                 'cost','cost_currency_id','margin_pct','is_active'];
        $values = [
            $productId, $sku, $fields['internal_code'] ?? null, $fields['title'] ?? null,
            $price, $listPrice,
            ($price !== null || $listPrice !== null) ? gmdate('Y-m-d H:i:s') : null,
            $fields['cost'] ?? null, $fields['cost_currency_id'] ?? null, $fields['margin_pct'] ?? null,
            isset($fields['is_active']) ? (int)$fields['is_active'] : 1,
        ];
        foreach (['price_wh1_override','price_wh2_override'] as $override) {
            if (array_key_exists($override, $fields) && $this->hasColumn('product_variants', $override)) {
                $cols[] = $override;
                $values[] = $fields[$override];
            }
        }
        $sql = 'INSERT INTO product_variants (' . implode(', ', $cols) . ') VALUES ('
             . implode(',', array_fill(0, count($cols), '?')) . ')';
        $st = $this->db->prepare($sql);
        $ok = $st->execute($values);

        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    /** @param array<string,mixed> $fields */
    public function updateById(int $variantId, array $fields): bool
    {
        $variantId = (int)$variantId;
        if ($variantId <= 0) return false;

        $allowed = ['sku','internal_code','title','price','list_price','cost','cost_currency_id','margin_pct','is_active','price_wh1_override','price_wh2_override'];

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
