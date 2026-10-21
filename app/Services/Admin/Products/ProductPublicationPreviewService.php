<?php
namespace App\Services\Admin\Products;

use PDO;
use Throwable;

final class ProductPublicationPreviewService
{
    public function __construct(private PDO $db)
    {
    }

    /** @param array<string,mixed> $product @param array<int,array<string,mixed>> $imgs @param array<string,mixed>|null $wizardStatus */
    public function build(int $productId, array $product, array $imgs, int $variantsCount, ?array $wizardStatus): array
    {
        $saleMode = (string)($product['sale_mode'] ?? 'simple');
        $price = $product['price'] ?? null;

        $warnings = [];
        $metaImagesTotal = null;
        $metaImagesGeneral = null;
        $blockers = [];
        $publishable = null;
        if (is_array($wizardStatus)) {
            $warnings = (array)($wizardStatus['warnings_globales'] ?? []);
            $blockers = (array)($wizardStatus['blockers_globales'] ?? []);
            $publishable = (bool)($wizardStatus['publishable'] ?? false);
            $meta = (array)($wizardStatus['meta'] ?? []);
            $metaImagesTotal = (int)($meta['images_count'] ?? 0);
            $metaImagesGeneral = (int)($meta['images_general_count'] ?? $metaImagesTotal);
        }

        $coverPath = '';
        $coverThumb = '';
        if (!empty($imgs)) {
            $cover = null;
            foreach ($imgs as $im) {
                if ((int)($im['is_cover'] ?? 0) === 1) {
                    $cover = $im;
                    break;
                }
            }
            if (!$cover) {
                $cover = $imgs[0];
            }
            $coverPath = (string)($cover['path'] ?? '');
            $coverThumb = $this->resolveThumbPath($coverPath);
        }

        $coverUrl = '';
        if ($coverThumb !== '') {
            $coverUrl = base_url('/' . $coverThumb);
        } elseif ($coverPath !== '') {
            $coverUrl = base_url('/' . $coverPath);
        }

        if ($coverUrl === '' && $productId > 0) {
            $fallback = $this->findAnyProductImage($productId);
            if ($fallback !== null) {
                $coverPath = (string)($fallback['path'] ?? '');
                $coverThumb = $this->resolveThumbPath($coverPath);
                if ($coverThumb !== '') {
                    $coverUrl = base_url('/' . $coverThumb);
                } elseif ($coverPath !== '') {
                    $coverUrl = base_url('/' . $coverPath);
                }
            }
        }

        $previewPrice = null;
        if ($saleMode === 'simple') {
            $previewPrice = is_numeric($price) ? (float)$price : null;
        } elseif ($saleMode === 'variants' && $variantsCount > 0 && $productId > 0) {
            $previewPrice = $this->findMinSellableVariantPrice($productId);
        }

        return [
            'warnings' => $warnings,
            'metaImagesTotal' => $metaImagesTotal,
            'metaImagesGeneral' => $metaImagesGeneral,
            'blockers' => $blockers,
            'publishable' => $publishable,
            'coverUrl' => $coverUrl,
            'previewPrice' => $previewPrice,
        ];
    }

    private function resolveThumbPath(string $coverPath): string
    {
        if ($coverPath === '') return '';
        $base = preg_replace('/\.(jpe?g|png|webp)$/i', '', $coverPath);
        if (!$base) return '';
        foreach ([$base . '_thumb.webp', $base . '_thumb.jpg', $base . '_thumb.jpeg'] as $cand) {
            if (is_file(base_path('public_html/' . $cand))) {
                return $cand;
            }
        }
        return '';
    }

    /** @return array<string,mixed>|null */
    private function findAnyProductImage(int $productId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT path, is_cover, variant_id
                 FROM product_images
                 WHERE product_id = :pid
                 ORDER BY (variant_id IS NULL) DESC, is_cover DESC, sort ASC, id ASC
                 LIMIT 1"
            );
            $stmt->execute([':pid' => $productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function findMinSellableVariantPrice(int $productId): ?float
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT MIN(pv.price) AS min_price
                 FROM product_variants pv
                 LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
                 WHERE pv.product_id = ?
                   AND pv.price IS NOT NULL AND pv.price > 0
                   AND COALESCE(iv.stock,0) > 0"
            );
            $stmt->execute([$productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['min_price'] === null) {
                return null;
            }
            return (float)$row['min_price'];
        } catch (Throwable $e) {
            return null;
        }
    }
}
