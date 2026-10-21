<?php
namespace App\Services\Seo;

use PDO;

class SeoAuditService
{
    public function __construct(private PDO $db) {}

    /**
     * Auditoría orientada a ADMIN (no para buscadores).
     * Devuelve score + checklist + preview (título/descr/URL).
     */
    public function auditForAdmin(?array $product, array $images = []): array
    {
        $product = $product ?? [];
        $id = (int)($product['id'] ?? 0);

        $name = trim((string)($product['name'] ?? ''));
        $slug = trim((string)($product['slug'] ?? ''));

        $metaTitle = trim((string)($product['meta_title'] ?? ''));
        $metaDesc  = trim((string)($product['meta_description'] ?? ''));

        // Pricing/stock (coincide con lógica SEO JSON-LD)
        $pricing = $this->computePricing((int)$id, (string)($product['sale_mode'] ?? 'simple'), (int)($product['price'] ?? 0));

        $hasCover = false;
        foreach ($images as $img) {
            if (!empty($img['is_cover'])) { $hasCover = true; break; }
        }

        $url = $this->buildPublicUrl($slug);

        // Checklist
        $items = [];

        $items[] = $this->item('titulo', 'Título del producto', $name !== '', $name !== '' ? 'OK' : 'Falta nombre.');

        $items[] = $this->item('slug', 'URL (slug)', $slug !== '', $slug !== '' ? 'OK' : 'Falta slug.');

        $items[] = $this->item(
            'meta_title',
            'Meta title',
            $metaTitle !== '' && mb_strlen($metaTitle) >= 25 && mb_strlen($metaTitle) <= 60,
            $metaTitle === '' ? 'Falta. Se usará el nombre (menos control).' : (mb_strlen($metaTitle) > 60 ? 'Muy largo (recomendado ≤ 60).' : 'OK')
        );

        $items[] = $this->item(
            'meta_desc',
            'Meta description',
            $metaDesc !== '' && mb_strlen($metaDesc) >= 70 && mb_strlen($metaDesc) <= 160,
            $metaDesc === '' ? 'Falta. Se usará la descripción corta/automática.' : (mb_strlen($metaDesc) > 160 ? 'Muy larga (recomendado ≤ 160).' : 'OK')
        );

        $items[] = $this->item(
            'imagen',
            'Imagen principal',
            $hasCover,
            $hasCover ? 'OK' : 'No hay portada (impacta CTR).'
        );

        $items[] = $this->item(
            'precio',
            'Precio',
            ($pricing['low'] > 0),
            ($pricing['low'] > 0) ? 'OK' : 'Precio en 0. No se recomienda indexar ofertas sin precio.'
        );

        // Scoring simple (ponderado)
        $score = 0;
        $score += ($name !== '') ? 15 : 0;
        $score += ($slug !== '') ? 10 : 0;
        $score += ($items[2]['status'] === 'ok') ? 20 : 10; // meta title
        $score += ($items[3]['status'] === 'ok') ? 20 : 10; // meta desc
        $score += ($hasCover) ? 20 : 0;
        $score += ($pricing['low'] > 0) ? 15 : 0;
        $score = min(100, $score);

        // Preview
        $previewTitle = $metaTitle !== '' ? $metaTitle : ($name !== '' ? ($name . ' | Mi Pesca & Aventura') : 'Producto | Mi Pesca & Aventura');
        $previewDesc  = $metaDesc !== '' ? $metaDesc : ($name !== '' ? $name : 'Producto');

        return [
            'score' => $score,
            'preview' => [
                'title' => $previewTitle,
                'url' => $url,
                'description' => $previewDesc,
                'meta_title_len' => mb_strlen($metaTitle),
                'meta_desc_len'  => mb_strlen($metaDesc),
            ],
            'pricing' => $pricing,
            'items' => $items,
        ];
    }

    /**
     * Auditoría para Categorías (ADMIN).
     */
    public function auditCategoryForAdmin(array $category): array
    {
        return $this->auditEntityForAdmin('category', $category);
    }

    /**
     * Auditoría para Marcas (ADMIN).
     */
    public function auditBrandForAdmin(array $brand): array
    {
        return $this->auditEntityForAdmin('brand', $brand);
    }

    private function item(string $key, string $label, bool $ok, string $detail): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $ok ? 'ok' : 'warn',
            'detail' => $detail,
        ];
    }

    private function auditEntityForAdmin(string $entityType, array $entity): array
    {
        $name = trim((string)($entity['name'] ?? ''));
        $slug = trim((string)($entity['slug'] ?? ''));

        $metaTitle = trim((string)($entity['meta_title'] ?? ''));
        $metaDesc  = trim((string)($entity['meta_description'] ?? ''));
        $seoText   = trim((string)($entity['seo_text'] ?? ''));

        $titleL = mb_strlen($metaTitle);
        $descL  = mb_strlen($metaDesc);
        $textL  = mb_strlen($seoText);

        $url = $this->buildEntityPublicUrl($entityType, $slug);

        $items = [];
        $items[] = $this->item('titulo', 'Nombre', $name !== '', $name !== '' ? 'OK' : 'Falta nombre.');
        $items[] = $this->item('slug', 'URL (slug)', $slug !== '', $slug !== '' ? 'OK' : 'Falta slug.');

        $items[] = $this->item(
            'meta_title',
            'Meta title',
            $metaTitle !== '' && $titleL >= 25 && $titleL <= 60,
            $metaTitle === '' ? 'Falta. Se usará el nombre (menos control).' : ($titleL > 60 ? 'Muy largo (≤ 60).' : ($titleL < 25 ? 'Muy corto (≥ 25).' : 'OK'))
        );

        $items[] = $this->item(
            'meta_desc',
            'Meta description',
            $metaDesc !== '' && $descL >= 70 && $descL <= 160,
            $metaDesc === '' ? 'Falta. Se generará automática.' : ($descL > 160 ? 'Muy larga (≤ 160).' : ($descL < 70 ? 'Muy corta (≥ 70).' : 'OK'))
        );

        $items[] = $this->item(
            'seo_text',
            'Texto SEO',
            $textL >= 120,
            $seoText === '' ? 'Opcional, pero recomendado (mejora ranking / long tail).' : ($textL < 120 ? 'Muy corto (recomendado ≥ 120).' : 'OK')
        );

        // Scoring simple (0-100)
        $score = 0;
        $score += ($name !== '') ? 20 : 0;
        $score += ($slug !== '') ? 15 : 0;
        $score += ($items[2]['status'] === 'ok') ? 20 : 10; // meta title
        $score += ($items[3]['status'] === 'ok') ? 20 : 10; // meta desc

        if ($seoText === '') $score += 0;
        else if ($textL >= 120) $score += 25;
        else $score += 10;

        $score = min(100, $score);

        $previewTitle = $metaTitle !== '' ? $metaTitle : ($name !== '' ? ($name . ' | Mi Pesca & Aventura') : 'Mi Pesca & Aventura');
        $previewDesc  = $metaDesc !== '' ? $metaDesc : ($seoText !== '' ? mb_substr($seoText, 0, 160) : ($name !== '' ? $name : ''));

        return [
            'score' => $score,
            'preview' => [
                'title' => $previewTitle,
                'url' => $url,
                'description' => $previewDesc,
                'meta_title_len' => $titleL,
                'meta_desc_len'  => $descL,
                'seo_text_len'   => $textL,
            ],
            'items' => $items,
        ];
    }

    private function buildPublicUrl(string $slug): string
    {
        // Usamos helper base_url() si existe, si no dejamos relativo seguro.
        if (function_exists('base_url')) {
            return rtrim(base_url('/'), '/') . '/producto?slug=' . rawurlencode($slug);
        }
        return '/producto?slug=' . rawurlencode($slug);
    }

    private function buildEntityPublicUrl(string $entityType, string $slug): string
    {
        $path = ($entityType === 'brand') ? '/marca?slug=' : '/categoria?slug=';
        if (function_exists('base_url')) {
            return rtrim(base_url('/'), '/') . $path . rawurlencode($slug);
        }
        return $path . rawurlencode($slug);
    }

    private function computePricing(int $productId, string $saleMode, int $priceCents): array
    {
        if ($saleMode === 'variants') {
            $sql = "SELECT
                        MIN(CASE WHEN v.price > 0 THEN v.price END) AS min_price,
                        MAX(CASE WHEN v.price > 0 THEN v.price END) AS max_price,
                        SUM(COALESCE(iv.stock,0)) AS total_stock
                    FROM product_variants v
                    LEFT JOIN inventory_variants iv ON iv.variant_id = v.id
                    WHERE v.product_id = ? AND v.is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$productId]);
            $row = (array)$stmt->fetch(PDO::FETCH_ASSOC);

            $min = (int)($row['min_price'] ?? 0);
            $max = (int)($row['max_price'] ?? 0);

            return [
                'mode' => 'variants',
                'low'  => $min > 0 ? ($min / 100) : 0,
                'high' => $max > 0 ? ($max / 100) : ($min > 0 ? ($min / 100) : 0),
                'total_stock' => (int)($row['total_stock'] ?? 0),
            ];
        }

        return [
            'mode' => 'simple',
            'low'  => $priceCents > 0 ? ($priceCents / 100) : 0,
            'high' => $priceCents > 0 ? ($priceCents / 100) : 0,
            'total_stock' => null,
        ];
    }
}