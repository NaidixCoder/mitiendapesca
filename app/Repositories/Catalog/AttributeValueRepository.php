<?php
namespace App\Repositories\Catalog;

use PDO;

final class AttributeValueRepository
{
    public function __construct(private PDO $db) {}

    /** @return array{id:int,attribute_id:int,value:string,sort:int}|null */
    public function findByAttributeAndValue(int $attributeId, string $value): ?array
    {
        $attributeId = (int)$attributeId;
        $value = trim($value);
        if ($attributeId <= 0 || $value === '') return null;
        $st = $this->db->prepare("SELECT id, attribute_id, value, sort FROM attribute_values WHERE attribute_id=? AND value=? LIMIT 1");
        $st->execute([$attributeId, $value]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        return [
            'id' => (int)$r['id'],
            'attribute_id' => (int)$r['attribute_id'],
            'value' => (string)$r['value'],
            'sort' => (int)($r['sort'] ?? 0),
        ];
    }

    public function insert(int $attributeId, string $value, int $sort = 0): int
    {
        $attributeId = (int)$attributeId;
        $value = trim($value);
        if ($attributeId <= 0 || $value === '') return 0;
        $st = $this->db->prepare("INSERT INTO attribute_values (attribute_id, value, sort) VALUES (?,?,?)");
        $ok = $st->execute([$attributeId, $value, (int)$sort]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }
}

