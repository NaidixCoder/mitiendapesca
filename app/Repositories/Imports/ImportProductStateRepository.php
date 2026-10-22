<?php
namespace App\Repositories\Imports;

use PDO;

/** Snapshot del padre que VariantImporter modifica (modo y caché Desde). */
final class ImportProductStateRepository
{
    public function __construct(private PDO $db) {}

    public function captureVariantParent(int $productId): array
    {
        if ($productId <= 0 || !$this->db->inTransaction()) {
            throw new \LogicException('Captura de padre requiere transacción activa.');
        }
        $st=$this->db->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');
        $st->execute([$productId]);
        $row=$st->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) throw new \RuntimeException('Padre no encontrado en snapshot.');
        foreach (['sale_mode','price_from_cache','list_price_from_cache'] as $field) {
            if (!array_key_exists($field,$row)) {
                throw new \RuntimeException('Esquema incompatible: falta '.$field.' en products.');
            }
        }
        return ['entity'=>'product','action'=>'update','id'=>$productId,
            'before'=>[
                'sale_mode'=>$row['sale_mode'],
                'price_from_cache'=>$row['price_from_cache'],
                'list_price_from_cache'=>$row['list_price_from_cache'],
            ]];
    }
}
