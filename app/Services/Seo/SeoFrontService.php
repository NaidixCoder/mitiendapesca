<?php
namespace App\Services\Seo;

use App\Repositories\Seo\SeoSettingsRepository;
use PDO;

/**
 * SEO PRO — Lote 2
 *
 * Construye un array $seo listo para renderizar en el <head>:
 * - title, description, canonical
 * - robots ("index,follow" | "noindex,follow" ...)
 * - OpenGraph/Twitter (og_*, twitter_*)
 * - jsonld (string con scripts JSON-LD)
 *
 * Además, respeta overrides guardados en `seo_settings`.
 */
final class SeoFrontService
{
    private SeoSettingsRepository $settingsRepo;

    public function __construct(private PDO $db)
    {
        $this->settingsRepo = new SeoSettingsRepository($db);
    }

    /**
     * SEO para página de producto.
     *
     * @param array $product fila de products + opcional: images[], brand_name, category_name
     * @param array $trail  breadcrumbs [['label'=>, 'url'=>], ...]
     */
    public function buildProduct(array $product, array $trail): array
    {
        $id   = (int)($product['id'] ?? 0);
        $slug = (string)($product['slug'] ?? '');

        $title = trim((string)($product['meta_title'] ?? ''));
        if ($title === '') $title = (string)($product['name'] ?? 'Producto');
        if (!str_contains($title, '|')) $title .= ' | Mi Pesca & Aventura';

        $description = trim((string)($product['meta_description'] ?? ''));
        if ($description === '') {
            $raw = (string)($product['short_desc'] ?? $product['long_desc'] ?? '');
            $raw = trim(strip_tags($raw));
            $description = $this->truncate($raw, 160);
        }

        // Canonical (con query slug)
        $canonical = function_exists('url')
            ? url('/producto?slug=' . urlencode($slug))
            : ('/producto?slug=' . urlencode($slug));

        // OG/Twitter image: portada si existe
        $images = $product['images'] ?? [];
        $ogImage = null;
        if (is_array($images) && !empty($images[0])) {
            $ogImage = function_exists('public_url') ? public_url($images[0]) : (string)$images[0];
        }

        // Precio/stock (simple o variantes)
        $pricing = $this->computeProductPricing($id, (string)($product['sale_mode'] ?? 'simple'));

        $seo = [
            'type'        => 'product',
            'title'       => $title,
            'description' => $description,
            'canonical'   => $canonical,
            'robots'      => 'index,follow',

            'og_title'       => $title,
            'og_description' => $description,
            'og_image'       => $ogImage,

            'twitter_card'        => 'summary_large_image',
            'twitter_title'       => $title,
            'twitter_description' => $description,
            'twitter_image'       => $ogImage,
        ];

        // JSON-LD: Product + Breadcrumbs
        $seo['jsonld'] = $this->jsonLdProduct($product, $seo, $pricing, $trail);

        // FAQ schema (si hay FAQs completas)
        $faqLd = $this->jsonLdFaq($product);
        if ($faqLd) {
            $seo['jsonld'] .= "\n" . $this->wrapJsonLd(json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }


        // Overrides desde seo_settings
        $settings = $this->settingsRepo->get('product', $id);
        if ($settings) {
            $seo = $this->applySettings($seo, $settings);

            // schema_override reemplaza JSON-LD si viene (string JSON)
            $override = trim((string)($settings['schema_override'] ?? ''));
            if ($override !== '') {
                $seo['jsonld'] = $this->wrapJsonLd($override);
            }
        }

        return $seo;
    }

    /**
     * Aplica `seo_settings` sobre el array base.
     */
    public function applySettings(array $seo, array $settingsRow): array
    {
        // canonical
        $canonical = trim((string)($settingsRow['canonical_url'] ?? ''));
        if ($canonical !== '') {
            // Si es relativa, la convertimos a absoluta usando base_url.
            if (!preg_match('#^https?://#i', $canonical) && function_exists('base_url')) {
                $canonical = base_url('/' . ltrim($canonical, '/'));
            }
            $seo['canonical'] = $canonical;
        }

        // robots
        $idx  = isset($settingsRow['robots_index']) ? (int)$settingsRow['robots_index'] : null;
        $fol  = isset($settingsRow['robots_follow']) ? (int)$settingsRow['robots_follow'] : null;
        if ($idx !== null || $fol !== null) {
            $seo['robots'] = ($idx === 0 ? 'noindex' : 'index') . ',' . ($fol === 0 ? 'nofollow' : 'follow');
        }

        // OpenGraph
        foreach (['og_title','og_description'] as $k) {
            $v = trim((string)($settingsRow[$k] ?? ''));
            if ($v !== '') $seo[$k] = $v;
        }
        $ogPath = trim((string)($settingsRow['og_image_path'] ?? ''));
        if ($ogPath !== '') {
            $seo['og_image'] = function_exists('public_url') ? public_url($ogPath) : $ogPath;
        }

        // Twitter
        $card = trim((string)($settingsRow['twitter_card'] ?? ''));
        if ($card !== '') $seo['twitter_card'] = $card;
        foreach (['twitter_title','twitter_description'] as $k) {
            $v = trim((string)($settingsRow[$k] ?? ''));
            if ($v !== '') $seo[$k] = $v;
        }
        $twPath = trim((string)($settingsRow['twitter_image_path'] ?? ''));
        if ($twPath !== '') {
            $seo['twitter_image'] = function_exists('public_url') ? public_url($twPath) : $twPath;
        }

        return $seo;
    }


/** Construye SEO para landing de categoría (/categoria?slug=...). */
public function buildCategory(array $category, array $trail = []): array
{
    $id   = (int)($category['id'] ?? 0);
    $slug = trim((string)($category['slug'] ?? ''));
    $name = trim((string)($category['name'] ?? 'Categoría'));

    $title = trim((string)($category['meta_title'] ?? ''));
    if ($title === '') {
        $title = $this->truncate($name, 70);
    }

    $description = trim((string)($category['meta_description'] ?? ''));
    if ($description === '') {
        $raw = (string)($category['seo_text'] ?? '');
        $raw = trim(strip_tags($raw));
        $description = $raw !== '' ? $this->truncate($raw, 160) : $this->truncate('Productos de pesca en ' . $name . '.', 160);
    }

    $canonical = function_exists('url')
        ? url('/categoria?slug=' . urlencode($slug))
        : ('/categoria?slug=' . urlencode($slug));

    $seo = [
        'type'        => 'category',
        'title'       => $title,
        'description' => $description,
        'canonical'   => $canonical,
        'robots'      => 'index,follow',

        'og_title'       => $title,
        'og_description' => $description,
        'og_image'       => null,

        'twitter_card'        => 'summary_large_image',
        'twitter_title'       => $title,
        'twitter_description' => $description,
        'twitter_image'       => null,
    ];

    // JSON-LD: CollectionPage + Breadcrumbs
    $faqLd = $this->jsonLdFaq($category);
            $seo['jsonld'] = $this->jsonLdCollectionPage($name, $seo, $trail, $faqLd);

    // Overrides desde seo_settings
    $settingsRow = $this->settingsRepo->get('category', $id) ?: [];
    $seo = $this->applyAdvancedSettings($seo, $settingsRow);

    // schema_override reemplaza JSON-LD si viene (string JSON)
    $override = trim((string)($settingsRow['schema_override'] ?? ''));
    if ($override !== '') {
        $seo['jsonld'] = $this->wrapJsonLd($override);
    }

    return $seo;
}

/** Construye SEO para landing de marca (/marca?slug=...). */
public function buildBrand(array $brand, array $trail = []): array
{
    $id   = (int)($brand['id'] ?? 0);
    $slug = trim((string)($brand['slug'] ?? ''));
    $name = trim((string)($brand['name'] ?? 'Marca'));

    $title = trim((string)($brand['meta_title'] ?? ''));
    if ($title === '') {
        $title = $this->truncate($name, 70);
    }

    $description = trim((string)($brand['meta_description'] ?? ''));
    if ($description === '') {
        $raw = (string)($brand['seo_text'] ?? '');
        $raw = trim(strip_tags($raw));
        $description = $raw !== '' ? $this->truncate($raw, 160) : $this->truncate('Productos de pesca de la marca ' . $name . '.', 160);
    }

    $canonical = function_exists('url')
        ? url('/marca?slug=' . urlencode($slug))
        : ('/marca?slug=' . urlencode($slug));

    $seo = [
        'type'        => 'brand',
        'title'       => $title,
        'description' => $description,
        'canonical'   => $canonical,
        'robots'      => 'index,follow',

        'og_title'       => $title,
        'og_description' => $description,
        'og_image'       => null,

        'twitter_card'        => 'summary_large_image',
        'twitter_title'       => $title,
        'twitter_description' => $description,
        'twitter_image'       => null,
    ];

    $faqLd = $this->jsonLdFaq($brand);
            $seo['jsonld'] = $this->jsonLdCollectionPage($name, $seo, $trail, $faqLd);

    $settingsRow = $this->settingsRepo->get('brand', $id) ?: [];
    $seo = $this->applyAdvancedSettings($seo, $settingsRow);

    $override = trim((string)($settingsRow['schema_override'] ?? ''));
    if ($override !== '') {
        $seo['jsonld'] = $this->wrapJsonLd($override);
    }

    return $seo;
}

/** Aplica canonical/robots/social desde una fila de seo_settings. */
private function applyAdvancedSettings(array $seo, array $settingsRow): array
{
    // Canonical
    $canonical = trim((string)($settingsRow['canonical_url'] ?? ''));
    if ($canonical !== '') {
        if (!preg_match('~^https?://~i', $canonical)) {
            $canonical = function_exists('base_url') ? base_url('/' . ltrim($canonical, '/')) : ('/' . ltrim($canonical, '/'));
        }
        $seo['canonical'] = $canonical;
    }

    // Robots
    $idx = (int)($settingsRow['robots_index'] ?? 1);
    $fol = (int)($settingsRow['robots_follow'] ?? 1);
    $seo['robots'] = ($idx ? 'index' : 'noindex') . ',' . ($fol ? 'follow' : 'nofollow');

    // OpenGraph
    foreach (['og_title','og_description'] as $k) {
        $v = trim((string)($settingsRow[$k] ?? ''));
        if ($v !== '') $seo[$k] = $v;
    }
    $ogPath = trim((string)($settingsRow['og_image_path'] ?? ''));
    if ($ogPath !== '') {
        $seo['og_image'] = function_exists('public_url') ? public_url($ogPath) : $ogPath;
    }

    // Twitter
    $card = trim((string)($settingsRow['twitter_card'] ?? ''));
    if ($card !== '') $seo['twitter_card'] = $card;
    foreach (['twitter_title','twitter_description'] as $k) {
        $v = trim((string)($settingsRow[$k] ?? ''));
        if ($v !== '') $seo[$k] = $v;
    }
    $twPath = trim((string)($settingsRow['twitter_image_path'] ?? ''));
    if ($twPath !== '') {
        $seo['twitter_image'] = function_exists('public_url') ? public_url($twPath) : $twPath;
    }

    return $seo;
}

/**
 * JSON-LD base para landings (CollectionPage) + Breadcrumbs.
 * @return array<int, array<string,mixed>>
 */

/**
 * JSON-LD para landings: CollectionPage + Breadcrumbs (+ FAQ opcional).
 */
private function jsonLdCollectionPage(string $name, array $seo, array $trail, ?array $faqLd = null): string
{
    $pageNode = [
        '@type'       => 'CollectionPage',
        'name'        => $name,
        'description' => (string)($seo['description'] ?? ''),
        'url'         => (string)($seo['canonical'] ?? ''),
    ];

    $graph = [$pageNode];

    $bc = $this->jsonLdBreadcrumbNode($trail);
    if ($bc) $graph[] = $bc;

    if (is_array($faqLd)) {
        // jsonLdFaq() trae @context; en @graph no lo necesitamos
        $faqNode = $faqLd;
        unset($faqNode['@context']);
        $graph[] = $faqNode;
    }

    $json = json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return '<script type="application/ld+json">' . ($json ?: '{}') . '</script>';
}

/**
 * BreadcrumbList node para @graph.
 * $trail: array<int, array{name:string,url:string}>
 */
private function jsonLdBreadcrumbNode(array $trail): ?array
{
    if (empty($trail)) return null;
    $items = [];
    $pos = 1;
    foreach ($trail as $t) {
        $name = trim((string)($t['name'] ?? ''));
        $url  = trim((string)($t['url']  ?? ''));
        if ($name === '' || $url === '') continue;
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => $name,
            'item'     => $url,
        ];
    }
    if (empty($items)) return null;
    return [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}
    /* ===================== Internals ===================== */

    private function truncate(string $s, int $max): string
    {
        $s = trim($s);
        if ($s === '') return '';
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($s) > $max ? (mb_substr($s, 0, $max - 3) . '...') : $s;
        }
        return strlen($s) > $max ? (substr($s, 0, $max - 3) . '...') : $s;
    }

    /**
     * Devuelve info de precio/stock para schema.org.
     * @return array{mode:string, currency:string, in_stock:bool, price:?float, low_price:?float, high_price:?float, offer_count:int}
     */


    /**
     * Genera JSON-LD FAQPage si hay FAQs completas.
     * @return array<string,mixed>|null
     */
    private function jsonLdFaq(array $product): ?array
    {
        $faq = $product['seo_faq'] ?? null;
        if (!is_array($faq)) return null;

        $main = [];
        foreach ($faq as $item) {
            $q = trim((string)($item['q'] ?? ''));
            $a = trim((string)($item['a'] ?? ''));
            if ($q === '' || $a === '') continue;
            $main[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
            ];
        }
        if (empty($main)) return null;

        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $main];
    }
    private function computeProductPricing(int $productId, string $mode): array
    {
        $currency = 'ARS';

        if ($mode !== 'variants') {
            $st = $this->db->prepare('SELECT p.price, i.stock FROM products p LEFT JOIN inventory i ON i.product_id = p.id WHERE p.id = :id LIMIT 1');
            $st->execute([':id' => $productId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $price = isset($row['price']) ? ((float)$row['price'] / 100.0) : null;
            $stock = isset($row['stock']) ? (int)$row['stock'] : 0;
            return [
                'mode'        => 'simple',
                'currency'    => $currency,
                'in_stock'    => $stock > 0,
                'price'       => $price,
                'low_price'   => null,
                'high_price'  => null,
                'offer_count' => 1,
            ];
        }

        // Variantes: aggregate
        $st = $this->db->prepare('
            SELECT
              MIN(v.price) AS min_price,
              MAX(v.price) AS max_price,
              COUNT(*)      AS cnt,
              SUM(CASE WHEN iv.stock > 0 THEN 1 ELSE 0 END) AS in_stock_cnt
            FROM product_variants v
            LEFT JOIN inventory_variants iv ON iv.variant_id = v.id
            WHERE v.product_id = :pid AND v.is_active = 1 AND v.price IS NOT NULL
        ');
        $st->execute([':pid' => $productId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $min = isset($row['min_price']) && $row['min_price'] !== null ? ((float)$row['min_price'] / 100.0) : null;
        $max = isset($row['max_price']) && $row['max_price'] !== null ? ((float)$row['max_price'] / 100.0) : null;
        $cnt = (int)($row['cnt'] ?? 0);
        $in  = (int)($row['in_stock_cnt'] ?? 0);
        return [
            'mode'        => 'variants',
            'currency'    => $currency,
            'in_stock'    => $in > 0,
            'price'       => $min,
            'low_price'   => $min,
            'high_price'  => $max,
            'offer_count' => $cnt,
        ];
    }

    private function jsonLdProduct(array $product, array $seo, array $pricing, array $trail): string
    {
        $name = (string)($product['name'] ?? '');
        $desc = (string)($seo['description'] ?? '');
        $sku  = (string)($product['sku'] ?? '');
        $brand = trim((string)($product['brand_name'] ?? ''));
        $category = trim((string)($product['category_name'] ?? ''));
        $images = $product['images'] ?? [];
        $imgUrls = [];
        if (is_array($images)) {
            foreach ($images as $p) {
                $u = $p ? (function_exists('public_url') ? public_url($p) : (string)$p) : '';
                if ($u) $imgUrls[] = $u;
                if (count($imgUrls) >= 6) break; // cap
            }
        }

        $offers = null;
        if ($pricing['mode'] === 'variants') {
            if ($pricing['low_price'] !== null) {
                $offers = [
                    '@type'         => 'AggregateOffer',
                    'priceCurrency' => $pricing['currency'],
                    'lowPrice'      => $pricing['low_price'],
                    'highPrice'     => $pricing['high_price'] ?? $pricing['low_price'],
                    'offerCount'    => (int)$pricing['offer_count'],
                    'availability'  => $pricing['in_stock'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url'           => (string)($seo['canonical'] ?? ''),
                ];
            }
        } else {
            if ($pricing['price'] !== null) {
                $offers = [
                    '@type'         => 'Offer',
                    'priceCurrency' => $pricing['currency'],
                    'price'         => $pricing['price'],
                    'availability'  => $pricing['in_stock'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url'           => (string)($seo['canonical'] ?? ''),
                ];
            }
        }

        $productLd = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $name,
            'description' => $desc,
            'url'         => (string)($seo['canonical'] ?? ''),
        ];
        if ($sku !== '') $productLd['sku'] = $sku;
        if ($brand !== '') $productLd['brand'] = ['@type' => 'Brand', 'name' => $brand];
        if ($category !== '') $productLd['category'] = $category;
        if ($imgUrls) $productLd['image'] = $imgUrls;
        if ($offers) $productLd['offers'] = $offers;

        $crumbItems = [];
        $pos = 1;
        foreach ($trail as $it) {
            $label = (string)($it['label'] ?? '');
            $url   = (string)($it['url'] ?? '');
            if ($label === '' || $url === '') continue;
            $crumbItems[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => $label,
                'item'     => $url,
            ];
        }
        $breadcrumbsLd = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbItems,
        ];

        return $this->wrapJsonLd(
            json_encode($breadcrumbsLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ) . "\n" . $this->wrapJsonLd(
            json_encode($productLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private function wrapJsonLd(string $json): string
    {
        $json = trim($json);
        if ($json === '') return '';
        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
