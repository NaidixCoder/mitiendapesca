<?php
namespace App\Repositories\Orders;

use PDO;

final class OrderItemRepository
{
    public function __construct(private PDO $db) {}

    private static array $hasCol = [];

    private function hasColumn(string $col): bool
    {
        if (array_key_exists($col, self::$hasCol)) return self::$hasCol[$col];
        $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS\n            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='order_items' AND COLUMN_NAME=:c");
        $st->execute([':c' => $col]);
        $ok = ((int)$st->fetchColumn() > 0);
        self::$hasCol[$col] = $ok;
        return $ok;
    }

    /**
     * Inserta snapshots de items del pedido.
     * @param array<int,array<string,mixed>> $items
     */
    public function bulkInsert(int $orderId, array $items): void
    {
        if ($orderId <= 0 || empty($items)) return;

        // Snapshot fiscal (opcional)
        $hasVatRate = $this->hasColumn('vat_rate');
        $hasUnitNet = $this->hasColumn('unit_net');
        $hasUnitVat = $this->hasColumn('unit_vat');
        $hasLineNet = $this->hasColumn('line_net');
        $hasLineVat = $this->hasColumn('line_vat');

        $cols = [
            'order_id','product_id','variant_id','qty','price','unit_list_price','unit_cost',
        ];
        $vals = [
            ':order_id',':product_id',':variant_id',':qty',':price',':unit_list_price',':unit_cost',
        ];

        if ($hasVatRate) { $cols[] = 'vat_rate'; $vals[]=':vat_rate'; }
        if ($hasUnitNet) { $cols[] = 'unit_net'; $vals[]=':unit_net'; }
        if ($hasUnitVat) { $cols[] = 'unit_vat'; $vals[]=':unit_vat'; }
        if ($hasLineNet) { $cols[] = 'line_net'; $vals[]=':line_net'; }
        if ($hasLineVat) { $cols[] = 'line_vat'; $vals[]=':line_vat'; }

        $cols = array_merge($cols, [
            'product_sku','product_name',
            'variant_sku','variant_title','variant_attributes_json',
            'created_at'
        ]);
        $vals = array_merge($vals, [
            ':product_sku',':product_name',
            ':variant_sku',':variant_title',':variant_attributes_json',
            'NOW()'
        ]);

        $sql = "INSERT INTO order_items (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
        $st = $this->db->prepare($sql);

        foreach ($items as $it) {
            $variantId = $it['variant_id'] ?? null;
            if (is_int($variantId) && $variantId <= 0) $variantId = null;

            $attrs = $it['variant_attributes_json'] ?? null;
            if (is_array($attrs)) $attrs = json_encode($attrs, JSON_UNESCAPED_UNICODE);
            if (is_string($attrs)) {
                $attrs = trim($attrs);
                if ($attrs === '') $attrs = null;
            }

            $params = [
                ':order_id' => $orderId,
                ':product_id' => (int)($it['product_id'] ?? 0),
                ':variant_id' => $variantId,
                ':qty' => (int)($it['qty'] ?? 0),
                ':price' => isset($it['price']) ? (int)$it['price'] : null,
                ':unit_list_price' => isset($it['unit_list_price']) ? (int)$it['unit_list_price'] : null,
                ':unit_cost' => isset($it['unit_cost']) ? (int)$it['unit_cost'] : null,
                ':product_sku' => $it['product_sku'] ?? null,
                ':product_name' => $it['product_name'] ?? null,

                ':variant_sku' => $it['variant_sku'] ?? null,
                ':variant_title' => $it['variant_title'] ?? null,
                ':variant_attributes_json' => $attrs,
            ];

            if ($hasVatRate) $params[':vat_rate'] = isset($it['vat_rate']) ? (float)$it['vat_rate'] : null;
            if ($hasUnitNet) $params[':unit_net'] = isset($it['unit_net']) ? (int)$it['unit_net'] : null;
            if ($hasUnitVat) $params[':unit_vat'] = isset($it['unit_vat']) ? (int)$it['unit_vat'] : null;
            if ($hasLineNet) $params[':line_net'] = isset($it['line_net']) ? (int)$it['line_net'] : null;
            if ($hasLineVat) $params[':line_vat'] = isset($it['line_vat']) ? (int)$it['line_vat'] : null;

            $st->execute($params);
        }
    }
}

