<?php
namespace App\Support\Admin;

use PDO;
use Throwable;

final class AttributesSchema
{
    /** @var array<string,bool> */
    private static array $columnCache = [];

    public static function hasColumn(PDO $db, string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            $dbName = (string)($db->query('SELECT DATABASE()')->fetchColumn() ?: '');
            if ($dbName !== '') {
                $st = $db->prepare(
                    "SELECT 1
                       FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                        AND COLUMN_NAME = ?
                      LIMIT 1"
                );
                $st->execute([$dbName, $table, $column]);
                $exists = (bool)$st->fetchColumn();
                if ($exists) {
                    return self::$columnCache[$key] = true;
                }
            }

            $st = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $st->execute([$column]);
            return self::$columnCache[$key] = (bool)$st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return self::$columnCache[$key] = false;
        }
    }

    /**
     * @return list<string>
     */
    public static function editableColumns(PDO $db): array
    {
        $columns = ['id', 'name', 'slug', 'type'];
        foreach (['code', 'is_variant_axis', 'unit'] as $column) {
            if (self::hasColumn($db, 'attributes', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public static function normalizeRow(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => trim((string)($row['name'] ?? '')),
            'slug' => trim((string)($row['slug'] ?? '')),
            'type' => trim((string)($row['type'] ?? 'select')),
            'code' => trim((string)($row['code'] ?? '')),
            'is_variant_axis' => !empty($row['is_variant_axis']),
            'unit' => trim((string)($row['unit'] ?? '')),
        ];
    }
}
