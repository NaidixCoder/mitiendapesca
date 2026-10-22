<?php
namespace App\Repositories\Catalog;

use PDO;

final class VariantAttributesRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findAttributeValue(int $valueId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT id, attribute_id, value FROM attribute_values WHERE id = ? LIMIT 1';
        if ($forUpdate && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';
        $st = $this->db->prepare($sql);
        $st->execute([$valueId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row;
    }

    public function countProductSelections(int $valueId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM product_attribute_values WHERE value_id = ?');
        $st->execute([$valueId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function countVariantUsage(int $valueId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM product_variant_values WHERE value_id = ?');
        $st->execute([$valueId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function countSetUsage(int $valueId): int
    {
        // Fallo cerrado: los errores SQL nunca equivalen a ausencia de relaciones.
        $st = $this->db->prepare('SELECT COUNT(*) FROM variant_value_set_items WHERE value_id = ?');
        $st->execute([$valueId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function deleteProductSelections(int $valueId): void
    {
        $st = $this->db->prepare('DELETE FROM product_attribute_values WHERE value_id = ?');
        $st->execute([$valueId]);
    }

    public function deleteSetItems(int $valueId): void
    {
        try {
            $st = $this->db->prepare('DELETE FROM variant_value_set_items WHERE value_id = ?');
            $st->execute([$valueId]);
        } catch (\Throwable $e) {
            // Compat: tabla no presente
        }
    }

    public function deleteAttributeValue(int $valueId, ?int $attributeId = null): bool
    {
        if ($attributeId !== null && $attributeId > 0) {
            $st = $this->db->prepare('DELETE FROM attribute_values WHERE id = ? AND attribute_id = ?');
            $st->execute([$valueId, $attributeId]);
            return $st->rowCount() > 0;
        }

        $st = $this->db->prepare('DELETE FROM attribute_values WHERE id = ?');
        $st->execute([$valueId]);
        return $st->rowCount() > 0;
    }
}

