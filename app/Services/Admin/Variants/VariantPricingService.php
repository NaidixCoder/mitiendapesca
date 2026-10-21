<?php
namespace App\Services\Admin\Variants;

use App\Repositories\Catalog\VariantPricingRepository;
use PDO;

final class VariantPricingService
{
    public function __construct(private PDO $db)
    {
    }

    /** @return array<string,mixed>|null */
    public function getSnapshot(int $variantId): ?array
    {
        $repo = new VariantPricingRepository($this->db);
        return $repo->findSnapshot($variantId);
    }

    public function savePricing(int $variantId, array $data, int $userId = null): void
    {
        $productId = (int)($data['product_id'] ?? 0);
        if ($variantId <= 0 || $productId <= 0) {
            throw new \RuntimeException('Variante invalida para pricing.');
        }

        $price = $this->normalizeNullableMoney($data['price'] ?? null);
        $listPrice = $this->normalizeNullableMoney($data['list_price'] ?? null);
        $cost = $this->normalizeNullableMoney($data['cost'] ?? null);
        $marginPct = $this->normalizeNullableRate($data['margin_pct'] ?? null);

        $repo = new VariantPricingRepository($this->db);
        $repo->updatePricing($productId, $variantId, [
            'price' => $price,
            'list_price' => $listPrice,
            'cost' => $cost,
            'margin_pct' => $marginPct,
        ]);
    }

    private function normalizeNullableMoney(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (float)$value;
        if ($n < 0) {
            $n = 0;
        }
        return (int)round($n);
    }

    private function normalizeNullableRate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (float)$value;
        if ($n < 0) {
            $n = 0.0;
        }
        return $n;
    }
}

