<?php
namespace App\Services\Catalog;

use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Pricing\CommercialPriceService;

final class ProductCardViewModelService
{
    /**
     * @param array<string,mixed> $product
     * @return array<string,mixed>
     */
    public function build(array $product): array
    {
        $p = $this->normalize($product);

        $name = trim((string)($p['name'] ?? $p['title'] ?? $p['nombre'] ?? ''));
        if ($name === '') {
            $name = 'Producto';
        }

        $slug = trim((string)($p['slug'] ?? ''));
        $href = base_url('producto');
        if ($slug !== '') {
            $href .= '?slug=' . rawurlencode($slug);
        }

        $img = $this->resolveImage($p);

        $publicBase = $p['price'] ?? $p['price_from'] ?? $p['min_price'] ?? 0;
        $publicBaseInt = is_numeric($publicBase) ? (int)$publicBase : 0;
        $transferPrice = pricing_unit_price($p, null, $publicBaseInt);

        $compare = $p['price_compare'] ?? $p['list_price'] ?? $p['compare_at_price'] ?? null;
        $financedPrice = is_numeric($compare) ? (int)$compare : $transferPrice;
        $saleMode = (string)($p['sale_mode'] ?? 'simple');
        $hasVariants = $saleMode === 'variants';

        $pricing = new CommercialPriceService(new AppSettingsRepository(db()));
        $discountPct = $pricing->discountPercent($transferPrice, $financedPrice);
        $financedLabel = money($financedPrice);
        $transferAmountLabel = $transferPrice > 0 ? money($transferPrice) : '';
        $transferNoteLabel = $transferPrice > 0 ? 'con' : '';
        $transferDetailLabel = $transferPrice > 0 ? 'transferencia' : '';
        $installmentAmount = $pricing->installmentAmount($financedPrice);
        $installmentLabel = $installmentAmount > 0 ? ($pricing->installmentsCount() . ' cuotas') : '';
        $installmentDetailLabel = $installmentAmount > 0 ? ('sin interes de ' . money($installmentAmount)) : '';
        $inStock = !array_key_exists('in_stock', $p) || !empty($p['in_stock']);

        return [
            'id' => (int)($p['id'] ?? 0),
            'name' => $name,
            'href' => $href,
            'image' => $img,
            'brand_name' => trim((string)($p['brand_name'] ?? '')),
            'category_name' => trim((string)($p['category_name'] ?? '')),
            'price_label' => $financedLabel,
            'transfer_label' => $transferAmountLabel,
            'transfer_note_label' => $transferNoteLabel,
            'transfer_detail_label' => $transferDetailLabel,
            'installment_label' => $installmentLabel,
            'installment_detail_label' => $installmentDetailLabel,
            'has_offer' => $discountPct > 0,
            'discount_pct' => $discountPct,
            'has_variants' => $hasVariants,
            'in_stock' => $inStock,
            'cta_mode' => $hasVariants ? 'quick_add' : 'add_to_cart',
            'back_url' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $products
     * @return array<int,array<string,mixed>>
     */
    public function buildMany(array $products): array
    {
        $rows = [];
        foreach ($products as $product) {
            if (is_array($product)) {
                $rows[] = $this->build($product);
            }
        }

        return $rows;
    }

    /**
     * @param array<string,mixed> $product
     * @return array<string,mixed>
     */
    private function normalize(array $product): array
    {
        if (isset($product[0]) && !isset($product['id'])) {
            return [
                'id' => $product[0] ?? null,
                'slug' => $product[1] ?? null,
                'name' => $product[2] ?? null,
                'price' => $product[4] ?? null,
                'brand_name' => $product[9] ?? null,
                'brand_slug' => $product[10] ?? null,
                'category_name' => $product[11] ?? null,
                'category_slug' => $product[12] ?? null,
                'cover_url' => $product[13] ?? null,
            ];
        }

        return $product;
    }

    /**
     * @param array<string,mixed> $product
     */
    private function resolveImage(array $product): string
    {
        $img = $product['cover_url'] ?? $product['thumb'] ?? $product['image_url'] ?? $product['img_url'] ?? null;
        if (is_string($img) && $img !== '' && !preg_match('~^https?://~i', $img)) {
            $img = base_url(ltrim($img, '/'));
        }

        if (!is_string($img) || trim($img) === '') {
            return asset('img/placeholder.svg');
        }

        return $img;
    }
}
