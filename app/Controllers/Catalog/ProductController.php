<?php

namespace App\Controllers\Catalog;

use App\Services\Seo\SeoFrontService;
use App\Services\Seo\ProductSeoBlocksService;
use App\Repositories\Account\ProductFavoriteRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Catalog\RecommendedProductsService;
use App\Services\Pricing\CommercialPriceService;
use App\Services\Pricing\PriceService;
use PDO;

final class ProductController
{
    public function show(): void
    {
        $db = db();

        $slug = trim((string)($_GET['slug'] ?? ''));
        if ($slug === '') {
            render_page('errors/404', ['message' => 'Producto no encontrado']);
            return;
        }

        // Producto base
        $st = $db->prepare(
            "SELECT
                p.*,
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                c.slug AS category_slug,
                c.is_active AS category_is_active,
                inv.stock AS stock_simple
             FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN inventory inv ON inv.product_id = p.id
             WHERE p.slug = ?
               AND p.is_active = 1
             LIMIT 1"
        );
        $st->execute([$slug]);
        $product = $st->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$product) {
            render_page('errors/404', ['message' => 'Producto no encontrado']);
            return;
        }

        // Ajuste (modo híbrido): si está ON, ocultar productos cuya categoría esté inactiva.
        // (Productos sin categoría siguen visibles.)
        $settingsTmp = new AppSettingsRepository($db);
        $enforceActiveCategory = ($settingsTmp->get('catalog_hide_inactive_category_products', '0') === '1');
        if ($enforceActiveCategory && !empty($product['category_id']) && (int)($product['category_is_active'] ?? 1) === 0) {
            render_page('errors/404', ['message' => 'Producto no encontrado']);
            return;
        }

        $id = (int)$product['id'];

        // Imágenes (generales + por variante)
        // Nota: en schema la columna es `sort` (no `sort_order`).
        $imgSt = $db->prepare(
            "SELECT path, is_cover, sort, variant_id
             FROM product_images
             WHERE product_id = ?
             ORDER BY is_cover DESC, sort ASC, id ASC"
        );
        $imgSt->execute([$id]);
        $imgs = $imgSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $imagesGeneral = [];
        $imagesByVariant = [];
        foreach ($imgs as $r) {
            $path = (string)($r['path'] ?? '');
            if ($path === '') continue;
            $vid = $r['variant_id'] ?? null;
            if ($vid === null) {
                $imagesGeneral[] = $path;
            } else {
                $k = (string)(int)$vid;
                if (!isset($imagesByVariant[$k])) $imagesByVariant[$k] = [];
                $imagesByVariant[$k][] = $path;
            }
        }
        $product['images_general'] = $imagesGeneral;
        $product['images_by_variant'] = $imagesByVariant;
        // Compat: gallery antigua usa `images`
        $product['images'] = $imagesGeneral;

        // Pricing/stock unificado (en moneda para UI)
        // IMPORTANTE: no pisar el `price` original del producto antes de leerlo.
        // (regresión: productos simples quedaban con 0 y mostraban "Precio a consultar").
        $basePriceCents = (int)($product['price'] ?? 0);
        $saleMode = (string)($product['sale_mode'] ?? 'simple');
        $product['has_range'] = false;
        $product['price_low'] = 0;
        $product['price_high'] = 0;
        $product['price'] = 0;
        $product['in_stock'] = false;
        $product['stock'] = 0;
        $commercialPricing = new CommercialPriceService(new AppSettingsRepository($db));

        if ($saleMode === 'variants') {
            $pst = $db->prepare(
                "SELECT
                    MIN(v.price) AS min_price,
                    MAX(v.price) AS max_price,
                    SUM(COALESCE(iv.stock,0)) AS sum_stock
                 FROM product_variants v
                 LEFT JOIN inventory_variants iv ON iv.variant_id = v.id
                 WHERE v.product_id = ? AND v.is_active = 1 AND v.price IS NOT NULL"
            );
            $pst->execute([$id]);
            $row = $pst->fetch(PDO::FETCH_ASSOC) ?: [];

            $minC = (int)($row['min_price'] ?? 0);
            $maxC = (int)($row['max_price'] ?? 0);
            $stock = (int)($row['sum_stock'] ?? 0);

            $product['has_range'] = ($minC > 0 && $maxC > 0 && $minC !== $maxC);
            $product['price_low'] = $minC > 0 ? $minC : 0;
            $product['price_high'] = $maxC > 0 ? $maxC : 0;
            $product['price'] = $product['price_low'];
            $product['stock'] = $stock;
            $product['in_stock'] = ($stock > 0);

            // Variantes vendibles + atributos (para selector en la ficha)
                        $vExtra = '';
            if (function_exists('db_has_column') && db_has_column('product_variants','price_wh1_override')) $vExtra .= ', v.price_wh1_override';
            if (function_exists('db_has_column') && db_has_column('product_variants','price_wh2_override')) $vExtra .= ', v.price_wh2_override';

            $vst = $db->prepare(
                "SELECT v.id, v.sku, v.title, v.price, v.list_price, COALESCE(iv.stock,0) AS stock{$vExtra}
                 FROM product_variants v
                 LEFT JOIN inventory_variants iv ON iv.variant_id = v.id
                 WHERE v.product_id = ? AND v.is_active = 1
                 ORDER BY v.id ASC"
            );
            $vst->execute([$id]);
            $variants = $vst->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Valores por variante
            $vvalsSt = $db->prepare(
                "SELECT pvv.variant_id,
                        a.id AS attribute_id, a.name AS attribute_name, a.slug AS attribute_slug,
                        av.id AS value_id, av.value AS value_value
                 FROM product_variant_values pvv
                 INNER JOIN attributes a ON a.id = pvv.attribute_id
                 INNER JOIN attribute_values av ON av.id = pvv.value_id
                 WHERE pvv.variant_id IN (SELECT id FROM product_variants WHERE product_id = ? AND is_active = 1)"
            );
            $vvalsSt->execute([$id]);
            $rows = $vvalsSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $attrsByVariant = [];
            foreach ($rows as $r) {
                $vid = (string)(int)($r['variant_id'] ?? 0);
                if ($vid === '0') continue;
                if (!isset($attrsByVariant[$vid])) $attrsByVariant[$vid] = [];
                $attrsByVariant[$vid][] = [
                    'attribute_id' => (int)($r['attribute_id'] ?? 0),
                    'attribute' => (string)($r['attribute_name'] ?? ''),
                    'attribute_slug' => (string)($r['attribute_slug'] ?? ''),
                    'value_id' => (int)($r['value_id'] ?? 0),
                    'value' => (string)($r['value_value'] ?? ''),
                ];
            }

            // Orden de atributos definido en el producto (si existe)
            $paSt = $db->prepare(
                "SELECT a.id, a.name, a.slug
                 FROM product_attributes pa
                 INNER JOIN attributes a ON a.id = pa.attribute_id
                 WHERE pa.product_id = ?
                 ORDER BY pa.sort ASC, a.name ASC"
            );
            $paSt->execute([$id]);
            $attrOrder = $paSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $orderMap = [];
            foreach ($attrOrder as $i => $a) {
                $orderMap[(int)$a['id']] = $i;
            }

            // Precio de display según rol (mayoristas)
            $role = function_exists('current_user_role') ? current_user_role() : 'customer';
            $settingsRepo = new AppSettingsRepository($db);
            $priceSvc = new PriceService($settingsRepo);

            $out = [];
            $minTransferDisplay = null;
            $minFinancedDisplay = null;
            foreach ($variants as $v) {
                $vid = (string)(int)($v['id'] ?? 0);
                if ($vid === '0') continue;

                $priceC = (int)($v['price'] ?? 0);
                $listC  = (int)($v['list_price'] ?? 0);
                $stockV = (int)($v['stock'] ?? 0);

                $attrs = $attrsByVariant[$vid] ?? [];
                // Ordenar atributos si tenemos sort del producto
                usort($attrs, function($a, $b) use ($orderMap) {
                    $ai = $orderMap[$a['attribute_id']] ?? 999;
                    $bi = $orderMap[$b['attribute_id']] ?? 999;
                    if ($ai === $bi) return strcmp((string)$a['attribute'], (string)$b['attribute']);
                    return $ai <=> $bi;
                });

                $label = trim((string)($v['title'] ?? ''));
                if ($label === '' && !empty($attrs)) {
                    $parts = array_map(fn($x) => (string)($x['value'] ?? ''), $attrs);
                    $label = implode(' - ', array_filter($parts, fn($s) => $s !== ''));
                }
                if ($label === '') $label = 'Variante #' . $vid;

                $out[] = [
                    'id' => (int)$vid,
                    'sku' => (string)($v['sku'] ?? ''),
                    'title' => $label,
                    'price_cents' => $priceC,
                    'list_price_cents' => $listC,
                    'price' => $priceC > 0 ? $priceC : 0,
                    'list_price' => $listC > 0 ? $listC : 0,
                    'stock' => $stockV,
                    'in_stock' => ($stockV > 0),
	                'has_images' => !empty($imagesByVariant[$vid] ?? []),
                    'attrs' => $attrs,
                ];

                if ($priceC > 0 && ($minTransferDisplay === null || $priceC < $minTransferDisplay)) {
                    $minTransferDisplay = $priceC;
                }
                $financedCandidate = $listC > 0 ? $listC : $priceC;
                if ($financedCandidate > 0 && ($minFinancedDisplay === null || $financedCandidate < $minFinancedDisplay)) {
                    $minFinancedDisplay = $financedCandidate;
                }
            }

            $product['variants'] = $out;
            $product['display_transfer_price'] = (int)($minTransferDisplay ?? 0);
            $product['display_financed_price'] = (int)($minFinancedDisplay ?? ($minTransferDisplay ?? 0));
        } else {
            $priceC = $basePriceCents;
            $stock  = (int)($product['stock_simple'] ?? 0);

            $product['price'] = $priceC > 0 ? $priceC : 0;
            $product['price_low'] = $product['price'];
            $product['price_high'] = $product['price'];
            $product['stock'] = $stock;
            $product['in_stock'] = ($stock > 0);
            $product['display_transfer_price'] = $product['price'];
            $product['display_financed_price'] = $commercialPricing->resolveFinancedPrice(
                $product['price'],
                (int)($product['list_price'] ?? 0)
            );
        }

        $product['display_discount_pct'] = $commercialPricing->discountPercent(
            (int)($product['display_transfer_price'] ?? 0),
            (int)($product['display_financed_price'] ?? 0)
        );
        $product['display_installment_label'] = $commercialPricing->installmentLabel((int)($product['display_financed_price'] ?? 0));
        $product['display_installments_count'] = $commercialPricing->installmentsCount();
        $product['display_installments_enabled'] = $commercialPricing->isInstallmentsEnabled();

        // Descripción
        $product['description'] = (string)($product['short_desc'] ?? $product['description'] ?? $product['name'] ?? '');

        // SEO blocks (Bullets/Specs/FAQ)
        $blocks = (new ProductSeoBlocksService($db))->getForProduct($id);
        $product['seo_bullets'] = $blocks['bullets'] ?? [];
        $product['seo_specs']   = $blocks['specs'] ?? [];
        $product['seo_faq']     = $blocks['faq'] ?? [];

        // Breadcrumbs
        $trail = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => 'Productos', 'url' => url('/productos')],
        ];
        if (!empty($product['category_name'])) {
            $trail[] = ['name' => (string)$product['category_name'], 'url' => url('/categoria').'?slug='.urlencode((string)($product['category_slug'] ?? ''))];
        }
        $trail[] = ['name' => (string)($product['name'] ?? 'Producto'), 'url' => url('/producto').'?slug='.urlencode($slug)];

        // Related (con fallback para completar un mínimo y evitar secciones "pobres")
        $related = (new RecommendedProductsService($db))->forProduct($product, 5, 6);

        // SEO (head tags)
        $seo = (new SeoFrontService($db))->buildProduct($product, $trail);

        $favoriteActive = false;
        $userId = (int)($_SESSION['uid'] ?? 0);
        if ($userId > 0) {
            $favoriteActive = (new ProductFavoriteRepository($db))->isFavorite($userId, $id);
        }

        // Analíticas: visita a ficha de producto
        try {
            if (class_exists(\App\Services\Analytics\AnalyticsService::class)) {
                (new \App\Services\Analytics\AnalyticsService($db))->trackProductView((int)$id);
            }
        } catch (\Throwable $e) { /* no bloqueante */ }

        render_page('catalogo/producto', [
            'pageId' => 'product',
            'product' => $product,
            'seo' => $seo,
            'trail' => $trail,
            'related' => $related,
            'favoriteActive' => $favoriteActive,
        ]);
    }
}
