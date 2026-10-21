<?php
namespace App\Services\Admin\Inventory\ViewModels;

final class InventoryIndexViewModel
{
    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>
     */
    public function build(string $q, array $items): array
    {
        $totals = [
            'items' => count($items),
            'stock_units' => 0,
            'capital' => 0,
            'capital_final' => 0,
            'venta' => 0,
        ];

        $rowsPrepared = [];
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }

            $itemType = (string)($it['item_type'] ?? '');
            $name = (string)($it['product_name'] ?? '');
            $variantTitle = (string)($it['variant_title'] ?? '');
            $stock = (int)($it['stock'] ?? 0);
            $cost = (int)($it['cost'] ?? 0);
            $price = (int)($it['price'] ?? 0);
            $ivaRate = (float)($it['iva_rate'] ?? 0);

            $costFinal = (int) round($cost * (1.0 + $ivaRate));
            $capitalNeto = $stock * $cost;
            $capitalFinal = $stock * $costFinal;

            $totals['stock_units'] += $stock;
            $totals['capital'] += $capitalNeto;
            $totals['capital_final'] += $capitalFinal;
            $totals['venta'] += ($stock * $price);

            $desc = $name;
            if ($itemType === 'variant') {
                $desc = $name . ($variantTitle !== '' ? ' - ' . $variantTitle : ' - Variante');
            }

            $rowsPrepared[] = [
                'sku' => (string)($it['sku'] ?? ''),
                'description' => $desc,
                'type_label' => $itemType === 'variant' ? 'Variante' : 'Producto simple',
                'stock' => $stock,
                'cost' => $cost,
                'iva_rate_percent' => rtrim(rtrim(number_format($ivaRate * 100, 2, '.', ''), '0'), '.') . '%',
                'cost_final' => $costFinal,
                'price' => $price,
                'capital_neto' => $capitalNeto,
                'capital_final' => $capitalFinal,
            ];
        }

        $totals['margen_final'] = max(0, $totals['venta'] - $totals['capital_final']);
        $totals['margen_neto'] = max(0, $totals['venta'] - $totals['capital']);

        return [
            'q' => $q,
            'items_prepared' => $rowsPrepared,
            'totals' => $totals,
        ];
    }
}
