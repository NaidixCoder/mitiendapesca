<?php
namespace App\Services\Admin\Products;

use App\Repositories\Admin\CatalogTagsRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Repositories\Catalog\SupplierRepository;
use App\Services\Admin\ProductWizardService;
use App\Services\Admin\Variants\VariantReadService;
use App\Services\Seo\ProductSeoBlocksService;
use App\Services\Seo\SeoAuditService;
use App\Services\Seo\SeoManagerService;
use PDO;
use Throwable;

final class ProductFormService
{
    public function __construct(private PDO $db)
    {
    }

    public function buildContext(int $id, array $query = []): array
    {
        $product = null;
        $imgs = [];
        $inv  = ['stock' => 0, 'low_stock_threshold' => 3];
        $variantsCount = 0;
        $saleStatus = null;
        $wizardStatus = null;
        $publicationPreview = [];
        $catalogTagGroups = [];
        $productTagIds = [];

        $imgsImagesTab = [];
        $imgVariants = [];
        $imgVariantId = 0;
        $imgFilterVariantId = null;
        $imagesVariantSupported = false;
        $imgScopesImages = [];

        try {
            if ($id > 0) {
                $st = $this->db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
                $st->execute([':id' => $id]);
                $product = $st->fetch(PDO::FETCH_ASSOC) ?: null;

                $imagesVariantSupported = $this->imagesVariantScopeSupported($this->db);
                $imgVariantId = (int)($query['img_variant_id'] ?? 0);

                if ($imagesVariantSupported) {
                    $im = $this->db->prepare("SELECT id, path, alt, is_cover, sort FROM product_images WHERE product_id=:id AND variant_id IS NULL ORDER BY is_cover DESC, sort ASC, id ASC");
                } else {
                    $im = $this->db->prepare("SELECT id, path, alt, is_cover, sort FROM product_images WHERE product_id=:id ORDER BY is_cover DESC, sort ASC, id ASC");
                }
                $im->execute([':id' => $id]);
                $imgs = $im->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $iv = $this->db->prepare("SELECT stock, low_stock_threshold FROM inventory WHERE product_id=:id");
                $iv->execute([':id' => $id]);
                $inv = $iv->fetch(PDO::FETCH_ASSOC) ?: $inv;

                $vc = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id=:id");
                $vc->execute([':id' => $id]);
                $variantsCount = (int)$vc->fetchColumn();
                if ($variantsCount > 0 && is_array($product)) {
                    $product['sale_mode'] = 'variants';
                }

                if ($variantsCount > 0) {
                    $stVars = $this->db->prepare("SELECT id, sku, title FROM product_variants WHERE product_id = :id ORDER BY id ASC");
                    $stVars->execute([':id' => $id]);
                    $imgVariants = $stVars->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    if ($imgVariantId > 0) {
                        $belongs = false;
                        foreach ($imgVariants as $v) {
                            if ((int)($v['id'] ?? 0) === $imgVariantId) {
                                $belongs = true;
                                break;
                            }
                        }
                        if (!$belongs) {
                            $imgVariantId = 0;
                        }
                    }
                } else {
                    $imgVariantId = 0;
                }

                $rawFilter = trim((string)($query['img_filter_variant_id'] ?? ''));
                if ($rawFilter === '' || $rawFilter === 'all') {
                    $imgFilterVariantId = null;
                } else {
                    $tmp = (int)$rawFilter;
                    if ($tmp > 0) {
                        $belongs = false;
                        foreach ($imgVariants as $v) {
                            if ((int)($v['id'] ?? 0) === $tmp) {
                                $belongs = true;
                                break;
                            }
                        }
                        $imgFilterVariantId = $belongs ? $tmp : null;
                    } else {
                        $imgFilterVariantId = 0;
                    }
                }

                if ($imagesVariantSupported) {
                    $im2 = $this->db->prepare(
                        "SELECT id, path, alt, is_cover, sort, variant_id
                         FROM product_images
                         WHERE product_id = :id
                         ORDER BY (variant_id IS NULL) DESC, variant_id ASC, sort ASC, id ASC"
                    );
                    $im2->execute([':id' => $id]);
                    $imgsImagesTab = $im2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } else {
                    $imgsImagesTab = $imgs;
                }

                $imgsImagesTabFiltered = $imgsImagesTab;
                if ($imagesVariantSupported && $imgFilterVariantId !== null) {
                    if ($imgFilterVariantId === 0) {
                        $imgsImagesTabFiltered = array_values(array_filter($imgsImagesTab, fn($r) => ($r['variant_id'] ?? null) === null));
                    } else {
                        $imgsImagesTabFiltered = array_values(array_filter($imgsImagesTab, fn($r) => (int)($r['variant_id'] ?? 0) === (int)$imgFilterVariantId));
                    }
                }

                if ($imagesVariantSupported) {
                    try {
                        $stAll = $this->db->prepare(
                            "SELECT id, path, alt, is_cover, sort, variant_id
                             FROM product_images
                             WHERE product_id = :id
                             ORDER BY (variant_id IS NULL) DESC, variant_id ASC, is_cover DESC, sort ASC, id ASC"
                        );
                        $stAll->execute([':id' => $id]);
                        $allRows = $stAll->fetchAll(PDO::FETCH_ASSOC) ?: [];

                        $imgScopesImages = [0 => []];
                        foreach ($allRows as $r) {
                            $vid = ($r['variant_id'] ?? null);
                            $key = ($vid === null || $vid === '' ? 0 : (int)$vid);
                            if (!isset($imgScopesImages[$key])) {
                                $imgScopesImages[$key] = [];
                            }
                            $imgScopesImages[$key][] = $r;
                        }
                    } catch (Throwable $e) {
                        $imgScopesImages = [0 => $imgs];
                        if (function_exists('log_error')) {
                            log_error('ProductFormService imgScopesImages: ' . $e->getMessage());
                        }
                    }
                } else {
                    $imgScopesImages = [0 => $imgs];
                }

                $saleStatus = [
                    'mode' => (string)($product['sale_mode'] ?? 'simple'),
                    'ready' => false,
                    'badge' => 'Pendiente',
                    'message' => '',
                    'checks' => [],
                ];

                $mode = $saleStatus['mode'];
                if ($variantsCount > 0) {
                    $mode = 'variants';
                    $saleStatus['mode'] = 'variants';
                }

                if ($mode === 'simple') {
                    $sku = trim((string)($product['sku'] ?? ''));
                    $price = (int)($product['price'] ?? 0);
                    $stock = (int)($inv['stock'] ?? 0);

                    $hasSku = ($sku !== '');
                    $hasPrice = ($price > 0);
                    $hasStock = ($stock > 0);

                    $saleStatus['checks'] = [
                        ['label' => 'SKU cargado', 'ok' => $hasSku],
                        ['label' => 'Precio mayor a 0', 'ok' => $hasPrice],
                        ['label' => 'Stock mayor a 0', 'ok' => $hasStock],
                    ];

                    $saleStatus['ready'] = ($hasSku && $hasPrice && $hasStock);
                    $saleStatus['badge'] = $saleStatus['ready'] ? 'Vendible' : 'Pendiente';
                    $saleStatus['message'] = $saleStatus['ready']
                        ? 'Producto simple listo para vender.'
                        : 'Completá SKU, precio y stock para poder vender.';
                } else {
                    $stV = $this->db->prepare("
                        SELECT pv.id, pv.sku, pv.price, pv.is_active,
                               COALESCE(i.stock, 0) AS stock
                        FROM product_variants pv
                        LEFT JOIN inventory_variants i ON i.variant_id = pv.id
                        WHERE pv.product_id = :pid
                    ");
                    $stV->execute([':pid' => $id]);
                    $rows = $stV->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    $hasAny = (count($rows) > 0);
                    $hasActive = false;
                    $hasSku = false;
                    $hasPrice = false;
                    $hasStock = false;
                    $hasVendible = false;

                    foreach ($rows as $r) {
                        $active = (int)($r['is_active'] ?? 1) ? true : false;
                        $sku = trim((string)($r['sku'] ?? ''));
                        $price = (int)($r['price'] ?? 0);
                        $stock = (int)($r['stock'] ?? 0);

                        if ($active) {
                            $hasActive = true;
                        }
                        if ($sku !== '') {
                            $hasSku = true;
                        }
                        if ($price > 0) {
                            $hasPrice = true;
                        }
                        if ($stock > 0) {
                            $hasStock = true;
                        }

                        if ($active && $sku !== '' && $price > 0 && $stock > 0) {
                            $hasVendible = true;
                            break;
                        }
                    }

                    $saleStatus['checks'] = [
                        ['label' => 'Al menos 1 variante creada', 'ok' => $hasAny],
                        ['label' => 'Al menos 1 variante activa', 'ok' => $hasActive],
                        ['label' => 'SKU cargado en alguna variante', 'ok' => $hasSku],
                        ['label' => 'Precio mayor a 0 en alguna variante', 'ok' => $hasPrice],
                        ['label' => 'Stock mayor a 0 en alguna variante', 'ok' => $hasStock],
                        ['label' => 'Hay al menos 1 variante vendible', 'ok' => $hasVendible],
                    ];

                    $saleStatus['ready'] = $hasVendible;
                    $saleStatus['badge'] = $saleStatus['ready'] ? 'Vendible' : 'Pendiente';
                    $saleStatus['message'] = $saleStatus['ready']
                        ? 'Hay al menos una variante vendible (activa, con SKU, precio y stock).'
                        : 'Para vender, necesitás al menos una variante activa con SKU, precio y stock.';
                }
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductFormService@buildContext: ' . $e->getMessage());
            }
        }

        $brands = $this->db->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $cats = $this->db->query("SELECT id, name FROM categories WHERE is_active=1 ORDER BY sort ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $tagsRepo = new CatalogTagsRepository($this->db);
        try {
            $catalogTagGroups = $tagsRepo->listActiveGroupsWithTags();
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductFormService catalogTagGroups: ' . $e->getMessage());
            }
            $catalogTagGroups = [];
        }
        if ($id > 0) {
            try {
                $productTagIds = $tagsRepo->findProductTagIds($id);
            } catch (Throwable $e) {
                if (function_exists('log_error')) {
                    log_error('ProductFormService productTagIds: ' . $e->getMessage());
                }
                $productTagIds = [];
            }
        }

        $variantsCtx = [];
        if ($id > 0) {
            try {
                $variantsCtx = (new VariantReadService($this->db))->getData($id);
            } catch (Throwable $e) {
                if (function_exists('log_error')) {
                    log_error('ProductFormService variantsCtx: ' . $e->getMessage());
                }
                $variantsCtx = [];
            }
        }

        try {
            $wizardStatus = (new ProductWizardService($this->db))->evaluate($id);
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('ProductFormService wizardStatus: ' . $e->getMessage());
            }
            $wizardStatus = null;
        }

        if ($id > 0 && is_array($product)) {
            try {
                $publicationPreview = (new ProductPublicationPreviewService($this->db))->build(
                    $id,
                    $product,
                    $imgs,
                    $variantsCount,
                    $wizardStatus
                );
            } catch (Throwable $e) {
                if (function_exists('log_error')) {
                    log_error('ProductFormService publicationPreview: ' . $e->getMessage());
                }
                $publicationPreview = [];
            }
        }

        $seoBlocks = ['bullets' => [], 'specs' => [], 'faq' => []];
        if ($id > 0) {
            try {
                $seoBlocks = (new ProductSeoBlocksService($this->db))->getForProduct($id);
            } catch (Throwable $e) {
                if (function_exists('log_error')) {
                    log_error('ProductFormService seoBlocks: ' . $e->getMessage());
                }
            }
        }

        $settingsRepo = new AppSettingsRepository($this->db);
        $shippingDefaults = [
            'weight' => (int)($settingsRepo->get('shipping_default_weight', '1000') ?? 1000),
            'height' => (int)($settingsRepo->get('shipping_default_height', '10') ?? 10),
            'width'  => (int)($settingsRepo->get('shipping_default_width', '10') ?? 10),
            'length' => (int)($settingsRepo->get('shipping_default_length', '10') ?? 10),
        ];

        $saleTab = $this->buildSaleTabModel($id, $product, $inv, $variantsCount, $saleStatus, $variantsCtx, $shippingDefaults);
        $seoTab = $this->buildSeoTabModel($product, $ctxSeoAudit = (new SeoAuditService($this->db))->auditForAdmin($product ?? [], $imgs), $ctxSeoSettings = ($id ? (new SeoManagerService($this->db))->getSettings('product', (int)$id) : []), $seoBlocks);

        return [
            'product' => $product,
            'brands' => $brands,
            'cats' => $cats,
            'catalogTagGroups' => $catalogTagGroups,
            'productTagIds' => $productTagIds,
            'suppliers' => (new SupplierRepository($this->db))->listActiveForSelect(),
            'imgs' => $imgs,
            'shippingDefaults' => $shippingDefaults,
            'imgsImagesTab' => $imgsImagesTab,
            'imgsImagesTabFiltered' => $imgsImagesTabFiltered ?? $imgsImagesTab,
            'imgVariants' => $imgVariants,
            'imgVariantId' => $imgVariantId,
            'imgFilterVariantId' => $imgFilterVariantId,
            'imagesVariantSupported' => $imagesVariantSupported,
            'imgScopesImages' => $imgScopesImages,
            'inv' => $inv,
            'variantsCount' => $variantsCount,
            'seoAudit' => $ctxSeoAudit,
            'seoSettings' => $ctxSeoSettings,
            'seoBlocks' => $seoBlocks,
            'seoTab' => $seoTab,
            'variantsCtx' => $variantsCtx,
            'saleStatus' => $saleStatus,
            'saleTab' => $saleTab,
            'wizardStatus' => $wizardStatus,
            'publicationPreview' => $publicationPreview,
            'pageTitle' => ($id ? 'Editar producto' : 'Nuevo producto'),
        ];
    }

    private function imagesVariantScopeSupported(PDO $db): bool
    {
        try {
            $st = $db->query("SHOW COLUMNS FROM product_images LIKE 'variant_id'");
            return (bool)($st && $st->fetch(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            return false;
        }
    }

    private function buildSaleTabModel(int $id, ?array $product, array $inv, int $variantsCount, ?array $saleStatus, array $variantsCtx, array $shippingDefaults): array
    {
        $p = is_array($product) ? $product : [];
        $hasVariants = $variantsCount > 0;

        $saleMode = (string)($p['sale_mode'] ?? 'simple');
        if ($hasVariants) {
            $saleMode = 'variants';
        }

        $sku = (string)($p['sku'] ?? '');
        $barcode = (string)($p['barcode'] ?? '');
        $price = isset($p['price']) ? (string)$p['price'] : '';
        $listPrice = isset($p['list_price']) ? (string)$p['list_price'] : '';
        $cost = isset($p['cost']) ? (string)$p['cost'] : '';
        $ivaRate = isset($p['iva_rate']) ? (string)$p['iva_rate'] : '';
        $marginPctRaw = isset($p['margin_pct']) ? (string)$p['margin_pct'] : '';

        $marginRate = null;
        if ($marginPctRaw !== '') {
            $tmp = (float)str_replace(',', '.', $marginPctRaw);
            $marginRate = ($tmp >= 10) ? ($tmp / 100.0) : $tmp;
            if ($marginRate < 0) {
                $marginRate = 0.0;
            }
        }
        $marginDisplay = ($marginRate === null) ? '' : rtrim(rtrim(number_format($marginRate * 100.0, 2, '.', ''), '0'), '.');

        $stock = (int)($inv['stock'] ?? 0);
        $low = (int)($inv['low_stock_threshold'] ?? 3);

        $ready = false;
        $badgeText = 'Pendiente';
        $readyMsg = '';
        $checks = [];
        if (is_array($saleStatus)) {
            $ready = (bool)($saleStatus['ready'] ?? false);
            $badgeText = (string)($saleStatus['badge'] ?? ($ready ? 'Vendible' : 'Pendiente'));
            $readyMsg = (string)($saleStatus['message'] ?? '');
            $checks = is_array($saleStatus['checks'] ?? null) ? $saleStatus['checks'] : [];
        }

        $costNetoNum = (float)($cost !== '' ? $cost : 0);
        $ivaDec = null;
        if ($ivaRate !== '') {
            $tmp = (float)$ivaRate;
            $ivaDec = ($tmp > 1) ? ($tmp / 100.0) : $tmp;
        }
        $hasCostNeto = ($costNetoNum > 0);
        $hasIva = ($ivaDec !== null);
        $ivaPct = $hasIva ? ((float)$ivaDec * 100.0) : null;
        $costFinalNum = ($hasCostNeto && $hasIva) ? ($costNetoNum * (1.0 + (float)$ivaDec)) : null;
        $ivaImporteNum = ($hasCostNeto && $hasIva) ? ($costNetoNum * (float)$ivaDec) : null;
        $priceNum = (float)($price !== '' ? $price : 0);
        $markupVsFinal = ($costFinalNum && $costFinalNum > 0 && $priceNum > 0) ? (($priceNum / $costFinalNum) - 1.0) : null;

        $vc = $variantsCtx;
        $pAttrs2 = is_array($vc['variant_attributes'] ?? null) ? $vc['variant_attributes'] : [];
        $variants2 = is_array($vc['variants'] ?? null) ? $vc['variants'] : [];
        $valuesByVariant2 = is_array($vc['values_by_variant'] ?? null) ? $vc['values_by_variant'] : [];
        $attrCount2 = count($pAttrs2);
        $variantCount2 = count($variants2);
        $requiredIds2 = [];
        foreach ($pAttrs2 as $pa) {
            $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
            if ($aid && !empty($pa['is_required']) && !empty($pa['is_variant_axis'])) {
                $requiredIds2[] = $aid;
            }
        }
        $requiredIds2 = array_values(array_unique(array_filter($requiredIds2)));

        $incompleteCount2 = 0;
        $vendibleCount2 = 0;
        foreach ($variants2 as $v) {
            $vid = (int)($v['id'] ?? 0);
            if (!$vid) {
                continue;
            }
            $present = [];
            foreach (($valuesByVariant2[$vid] ?? []) as $vv) {
                $aid = (int)($vv['attribute_id'] ?? 0);
                if ($aid) {
                    $present[$aid] = true;
                }
            }
            $missingRequired = false;
            foreach ($requiredIds2 as $rid) {
                if (empty($present[$rid])) {
                    $missingRequired = true;
                    break;
                }
            }
            if ($missingRequired) {
                $incompleteCount2++;
            }

            $vSku = trim((string)($v['sku'] ?? ''));
            $vPrice = (float)($v['price'] ?? 0);
            $vStock = isset($v['stock']) ? (int)$v['stock'] : 0;
            $vActive = isset($v['is_active']) ? (int)$v['is_active'] : 1;
            if (!$missingRequired && $vActive === 1 && $vSku !== '' && $vPrice > 0 && $vStock >= 0) {
                $vendibleCount2++;
            }
        }

        return [
            'id' => $id,
            'hasVariants' => $hasVariants,
            'saleMode' => $saleMode,
            'sku' => $sku,
            'barcode' => $barcode,
            'price' => $price,
            'listPrice' => $listPrice,
            'cost' => $cost,
            'ivaRate' => $ivaRate,
            'marginDisplay' => $marginDisplay,
            'stock' => $stock,
            'low' => $low,
            'ready' => $ready,
            'badgeText' => $badgeText,
            'readyMsg' => $readyMsg,
            'checks' => $checks,
            'hasCostNeto' => $hasCostNeto,
            'hasIva' => $hasIva,
            'ivaPct' => $ivaPct,
            'costFinalNum' => $costFinalNum,
            'ivaImporteNum' => $ivaImporteNum,
            'markupVsFinal' => $markupVsFinal,
            'defW' => (int)($shippingDefaults['weight'] ?? 1000),
            'defH' => (int)($shippingDefaults['height'] ?? 10),
            'defWi' => (int)($shippingDefaults['width'] ?? 10),
            'defL' => (int)($shippingDefaults['length'] ?? 10),
            'shipWeight' => $p['ship_weight'] ?? null,
            'shipHeight' => $p['ship_height'] ?? null,
            'shipWidth' => $p['ship_width'] ?? null,
            'shipLength' => $p['ship_length'] ?? null,
            'attrCount2' => $attrCount2,
            'variantCount2' => $variantCount2,
            'incompleteCount2' => $incompleteCount2,
            'vendibleCount2' => $vendibleCount2,
        ];
    }

    private function buildSeoTabModel(?array $product, array $seoAudit, array $seoSettings, array $seoBlocks): array
    {
        $product = is_array($product) ? $product : [];
        $seoSettings = is_array($seoSettings) ? $seoSettings : [];
        $seoBlocks = is_array($seoBlocks) ? $seoBlocks : ['bullets' => [], 'specs' => [], 'faq' => []];
        $audit = is_array($seoAudit) ? $seoAudit : [];

        $robots = trim((string)($seoSettings['robots'] ?? '')) ?: 'index,follow';
        $seoIndex = (stripos($robots, 'noindex') !== false) ? 'noindex' : 'index';
        $seoFollow = (stripos($robots, 'nofollow') !== false) ? 'nofollow' : 'follow';

        $name = trim((string)($product['name'] ?? ''));
        $slug = trim((string)($product['slug'] ?? ''));
        $metaTitle = (string)($product['meta_title'] ?? '');
        $metaDesc = (string)($product['meta_description'] ?? '');
        $lenTitle = mb_strlen((string)$metaTitle);
        $lenDesc = mb_strlen((string)$metaDesc);

        $score = (int)($audit['score'] ?? 0);
        $previewUrl = (string)($audit['preview']['url'] ?? '');
        $previewTitle = (string)($audit['preview']['title'] ?? '');
        $previewDesc = (string)($audit['preview']['description'] ?? '');

        $hasCover = false;
        foreach ((array)($audit['items'] ?? []) as $it) {
            if (($it['key'] ?? '') === 'imagen' && (($it['status'] ?? '') === 'ok')) {
                $hasCover = true;
                break;
            }
        }
        $hasPrice = ((float)($audit['pricing']['low'] ?? 0)) > 0;
        $base = function_exists('base_url') ? rtrim((string)base_url('/'), '/') : '';

        if ($previewUrl === '' && $slug !== '') {
            $previewUrl = ($base !== '' ? $base : '') . '/producto?slug=' . rawurlencode($slug);
        }
        if ($previewTitle === '') {
            $previewTitle = $metaTitle !== '' ? $metaTitle : ($name !== '' ? ($name . ' | Mi Pesca & Aventura') : 'Producto | Mi Pesca & Aventura');
        }
        if ($previewDesc === '') {
            $previewDesc = $metaDesc !== '' ? $metaDesc : ($name !== '' ? $name : 'Producto');
        }
        $scorePill = $score >= 85 ? 'av3-bg-emerald-600' : ($score >= 65 ? 'av3-bg-amber-500' : 'av3-bg-rose-600');
        $scoreConfig = [
            'siteName' => 'Mi Pesca & Aventura',
            'baseUrl' => ($base !== '' ? $base : ''),
            'productName' => $name,
            'slug' => $slug,
            'hasCover' => $hasCover,
            'hasPrice' => $hasPrice,
            'initialScore' => $score,
        ];

        $checks = [
            ['key' => 'titulo', 'label' => 'Título del producto', 'ok' => ($name !== ''), 'detail' => ($name !== '' ? 'OK' : 'Falta nombre.')],
            ['key' => 'slug', 'label' => 'URL (slug)', 'ok' => ($slug !== ''), 'detail' => ($slug !== '' ? 'OK' : 'Falta slug.')],
            ['key' => 'meta_title', 'label' => 'Meta title (25-60)', 'ok' => ($metaTitle !== '' && $lenTitle >= 25 && $lenTitle <= 60), 'detail' => ($metaTitle === '' ? 'Falta.' : ($lenTitle > 60 ? 'Muy largo.' : ($lenTitle < 25 ? 'Muy corto.' : 'OK')))],
            ['key' => 'meta_desc', 'label' => 'Meta description (70-160)', 'ok' => ($metaDesc !== '' && $lenDesc >= 70 && $lenDesc <= 160), 'detail' => ($metaDesc === '' ? 'Falta.' : ($lenDesc > 160 ? 'Muy larga.' : ($lenDesc < 70 ? 'Muy corta.' : 'OK')))],
            ['key' => 'imagen', 'label' => 'Imagen principal (portada)', 'ok' => $hasCover, 'detail' => ($hasCover ? 'OK' : 'No hay portada.')],
            ['key' => 'precio', 'label' => 'Precio mayor a 0', 'ok' => $hasPrice, 'detail' => ($hasPrice ? 'OK' : 'Precio en 0.')],
        ];

        $bullets = is_array($seoBlocks['bullets'] ?? null) ? $seoBlocks['bullets'] : [];
        $specs = is_array($seoBlocks['specs'] ?? null) ? $seoBlocks['specs'] : [];
        $faq = is_array($seoBlocks['faq'] ?? null) ? $seoBlocks['faq'] : [];
        if (empty($bullets)) $bullets = [''];
        if (empty($specs)) $specs = [['label' => '', 'value' => '']];
        if (empty($faq)) $faq = [['q' => '', 'a' => '']];

        return [
            'robots' => $robots,
            'seoIndex' => $seoIndex,
            'seoFollow' => $seoFollow,
            'seoCanonical' => (string)($seoSettings['canonical_override'] ?? ''),
            'seoOgTitle' => (string)($seoSettings['og_title'] ?? ''),
            'seoOgDesc' => (string)($seoSettings['og_description'] ?? ''),
            'seoOgImage' => (string)($seoSettings['og_image'] ?? ''),
            'seoTwitter' => (string)($seoSettings['twitter_card'] ?? ''),
            'seoSchema' => (string)($seoSettings['schema_override_jsonld'] ?? ''),
            'name' => $name,
            'slug' => $slug,
            'metaTitle' => $metaTitle,
            'metaDesc' => $metaDesc,
            'lenTitle' => $lenTitle,
            'lenDesc' => $lenDesc,
            'score' => $score,
            'previewUrl' => $previewUrl,
            'previewTitle' => $previewTitle,
            'previewDesc' => $previewDesc,
            'hasCover' => $hasCover,
            'hasPrice' => $hasPrice,
            'scorePill' => $scorePill,
            'scoreConfig' => $scoreConfig,
            'checks' => $checks,
            'bullets' => $bullets,
            'specs' => $specs,
            'faq' => $faq,
        ];
    }
}


