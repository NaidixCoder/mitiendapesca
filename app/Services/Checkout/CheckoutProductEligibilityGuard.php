<?php
declare(strict_types=1);

namespace App\Services\Checkout;

use App\Services\Catalog\ProductPublicVisibilityService;
use PDO;
use RuntimeException;

/**
 * B1 de COMPRA: no confía en carrito/sesión/intents antiguos.
 * Dentro de una transacción bloquea settings -> árbol por ID -> productos
 * -> variantes, en orden determinístico. Compartido con las transiciones
 * de Categorías, que bloquean el árbol ascendente FOR UPDATE.
 */
final class CheckoutProductEligibilityGuard
{
    public const ERROR = 'B1_UNAVAILABLE: Hay productos que ya no están disponibles. Revisá tu carrito y quitá esos artículos antes de comprar.';

    public function __construct(private PDO $db) {}

    /** @return array{0:bool,1:string,2:list<int>} */
    public function checkItems(array $items, bool $lock = false): array
    {
        if ($lock && !$this->db->inTransaction()) {
            throw new RuntimeException('B1 requiere una transacción activa para confirmar el pedido.');
        }
        $ids = [];
        $variants = [];
        foreach ($items as $it) {
            if (!is_array($it)) return [false,self::ERROR,[]];
            $pid = (int)($it['product_id'] ?? $it['id'] ?? 0);
            $vid = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);
            $qty = (int)($it['qty'] ?? $it['quantity'] ?? 0);
            if ($pid <= 0 || $vid < 0 || $qty <= 0) return [false,self::ERROR,[]];
            $ids[$pid] = true;
            if ($vid > 0) $variants[$vid] = $pid;
        }
        if ($ids === []) return [false,self::ERROR,[]];
        $productIds = array_keys($ids);
        sort($productIds,SORT_NUMERIC);

        if ($lock) {
            // Bloquear el flag antes que categorías; UPDATE del flag espera.
            $setting = $this->db->query(
                "SELECT setting_value FROM app_settings WHERE setting_key = 'catalog_hide_inactive_category_products' FOR UPDATE"
            )->fetchColumn();
            $hide = (string)$setting === '1';
            $nodes = [];
            if ($hide) {
                // Mismo orden FOR UPDATE que la política Admin de Categorías.
                $rows = $this->db->query(
                    'SELECT id,parent_id,is_active FROM categories ORDER BY id FOR UPDATE'
                )->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $nodes[(int)$row['id']] = [
                        'parent' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
                        'active' => (int)$row['is_active'] === 1,
                    ];
                }
            }
            $ph = implode(',',array_fill(0,count($productIds),'?'));
            $st = $this->db->prepare(
                "SELECT id,category_id,is_active FROM products WHERE id IN ($ph) ORDER BY id FOR UPDATE"
            );
            $st->execute($productIds);
            $products = [];
            foreach($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $p) $products[(int)$p['id']] = $p;
            $invalid = [];
            foreach ($productIds as $pid) {
                $p = $products[$pid] ?? null;
                if (!$p || (int)$p['is_active'] !== 1) {
                    $invalid[] = $pid;
                    continue;
                }
                if (!$hide) continue;
                $at = (int)($p['category_id'] ?? 0);
                $seen = [];
                while (true) {
                    if ($at <= 0 || isset($seen[$at]) || !isset($nodes[$at]) || !$nodes[$at]['active']) {
                        $invalid[] = $pid;
                        break;
                    }
                    $seen[$at] = true;
                    $parent = $nodes[$at]['parent'];
                    if ($parent === null) break;
                    $at = (int)$parent;
                }
            }
        } else {
            // Misma política B1 aprobada de 6B-2, sobre los IDs del carrito.
            $allowed = (new ProductPublicVisibilityService($this->db))->eligibleIdsAmong($productIds);
            $invalid = array_values(array_diff($productIds,array_keys($allowed)));
        }

        if ($variants !== []) {
            $variantIds = array_keys($variants);
            sort($variantIds,SORT_NUMERIC);
            $ph = implode(',',array_fill(0,count($variantIds),'?'));
            $sql = "SELECT id,product_id,is_active FROM product_variants WHERE id IN ($ph) ORDER BY id"
                .($lock ? ' FOR UPDATE' : '');
            $st = $this->db->prepare($sql);
            $st->execute($variantIds);
            $found = [];
            foreach($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $v) {
                $vid = (int)$v['id'];
                if ((int)$v['is_active'] === 1 && (int)$v['product_id'] === $variants[$vid]) $found[$vid]=true;
            }
            foreach($variantIds as $vid) if(!isset($found[$vid])) $invalid[]=$variants[$vid];
        }
        $invalid = array_values(array_unique($invalid));
        return $invalid === [] ? [true,'OK',[]] : [false,self::ERROR,$invalid];
    }

    /** @return array<int,bool> IDs comprables entre los entregados, en una sola lectura. */
    public function eligibleProductIds(array $items): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn(array $it): int => (int)($it['product_id'] ?? $it['id'] ?? 0),
            array_filter($items,'is_array')
        ),static fn(int $v): bool => $v>0)));
        if ($ids === []) return [];
        return (new ProductPublicVisibilityService($this->db))->eligibleIdsAmong($ids);
    }
}
