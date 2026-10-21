<?php
namespace App\Services\Admin\Audit;

use PDO;

/**
 * Snapshot de estado actual de entidad para UX en /admin/audit/show.
 *
 * Reglas:
 * - Whitelist entity_type -> tabla.
 * - NO asumir columnas: introspección con SHOW COLUMNS.
 */
final class AuditSnapshotService
{
    public function getEntitySnapshot(PDO $db, string $entityType, int $entityId)
    {
        $map = [
            'brand' => 'brands',
            'category' => 'categories',
            'order' => 'orders',
            'product' => 'products',
        ];

        if (!isset($map[$entityType])) {
            return '__UNSUPPORTED__';
        }

        $table = (string)$map[$entityType];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return '__UNSUPPORTED__';
        }

        $candidates = match ($entityType) {
            'brand' => ['name', 'slug', 'is_active', 'active', 'status', 'created_at', 'updated_at'],
            'category' => ['name', 'slug', 'parent_id', 'is_active', 'active', 'status', 'created_at', 'updated_at'],
            'order' => ['status', 'total', 'grand_total', 'subtotal', 'shipping_total', 'discount_total', 'payment_method', 'payment_status', 'paid_at', 'created_at', 'updated_at'],
            'product' => ['name', 'sku', 'price', 'status', 'is_active', 'active', 'published', 'created_at', 'updated_at'],
            default => [],
        };

        $cols = [];
        try {
            $stCols = $db->query("SHOW COLUMNS FROM `$table`");
            $rows = $stCols ? ($stCols->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($rows as $r) {
                $f = (string)($r['Field'] ?? '');
                if ($f !== '') $cols[$f] = true;
            }
        } catch (\Throwable $e) {
            return [];
        }

        $select = [];
        foreach ($candidates as $c) {
            if (isset($cols[$c])) $select[] = $c;
        }

        $sqlSelect = !empty($select)
            ? ('`' . implode('`,`', $select) . '`')
            : '`id`';

        try {
            $st = $db->prepare("SELECT $sqlSelect FROM `$table` WHERE id = :id LIMIT 1");
            $st->bindValue(':id', $entityId, PDO::PARAM_INT);
            $st->execute();
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
        } catch (\Throwable $e) {
            return [];
        }

        if (empty($select)) {
            return [];
        }

        $out = [];
        foreach ($select as $c) {
            $out[$c] = $row[$c] ?? null;
        }

        return $out;
    }
}
