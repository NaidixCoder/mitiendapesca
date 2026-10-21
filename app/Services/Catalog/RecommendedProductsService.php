<?php

namespace App\Services\Catalog;

use App\Repositories\Analytics\AnalyticsReportRepository;
use App\Repositories\Catalog\ProductRepository;
use PDO;

final class RecommendedProductsService
{
    private ProductRepository $products;
    private ProductCardViewModelService $cards;

    public function __construct(private PDO $db)
    {
        $this->products = new ProductRepository($db);
        $this->cards = new ProductCardViewModelService();
    }

    /**
     * @param array<string,mixed> $product
     * @return array<int,array<string,mixed>>
     */
    public function forProduct(array $product, int $min = 5, int $max = 6): array
    {
        $productId = (int)($product['id'] ?? 0);
        if ($productId <= 0) {
            return [];
        }

        $rows = $this->products->findRelatedFill(
            $productId,
            (int)($product['category_id'] ?? 0),
            isset($product['brand_id']) ? (int)$product['brand_id'] : null,
            $min,
            $max
        );

        return $this->cards->buildMany($rows);
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<int,array<string,mixed>>
     */
    public function forCart(array $cart, int $min = 5, int $max = 6): array
    {
        return $this->forProductIds($this->extractProductIds($cart), $min, $max);
    }

    /**
     * @param array<int,int> $productIds
     * @return array<int,array<string,mixed>>
     */
    public function forProductIds(array $productIds, int $min = 5, int $max = 6): array
    {
        $rows = $this->products->findRelatedForCart($productIds, $min, $max);
        return $this->cards->buildMany($rows);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function forGlobal(int $min = 5, int $max = 6): array
    {
        $related = [];
        $seenIds = [];

        try {
            $analytics = new AnalyticsReportRepository($this->db);
            $seedIds = [];

            foreach ($analytics->topProductsViewToCart(30, 12) as $row) {
                $productId = (int)($row['product_id'] ?? 0);
                if ($productId > 0) {
                    $seedIds[] = $productId;
                }
            }

            foreach ($analytics->topAddToCart(30, 12) as $row) {
                $productId = (int)($row['product_id'] ?? 0);
                if ($productId > 0) {
                    $seedIds[] = $productId;
                }
            }

            foreach ($analytics->topProducts(30, 12) as $row) {
                $productId = (int)($row['product_id'] ?? 0);
                if ($productId > 0) {
                    $seedIds[] = $productId;
                }
            }

            $seedIds = array_values(array_unique($seedIds));
            if ($seedIds !== []) {
                $rows = $this->products->findVisibleByIdsOrdered($seedIds, $max);
                foreach ($this->cards->buildMany($rows) as $card) {
                    $productId = (int)($card['id'] ?? 0);
                    if ($productId <= 0 || isset($seenIds[$productId])) {
                        continue;
                    }
                    $seenIds[$productId] = true;
                    $related[] = $card;
                    if (count($related) >= $max) {
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback silencioso: si analytics no está disponible, caemos al criterio global simple.
        }

        if (count($related) < $min) {
            foreach ($this->forProductIds([], $min, $max) as $card) {
                $productId = (int)($card['id'] ?? 0);
                if ($productId <= 0 || isset($seenIds[$productId])) {
                    continue;
                }
                $seenIds[$productId] = true;
                $related[] = $card;
                if (count($related) >= $max) {
                    break;
                }
            }
        }

        return array_slice($related, 0, $max);
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<int,int>
     */
    private function extractProductIds(array $cart): array
    {
        $items = is_array($cart['items'] ?? null) ? $cart['items'] : [];
        $productIds = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
            if ($productId > 0) {
                $productIds[] = $productId;
            }
        }

        return array_values(array_unique($productIds));
    }
}
