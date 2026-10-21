<?php
namespace App\Repositories\Catalog;

use PDO;

final class ProductAttributeRepository
{
    public function __construct(private PDO $db) {}

    public function ensure(int $productId, int $attributeId, int $sort = 0, int $isRequired = 0): bool
    {
        $sql = "INSERT IGNORE INTO product_attributes (product_id, attribute_id, sort, is_required)
                VALUES (:pid, :aid, :sort, :req)";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute([
            ':pid' => $productId,
            ':aid' => $attributeId,
            ':sort'=> $sort,
            ':req' => $isRequired,
        ]);
    }
}

