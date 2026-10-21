<?php
declare(strict_types=1);

namespace App\Services\Cart;

use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Pricing\CommercialPriceService;
use PDO;

final class CartViewModelService
{
    private CommercialPriceService $pricing;

    public function __construct(private PDO $db)
    {
        $this->pricing = new CommercialPriceService(new AppSettingsRepository($db));
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<string,mixed>
     */
    public function build(array $cart): array
    {
        $items = [];
        foreach ((array)($cart['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = $this->mapItem($item);
        }

        return [
            'items' => $items,
            'subtotal' => (int)($cart['subtotal'] ?? 0),
            'total_qty' => (int)($cart['total_qty'] ?? 0),
            'items_count' => count($items),
        ];
    }

    /**
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    private function mapItem(array $item): array
    {
        $transferPrice = isset($item['price']) ? (int)$item['price'] : 0;
        $listPrice = isset($item['list_price']) ? (int)$item['list_price'] : 0;
        $qty = max(0, (int)($item['qty'] ?? 0));
        $stock = isset($item['stock']) ? (int)$item['stock'] : 0;
        $isActive = !empty($item['is_active']);

        $financed = $this->pricing->resolveFinancedPrice($transferPrice, $listPrice);
        $transfer = $this->pricing->resolveTransferPrice($transferPrice);
        $discountPct = $this->pricing->discountPercent($transfer, $financed);
        $financedLine = $financed * $qty;
        $transferLine = $transfer * $qty;

        $item['display_financed_price'] = $financed;
        $item['display_transfer_price'] = $transfer;
        $item['display_financed_line_price'] = $financedLine;
        $item['display_transfer_line_price'] = $transferLine;
        $item['display_discount_pct'] = $discountPct;
        $item['display_financed_label'] = money($financed);
        $item['display_transfer_label'] = money($transfer);
        $item['display_financed_unit_label'] = money($financed);
        $item['display_transfer_unit_label'] = money($transfer);
        $item['display_financed_line_label'] = money($financedLine);
        $item['display_transfer_line_label'] = money($transferLine);
        $item['is_selectable'] = ($isActive && $stock > 0);

        return $item;
    }
}
