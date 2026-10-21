<?php
namespace App\Repositories\Catalog;

use PDO;

final class ProductAttributeValueRepository
{
    public function __construct(private PDO $db) {}

    public function ensure(int $productId, int $attributeId, int $valueId): bool
    {
        $sql = "INSERT IGNORE INTO product_attribute_values (product_id, attribute_id, value_id)
                VALUES (:pid, :aid, :vid)";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute([
            ':pid' => $productId,
            ':aid' => $attributeId,
            ':vid' => $valueId,
        ]);
    }
}

