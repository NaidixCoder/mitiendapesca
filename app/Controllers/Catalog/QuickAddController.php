<?php

namespace App\Controllers\Catalog;

use App\Repositories\Catalog\ProductRepository;
use App\Repositories\Catalog\VariantRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Pricing\CommercialPriceService;
use App\Services\Pricing\PriceService;

/**
 * Quick Add (Catálogo)
 * - Devuelve HTML (partial) para seleccionar variante + cantidad y agregar.
 * - Se usa desde /productos (cards).
 */
final class QuickAddController
{
    public function show(): void
    {
        $db = db();

        $productId = (int)($_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            http_response_code(400);
            echo 'Producto inválido.';
            return;
        }

        $product = (new ProductRepository($db))->findQuickAdd($productId);

        $allowed = $product ? (new \App\Services\Catalog\ProductPublicVisibilityService($db))
            ->eligibleIdsAmong([$productId]) : [];
        if (!$product || !isset($allowed[$productId])) {
            http_response_code(404);
            echo 'Producto no encontrado.';
            return;
        }

        if ((string)($product['sale_mode'] ?? 'simple') !== 'variants') {
            http_response_code(409);
            echo 'Este producto no requiere opciones.';
            return;
        }

        // Igualar criterio de /producto?slug=... para descripcion corta visible.
        $product['description'] = (string)($product['short_desc'] ?? $product['description'] ?? $product['name'] ?? '');

        $variants = (new VariantRepository($db))->listActiveForProductQuickAdd($productId);

        // Ajustar precio de variantes según rol (mayoristas) usando PriceService.
        $role = function_exists('current_user_role') ? current_user_role() : 'customer';
        $settingsRepo = new AppSettingsRepository($db);
        $priceSvc = new PriceService($settingsRepo);
        $commercialPricing = new CommercialPriceService($settingsRepo);

        foreach ($variants as $index => $variant) {
            if (!is_array($variant)) {
                continue;
            }

            $publicPrice = (int)($variant['price'] ?? 0);
            $variantRow = $variant;
            $variantRow['price'] = $publicPrice;

            $transferPrice = (int)$priceSvc->getUnitPriceForUser($product, $variantRow, (string)$role);
            $financedPrice = (int)($variant['list_price'] ?? 0);
            if ($financedPrice <= 0) {
                $financedPrice = $transferPrice;
            }

            $installmentAmount = $commercialPricing->installmentAmount($financedPrice);
            $installmentInline = $installmentAmount > 0
                ? ($commercialPricing->installmentsCount() . ' cuotas sin interés de ' . money($installmentAmount))
                : '';

            $variants[$index]['price'] = $transferPrice;
            $variants[$index]['transfer_price'] = $transferPrice;
            $variants[$index]['financed_price'] = $financedPrice;
            $variants[$index]['installment_count'] = $commercialPricing->installmentsCount();
            $variants[$index]['installment_amount'] = $installmentAmount;
            $variants[$index]['installment_inline'] = $installmentInline;
        }

        $optionLabel = 'Variante';
        $attributeNames = [];
        foreach ($variants as $variant) {
            $name = trim((string)($variant['attribute_name'] ?? ''));
            if ($name !== '') {
                $attributeNames[] = mb_strtoupper($name, 'UTF-8');
            }
        }
        $attributeNames = array_values(array_unique($attributeNames));
        if (count($attributeNames) === 1) {
            $optionLabel = $attributeNames[0];
        }

        header('Content-Type: text/html; charset=utf-8');
        section('partials/catalogo/quick-add-content', [
            'product' => $product,
            'variants' => $variants,
            'optionLabel' => $optionLabel,
        ]);
    }
}
