<?php
namespace App\Repositories\Catalog;

use PDO;
use Throwable;

final class VariantPricingRepository
{
    public function __construct(private PDO $db)
    {
    }

    /** @return array<string,mixed>|null */
    public function findSnapshot(int $variantId): ?array
    {
        if ($variantId <= 0) {
            return null;
        }

        try {
            $st = $this->db->prepare(
                "SELECT id, product_id, price, list_price, cost, margin_pct
                 FROM product_variants
                 WHERE id = :id
                 LIMIT 1"
            );
            $st->execute([':id' => $variantId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($row === null) {
                return null;
            }
            return $row;
        } catch (Throwable $e) {
            // Compat: DB sin list_price o margin_pct
            $st = $this->db->prepare(
                "SELECT id, product_id, price, cost
                 FROM product_variants
                 WHERE id = :id
                 LIMIT 1"
            );
            $st->execute([':id' => $variantId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($row === null) {
                return null;
            }
            $row['list_price'] = null;
            $row['margin_pct'] = null;
            return $row;
        }
    }

    public function updatePricing(int $productId, int $variantId, array $fields): void
    {
        try {
            $up = $this->db->prepare(
                "UPDATE product_variants
                 SET price_updated_at = CASE
                        WHEN (price_updated_at IS NULL AND ((? IS NOT NULL) OR (? IS NOT NULL))) THEN UTC_TIMESTAMP()
                        WHEN ((? IS NOT NULL AND ? <> price) OR (? IS NOT NULL AND ? <> list_price)) THEN UTC_TIMESTAMP()
                        ELSE price_updated_at
                     END,
                     price = ?, list_price = ?, cost = ?, margin_pct = ?
                 WHERE id = ? AND product_id = ?"
            );
            $up->execute([
                $fields['price'],
                ($fields['list_price'] ?? null),
                $fields['price'],
                $fields['price'],
                ($fields['list_price'] ?? null),
                ($fields['list_price'] ?? null),
                $fields['price'],
                ($fields['list_price'] ?? null),
                $fields['cost'],
                ($fields['margin_pct'] ?? null),
                $variantId,
                $productId,
            ]);
            return;
        } catch (Throwable $e) {
            $msg = $e->getMessage();

            if (strpos($msg, 'list_price') !== false) {
                try {
                    $up = $this->db->prepare(
                        "UPDATE product_variants
                         SET price = :price, cost = :cost, margin_pct = :m
                         WHERE id = :id AND product_id = :pid"
                    );
                    $up->execute([
                        ':price' => $fields['price'],
                        ':cost' => $fields['cost'],
                        ':m' => ($fields['margin_pct'] ?? null),
                        ':id' => $variantId,
                        ':pid' => $productId,
                    ]);
                    return;
                } catch (Throwable $e2) {
                    if (strpos($e2->getMessage(), 'margin_pct') === false) {
                        throw $e2;
                    }
                    $up = $this->db->prepare(
                        "UPDATE product_variants
                         SET price = :price, cost = :cost
                         WHERE id = :id AND product_id = :pid"
                    );
                    $up->execute([
                        ':price' => $fields['price'],
                        ':cost' => $fields['cost'],
                        ':id' => $variantId,
                        ':pid' => $productId,
                    ]);
                    return;
                }
            }

            if (strpos($msg, 'margin_pct') !== false) {
                $up = $this->db->prepare(
                    "UPDATE product_variants
                     SET price_updated_at = CASE
                            WHEN (price_updated_at IS NULL AND ((? IS NOT NULL) OR (? IS NOT NULL))) THEN UTC_TIMESTAMP()
                            WHEN ((? IS NOT NULL AND ? <> price) OR (? IS NOT NULL AND ? <> list_price)) THEN UTC_TIMESTAMP()
                            ELSE price_updated_at
                         END,
                         price = ?, list_price = ?, cost = ?
                     WHERE id = ? AND product_id = ?"
                );
                $up->execute([
                    $fields['price'],
                    ($fields['list_price'] ?? null),
                    $fields['price'],
                    $fields['price'],
                    ($fields['list_price'] ?? null),
                    ($fields['list_price'] ?? null),
                    $fields['price'],
                    ($fields['list_price'] ?? null),
                    $fields['cost'],
                    $variantId,
                    $productId,
                ]);
                return;
            }

            throw $e;
        }
    }
}

