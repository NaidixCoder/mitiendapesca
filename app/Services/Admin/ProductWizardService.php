<?php
namespace App\Services\Admin;

use PDO;

/**
 * Evalúa el estado del wizard de productos (7 tabs) y devuelve blockers/warnings.
 *
 * - Se usa para mostrar badges/checklist.
 * - Se usa server-side para impedir publicar si faltan datos críticos.
 */
class ProductWizardService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @return array{tabs: array<string,array{ok:bool,blockers:array,warnings:array}>, blockers_globales: array, warnings_globales: array, publishable: bool, meta: array}
     */
    public function evaluate(int $productId): array
    {
        if ($productId <= 0) {
            return $this->emptyState();
        }

        // Producto
        $stP = $this->db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stP->execute([':id' => $productId]);
        $p = $stP->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$p) {
            return $this->emptyState();
        }

        $settings = new \App\Repositories\Settings\AppSettingsRepository($this->db);
        $allowPublishWithoutImages = ((string)($settings->get('catalog_allow_publish_without_images', '0') ?? '0') === '1');

        // Imágenes
        $stImg = $this->db->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = :id');
        $stImg->execute([':id' => $productId]);
        $imagesCount = (int)$stImg->fetchColumn();

        
        // Imágenes generales (variant_id IS NULL). Si el schema no tiene variant_id, hacemos fallback.
        $imagesGeneralCount = $imagesCount;
        try {
            $stImgG = $this->db->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = :id AND variant_id IS NULL');
            $stImgG->execute([':id' => $productId]);
            $imagesGeneralCount = (int)$stImgG->fetchColumn();
        } catch (\Throwable $e) {
            $imagesGeneralCount = $imagesCount;
        }

// Inventario (producto simple)
        $stInv = $this->db->prepare('SELECT stock FROM inventory WHERE product_id = :id');
        $stInv->execute([':id' => $productId]);
        $stockSimple = (int)($stInv->fetchColumn() ?? 0);

        // Variantes
        $stVC = $this->db->prepare('SELECT COUNT(*) FROM product_variants WHERE product_id = :id');
        $stVC->execute([':id' => $productId]);
        $variantsCount = (int)$stVC->fetchColumn();

        $saleMode = (string)($p['sale_mode'] ?? 'simple');
        if ($variantsCount > 0) {
            // Autodetección (consistencia)
            $saleMode = 'variants';
        }

        // Vendibles (variantes)
        $vendibleVariantsCount = 0;
        if ($saleMode === 'variants') {
            $stVend = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM product_variants pv
                 LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
                 WHERE pv.product_id = :id
                   AND COALESCE(pv.is_active, 1) = 1
                   AND TRIM(COALESCE(pv.sku, "")) <> ""
                   AND COALESCE(pv.price, 0) > 0
                   AND COALESCE(iv.stock, 0) > 0'
            );
            $stVend->execute([':id' => $productId]);
            $vendibleVariantsCount = (int)$stVend->fetchColumn();
        }

        // --- Tabs ---
        $tabs = [
            'basicos'     => ['ok' => true, 'blockers' => [], 'warnings' => []],
            'inventario'  => ['ok' => true, 'blockers' => [], 'warnings' => []],
            'variantes'   => ['ok' => true, 'blockers' => [], 'warnings' => []],
            'imagenes'    => ['ok' => true, 'blockers' => [], 'warnings' => []],
            'seo'         => ['ok' => true, 'blockers' => [], 'warnings' => []],
            'publicacion' => ['ok' => true, 'blockers' => [], 'warnings' => []],
        ];


        $warningsGlobales = [];

        // Básicos
        $name = trim((string)($p['name'] ?? ''));
        $catId = (int)($p['category_id'] ?? 0);
        if ($name === '') {
            $tabs['basicos']['ok'] = false;
            $tabs['basicos']['blockers'][] = 'Completá el nombre del producto.';
        }
        if ($catId <= 0) {
            $tabs['basicos']['ok'] = false;
            $tabs['basicos']['blockers'][] = 'Seleccioná una categoría.';
        }

        // SEO (slug)
        $slug = trim((string)($p['slug'] ?? ''));
        if ($slug === '') {
            $tabs['seo']['ok'] = false;
            $tabs['seo']['blockers'][] = 'Completá el slug (URL).';
        } else {
            $stDup = $this->db->prepare('SELECT id FROM products WHERE slug = :slug AND id <> :id LIMIT 1');
            $stDup->execute([':slug' => $slug, ':id' => $productId]);
            if ($stDup->fetch(PDO::FETCH_ASSOC)) {
                $tabs['seo']['ok'] = false;
                $tabs['seo']['blockers'][] = 'El slug ya existe en otro producto.';
            }
        }

        // Imágenes
        if (!$allowPublishWithoutImages && $imagesCount <= 0) {
            $tabs['imagenes']['ok'] = false;
            $tabs['imagenes']['blockers'][] = 'Agregá al menos 1 imagen.';
        }

        // Recomendación UX: una imagen general ayuda a listados/catálogo.
        if ($imagesCount > 0 && $imagesGeneralCount <= 0) {
            $tabs['imagenes']['warnings'][] = 'Recomendado: agregá al menos 1 imagen general para el catálogo/listado.';
            $warningsGlobales[] = 'Recomendado: agregá al menos 1 imagen general para el catálogo/listado.';
        }

        // Venta + Variantes
        if ($saleMode === 'simple') {
            $sku = trim((string)($p['sku'] ?? ''));
            $price = (int)($p['price'] ?? 0);
            if ($sku === '') {
                $tabs['inventario']['ok'] = false;
                $tabs['inventario']['blockers'][] = 'Completá el SKU.';
            }
            if ($price <= 0) {
                $tabs['inventario']['ok'] = false;
                $tabs['inventario']['blockers'][] = 'Definí un precio mayor a 0.';
            }
            if ($stockSimple <= 0) {
                $tabs['inventario']['ok'] = false;
                $tabs['inventario']['blockers'][] = 'Cargá stock (mayor a 0) para poder vender.';
            }

            // Tab Variantes no aplica
            $tabs['variantes']['ok'] = true;
            $tabs['variantes']['blockers'] = [];
        } else {
            // Producto con variantes: Venta queda OK cuando el modo quedó guardado como variantes.
            // La validación de existencia/vendibilidad de variantes corresponde al tab Variantes.
            if ($variantsCount <= 0) {
                $tabs['variantes']['ok'] = false;
                $tabs['variantes']['blockers'][] = 'Creá al menos 1 variante.';
            }

            if ($variantsCount > 0 && $vendibleVariantsCount <= 0) {
                $tabs['variantes']['ok'] = false;
                $tabs['variantes']['blockers'][] = 'Completá al menos 1 variante vendible (SKU, precio y stock).';
            }
        }

        // Global blockers para publicar
        $blockersGlobales = [];
        foreach (['basicos','inventario','variantes','imagenes','seo'] as $tKey) {
            if (!empty($tabs[$tKey]['blockers'])) {
                foreach ($tabs[$tKey]['blockers'] as $b) {
                    $blockersGlobales[] = $b;
                }
            }
        }

        $publishable = empty($blockersGlobales);
        $tabs['publicacion']['ok'] = $publishable;
        if (!$publishable) {
            $tabs['publicacion']['blockers'][] = 'No podés publicar hasta completar los puntos pendientes.';
        }

        return [
            'tabs' => $tabs,
            'blockers_globales' => $blockersGlobales,
            'warnings_globales' => $warningsGlobales,
            'publishable' => $publishable,
            'meta' => [
                'sale_mode' => $saleMode,
                'images_count' => $imagesCount,
                'images_general_count' => $imagesGeneralCount,
                'variants_count' => $variantsCount,
                'vendible_variants_count' => $vendibleVariantsCount,
                'stock_simple' => $stockSimple,
            ],
        ];
    }

    private function emptyState(): array
    {
        return [
            'tabs' => [
                'basicos'     => ['ok' => false, 'blockers' => ['Guardá los datos básicos para crear el producto.'], 'warnings' => []],
                'inventario'  => ['ok' => false, 'blockers' => ['Primero guardá Básicos.'], 'warnings' => []],
                'variantes'   => ['ok' => false, 'blockers' => ['Primero guardá Básicos.'], 'warnings' => []],
                'imagenes'    => ['ok' => false, 'blockers' => ['Primero guardá Básicos.'], 'warnings' => []],
                'seo'         => ['ok' => false, 'blockers' => ['Primero guardá Básicos.'], 'warnings' => []],
                'rel'         => ['ok' => true,  'blockers' => [], 'warnings' => []],
                'publicacion' => ['ok' => false, 'blockers' => ['Primero guardá Básicos.'], 'warnings' => []],
            ],
            'blockers_globales' => ['Guardá los datos básicos para crear el producto.'],
            'warnings_globales' => [],
            'publishable' => false,
            'meta' => [
                'sale_mode' => 'simple',
                'images_count' => 0,
                'images_general_count' => 0,
                'variants_count' => 0,
                'vendible_variants_count' => 0,
                'stock_simple' => 0,
            ],
        ];
    }
}
