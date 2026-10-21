<?php
namespace App\Services\Admin\Products;

use PDO;
use Throwable;

final class ProductPricingService
{
    public function __construct(private PDO $db) {}

    /**
     * Actualiza IVA del producto y recalcula precios manteniendo el markup.
     * Devuelve payload HTTP-like: ['status'=>int, 'body'=>array]
     */
    public function updateIvaAndReprice(int $productId, string $rawIvaRate): array
    {
        if ($productId <= 0) {
            return ['status' => 422, 'body' => ['ok' => false, 'message' => 'ID inválido.']];
        }

        $raw = trim($rawIvaRate);
        if ($raw === '') {
            return ['status' => 422, 'body' => ['ok' => false, 'message' => 'IVA requerido.']];
        }

        $raw = str_replace(',', '.', $raw);
        $new = (float)$raw;
        $new = ($new > 1.0) ? ($new / 100.0) : $new;
        if ($new < 0 || $new > 1.0) {
            return ['status' => 422, 'body' => ['ok' => false, 'message' => 'IVA inválido.']];
        }

        try {
            $this->db->beginTransaction();

            $stP = $this->db->prepare('SELECT id, sale_mode, iva_rate, price, list_price FROM products WHERE id = :id');
            $stP->execute([':id' => $productId]);
            $p = $stP->fetch(PDO::FETCH_ASSOC);
            if (!$p) {
                $this->db->rollBack();
                return ['status' => 404, 'body' => ['ok' => false, 'message' => 'Producto no encontrado.']];
            }

            $old = (float)($p['iva_rate'] ?? 0.0);
            $old = ($old > 1.0) ? ($old / 100.0) : $old;
            if ($old < 0) {
                $old = 0.0;
            }

            if (abs($old - $new) < 0.000001) {
                $this->db->commit();
                return ['status' => 200, 'body' => ['ok' => true, 'message' => 'IVA sin cambios.']];
            }

            $stUp = $this->db->prepare('UPDATE products SET iva_rate = :iva WHERE id = :id');
            $stUp->execute([':iva' => $new, ':id' => $productId]);

            $mul = (1.0 + $new) / (1.0 + $old);

            if (($p['sale_mode'] ?? 'simple') === 'simple') {
                $curPrice = $p['price'] !== null ? (float)$p['price'] : null;
                $curList = $p['list_price'] !== null ? (float)$p['list_price'] : null;
                $newPrice = $curPrice !== null ? round($curPrice * $mul) : null;
                $newList = $curList !== null ? round($curList * $mul) : null;
                $stPS = $this->db->prepare('UPDATE products SET price = :p, list_price = :pl, price_updated_at = NOW() WHERE id = :id');
                $stPS->execute([
                    ':p' => $newPrice,
                    ':pl' => $newList,
                    ':id' => $productId,
                ]);
            } else {
                $stV = $this->db->prepare('SELECT id, price, list_price FROM product_variants WHERE product_id = :pid');
                $stV->execute([':pid' => $productId]);
                $rows = $stV->fetchAll(PDO::FETCH_ASSOC) ?: [];

                if (!empty($rows)) {
                    $stVU = $this->db->prepare('UPDATE product_variants SET price = :p, list_price = :pl, price_updated_at = NOW() WHERE id = :id');
                    foreach ($rows as $v) {
                        $vid = (int)($v['id'] ?? 0);
                        if ($vid <= 0) {
                            continue;
                        }
                        $curPrice = $v['price'] !== null ? (float)$v['price'] : null;
                        $curList = $v['list_price'] !== null ? (float)$v['list_price'] : null;
                        $newPrice = $curPrice !== null ? round($curPrice * $mul) : null;
                        $newList = $curList !== null ? round($curList * $mul) : null;
                        $stVU->execute([
                            ':p' => $newPrice,
                            ':pl' => $newList,
                            ':id' => $vid,
                        ]);
                    }
                }
            }

            $this->db->commit();

            try {
                \App\Support\Audit::log('product_iva_updated', 'product', $productId, [
                    'old' => $old,
                    'new' => $new,
                    'mul' => $mul,
                    'product_id' => $productId,
                ]);
                cache_forget('feed:audit_recent8');
            } catch (Throwable $_) {
                // Best effort: la auditoria no debe romper el flujo.
            }

            return [
                'status' => 200,
                'body' => [
                    'ok' => true,
                    'message' => 'IVA guardado y precios recalculados.',
                    'old' => $old,
                    'new' => $new,
                    'mul' => $mul,
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['status' => 500, 'body' => ['ok' => false, 'message' => 'Error al guardar IVA.']];
        }
    }
}
