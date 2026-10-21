<?php
namespace App\Services\Admin\Products\ViewModels;

final class ProductIndexViewModel
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    public function build(array $rows, string $query, int $page, int $pages, int $total): array
    {
        return [
            'query' => $query,
            'page' => max(1, $page),
            'pages' => max(1, $pages),
            'total' => max(0, $total),
            'rows_prepared' => $this->prepareRows($rows),
            'pager_links' => $this->buildPagerLinks($query, max(1, $pages), max(1, $page)),
            'variants_base_url' => url('/admin/products/variants'),
            'products_base_url' => url('/admin/products'),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $saleMode = (string)($row['sale_mode'] ?? 'simple');
            $variantsCount = (int)($row['variants_count'] ?? 0);
            $isVariants = ($saleMode === 'variants');

            $skuText = (string)($row['sku'] ?? '');
            $skuEmpty = ($skuText === '');
            if ($isVariants) {
                $skuText = (string)($row['variant_sku_any'] ?? '');
                $skuEmpty = ($skuText === '');
            }

            $priceText = '';
            if ($isVariants) {
                $minPrice = $row['price_from_cache'] ?? null;
                if ($minPrice === null || $minPrice === '') {
                    $priceText = 'Precio a consultar';
                } else {
                    $priceText = 'Desde $' . number_format((int)$minPrice, 0, ',', '.');
                }
            } else {
                $price = $row['price'] ?? null;
                $priceText = '$' . number_format((int)($price ?? 0), 0, ',', '.');
            }

            $updatedAt = '';
            $updatedAtRaw = $isVariants
                ? ($row['variant_price_updated_at'] ?? null)
                : ($row['price_updated_at'] ?? null);
            if (!empty($updatedAtRaw) && function_exists('dt_local_from_utc')) {
                $updatedAt = (string)dt_local_from_utc((string)$updatedAtRaw, 'Y-m-d H:i');
            }

            $variantsUrl = '';
            if ($isVariants && $variantsCount > 0) {
                $variantsUrl = url('/admin/products/variants') . '?' . http_build_query(['product_id' => $id]);
            }

            $isActiveRaw = !empty($row['is_active']);
            $stockSimple = (int)($row['stock_simple'] ?? 0);
            $vendibleVariantsCount = (int)($row['vendible_variants_count'] ?? 0);
            $isSellable = $isVariants ? ($vendibleVariantsCount > 0) : ($stockSimple > 0);
            $isActive = $isActiveRaw && $isSellable;
            $statusBadgeClass = 'badge-muted';
            $statusLabel = 'inactivo';
            if ($isActive) {
                $statusBadgeClass = 'badge-ok';
                $statusLabel = 'activo';
            }
            $out[] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
                'sku_text' => $skuText,
                'sku_empty' => $skuEmpty,
                'price_text' => $priceText,
                'price_consult' => ($priceText === 'Precio a consultar'),
                'updated_at_text' => $updatedAt,
                'updated_at_empty' => ($updatedAt === ''),
                'variants_count' => $variantsCount,
                'variants_url' => $variantsUrl,
                'has_variants_link' => ($variantsUrl !== ''),
                'is_active' => $isActive,
                'status_badge_class' => $statusBadgeClass,
                'status_label' => $statusLabel,
                'edit_url' => url('/admin/products/edit?id=' . $id),
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildPagerLinks(string $query, int $pages, int $currentPage): array
    {
        $links = [];
        for ($p = 1; $p <= $pages; $p++) {
            $params = array_filter([
                'q' => $query,
                'page' => $p,
            ], static fn($v) => $v !== '' && $v !== null);

            $links[] = [
                'page' => $p,
                'url' => url('/admin/products') . '?' . http_build_query($params),
                'active' => ($p === $currentPage),
            ];
        }
        return $links;
    }
}
