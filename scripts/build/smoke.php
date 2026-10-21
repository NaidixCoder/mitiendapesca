<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "[gate] Falta vendor/autoload.php\n");
    exit(1);
}
require_once $autoload;

$viewCheckout = __DIR__ . '/../../app/Views/pages/checkout/index.php';
$jsCheckoutSteps = __DIR__ . '/../../public_html/assets/js/checkout/checkout_steps.js';
$jsMpBrick = __DIR__ . '/../../public_html/assets/js/checkout/mp_payment_brick.js';

if (!is_file($viewCheckout) || !is_file($jsCheckoutSteps) || !is_file($jsMpBrick)) {
    fwrite(STDERR, "[gate] Faltan archivos de checkout para smoke de selectedPaymentMethod\n");
    exit(1);
}

$viewCheckoutSrc = (string)file_get_contents($viewCheckout);
if (strpos($viewCheckoutSrc, 'name="selectedPaymentMethod"') === false) {
    fwrite(STDERR, "[gate] Falta hidden input selectedPaymentMethod en checkout view\n");
    exit(1);
}

$jsCheckoutStepsSrc = (string)file_get_contents($jsCheckoutSteps);
if (strpos($jsCheckoutStepsSrc, "checkout-selected-payment-method") === false) {
    fwrite(STDERR, "[gate] checkout_steps.js no sincroniza hidden selectedPaymentMethod\n");
    exit(1);
}

$jsMpBrickSrc = (string)file_get_contents($jsMpBrick);
if (strpos($jsMpBrickSrc, 'resolveCheckoutSelectedMethod') === false) {
    fwrite(STDERR, "[gate] mp_payment_brick.js no tiene fallback para selectedPaymentMethod\n");
    exit(1);
}

$handlers = [
    [App\Controllers\Cart\CartController::class, 'index'],
    [App\Controllers\Cart\CartController::class, 'add'],
    [App\Controllers\Cart\CartController::class, 'update'],
    [App\Controllers\Cart\CartController::class, 'remove'],
    [App\Controllers\Cart\CartController::class, 'clear'],
    [App\Controllers\Cart\CartController::class, 'repeatMerge'],
    [App\Controllers\Cart\CartController::class, 'repeatReplace'],
    [App\Controllers\Cart\CartController::class, 'repeatCancel'],
    [App\Controllers\Cart\CartController::class, 'mergeUnify'],
    [App\Controllers\Cart\CartController::class, 'mergeReplace'],
    [App\Controllers\Cart\CartController::class, 'mergeDiscard'],
    [App\Controllers\Checkout\CheckoutController::class, 'index'],
    [App\Controllers\Checkout\CheckoutController::class, 'confirm'],
    [App\Controllers\Checkout\CheckoutController::class, 'transferencia'],
    [App\Controllers\Checkout\ShippingController::class, 'calculate'],
    [App\Controllers\Checkout\ShippingController::class, 'select'],
    [App\Controllers\Checkout\PaymentController::class, 'select'],
    [App\Controllers\Checkout\PaymentController::class, 'preference'],
    [App\Controllers\Checkout\PaymentController::class, 'payment'],
    [App\Controllers\Checkout\PaymentController::class, 'pay'],
    [App\Controllers\Checkout\PaymentController::class, 'result'],
];

$serviceClasses = [
    App\Services\Checkout\CheckoutFlowService::class,
    App\Services\Checkout\CheckoutIntentService::class,
    App\Services\Checkout\CheckoutOrderService::class,
    App\Services\Admin\Variants\VariantPricingService::class,
    App\Services\Admin\Variants\VariantStockService::class,
    App\Services\Admin\Variants\VariantAttributesService::class,
    App\Services\Admin\Variants\VariantReadService::class,
    App\Services\Admin\Variants\VariantWriteService::class,
    App\Services\Admin\Variants\VariantLifecycleService::class,
    App\Services\Admin\Variants\VariantValueSetsService::class,
    App\Services\Admin\Variants\VariantAuditService::class,
    App\Services\Admin\Variants\VariantAttributeValueDeleteService::class,
    App\Services\Admin\Products\ProductPricingService::class,
    App\Services\Admin\Products\ProductImagesService::class,
    App\Services\Admin\Products\ProductFormService::class,
    App\Services\Admin\Products\ProductSaveService::class,
    App\Services\Cart\CartReadService::class,
    App\Services\Cart\CartWriteService::class,
    App\Services\Cart\CartSignatureService::class,
    App\Services\Cart\Legacy\LegacyCartEngine::class,
    App\Repositories\Catalog\VariantInventoryRepository::class,
    App\Repositories\Catalog\VariantPricingRepository::class,
    App\Repositories\Catalog\VariantAttributesRepository::class,
    App\Repositories\Admin\AdminVariantReadRepository::class,
    App\Repositories\Admin\ProductWriteRepository::class,
    App\Services\Admin\Audit\ViewModels\AuditLoginsViewModel::class,
    App\Services\Admin\Attributes\ViewModels\AttributesIndexViewModel::class,
    App\Services\Admin\Categories\ViewModels\CategoriesFormViewModel::class,
];

$traits = [
    App\Services\Admin\Variants\VariantAttributesLifecycleTrait::class,
    App\Services\Admin\Variants\VariantAttributesSelectionTrait::class,
];

$checkoutServiceMethods = [
    [App\Services\Checkout\CheckoutFlowService::class, 'resolveCurrentStep'],
    [App\Services\Checkout\CheckoutFlowService::class, 'resolveFreeMinAmount'],
    [App\Services\Checkout\CheckoutFlowService::class, 'invalidateSelectionIfCartChanged'],
    [App\Services\Checkout\CheckoutFlowService::class, 'ensurePaymentSelectionForStep'],
    [App\Services\Checkout\CheckoutFlowService::class, 'normalizeShippingSnapshot'],
    [App\Services\Checkout\CheckoutFlowService::class, 'applyShippingToCart'],
    [App\Services\Checkout\CheckoutFlowService::class, 'enforceStep3Shipping'],
    [App\Services\Checkout\CheckoutIntentService::class, 'createIntent'],
    [App\Services\Checkout\CheckoutOrderService::class, 'confirm'],
];

$adminVariantServiceMethods = [
    [App\Services\Admin\Variants\VariantPricingService::class, 'getSnapshot'],
    [App\Services\Admin\Variants\VariantPricingService::class, 'savePricing'],
    [App\Services\Admin\Variants\VariantStockService::class, 'getSnapshot'],
    [App\Services\Admin\Variants\VariantStockService::class, 'saveStock'],
    [App\Services\Admin\Variants\VariantReadService::class, 'getData'],
    [App\Services\Admin\Variants\VariantWriteService::class, 'generateVariants'],
    [App\Services\Admin\Variants\VariantWriteService::class, 'saveVariant'],
    [App\Services\Admin\Variants\VariantLifecycleService::class, 'toggle'],
    [App\Services\Admin\Variants\VariantLifecycleService::class, 'deleteVariant'],
    [App\Services\Admin\Variants\VariantLifecycleService::class, 'cleanupObsoleteVariants'],
    [App\Services\Admin\Variants\VariantLifecycleService::class, 'deleteIncompleteVariants'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'snapshotVariant'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'logVariantSave'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'logVariantToggle'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'logVariantDeletePaused'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'logVariantDeleteDeleted'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'logCleanupObsolete'],
    [App\Services\Admin\Variants\VariantAuditService::class, 'logDeleteIncomplete'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'createValueSetFromProduct'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'importValueSet'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'valueSetsTablesExist'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'listValueSetsAdmin'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'getValueSetItems'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'deleteValueSet'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'getValueSet'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'getValueSetAdminRow'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'listCategories'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'listBrands'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'listAttributes'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'listAttributeValues'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'getValueSetEditGroups'],
    [App\Services\Admin\Variants\VariantValueSetsService::class, 'updateValueSet'],
    [App\Services\Admin\Products\ProductPricingService::class, 'updateIvaAndReprice'],
    [App\Services\Admin\Products\ProductImagesService::class, 'upload'],
    [App\Services\Admin\Products\ProductImagesService::class, 'delete'],
    [App\Services\Admin\Products\ProductImagesService::class, 'cover'],
    [App\Services\Admin\Products\ProductImagesService::class, 'assign'],
    [App\Services\Admin\Products\ProductImagesService::class, 'bulkAssign'],
    [App\Services\Admin\Products\ProductImagesService::class, 'bulkDelete'],
    [App\Services\Admin\Products\ProductImagesService::class, 'sort'],
    [App\Services\Admin\Products\ProductFormService::class, 'buildContext'],
    [App\Services\Admin\Products\ProductSaveService::class, 'handle'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'findProductById'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'findProductColumnsById'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'findProductIdByFieldExcluding'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'countVariantsByProductId'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'insertProduct'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'updateProduct'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'findInventoryByProductId'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'upsertInventory'],
    [App\Repositories\Admin\ProductWriteRepository::class, 'recalcVariantPricesForIva'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'addAttribute'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'removeAttribute'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'previewRemoveAttribute'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'updateProductAttribute'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'setAttributeValues'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'createValuesAndSelect'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'suggestValues'],
    [App\Services\Admin\Variants\VariantAttributesService::class, 'deleteAttributeValue'],
    [App\Services\Admin\Variants\VariantAttributeValueDeleteService::class, 'pruneAttributeValueIfUnused'],
    [App\Services\Admin\Variants\VariantAttributeValueDeleteService::class, 'deleteAttributeValue'],
    [App\Repositories\Catalog\VariantInventoryRepository::class, 'find'],
    [App\Repositories\Catalog\VariantInventoryRepository::class, 'upsert'],
    [App\Repositories\Catalog\VariantPricingRepository::class, 'findSnapshot'],
    [App\Repositories\Catalog\VariantPricingRepository::class, 'updatePricing'],
    [App\Repositories\Catalog\VariantAttributesRepository::class, 'deleteAttributeValue'],
    [App\Repositories\Admin\AdminVariantReadRepository::class, 'fetchForProduct'],
];

$cartServiceMethods = [
    [App\Services\Cart\CartReadService::class, 'hasItems'],
    [App\Services\Cart\CartReadService::class, 'syncSessionCount'],
    [App\Services\Cart\CartWriteService::class, 'prepareMergeOnLogin'],
    [App\Services\Cart\CartWriteService::class, 'mergeItems'],
];

$missingClasses = [];
$missingMethods = [];
foreach ($handlers as [$class, $method]) {
    if (!class_exists($class)) {
        $missingClasses[] = $class;
        continue;
    }
    if (!method_exists($class, $method)) {
        $missingMethods[] = $class . '@' . $method;
    }
}

if ($missingClasses !== []) {
    fwrite(STDERR, "[gate] Clases no cargadas:\n - " . implode("\n - ", array_unique($missingClasses)) . "\n");
    exit(1);
}

if ($missingMethods !== []) {
    fwrite(STDERR, "[gate] Metodos no encontrados:\n - " . implode("\n - ", $missingMethods) . "\n");
    exit(1);
}

foreach ($serviceClasses as $class) {
    if (!class_exists($class)) {
        fwrite(STDERR, "[gate] Servicio no cargado: {$class}\n");
        exit(1);
    }
}

foreach ($traits as $trait) {
    if (!trait_exists($trait)) {
        fwrite(STDERR, "[gate] Trait no cargado: {$trait}\n");
        exit(1);
    }
}

foreach ($cartServiceMethods as [$class, $method]) {
    if (!method_exists($class, $method)) {
        fwrite(STDERR, "[gate] Metodo de servicio no encontrado: {$class}@{$method}\n");
        exit(1);
    }
}

foreach ($checkoutServiceMethods as [$class, $method]) {
    if (!method_exists($class, $method)) {
        fwrite(STDERR, "[gate] Metodo checkout no encontrado: {$class}@{$method}\n");
        exit(1);
    }
}

foreach ($adminVariantServiceMethods as [$class, $method]) {
    if (!method_exists($class, $method)) {
        fwrite(STDERR, "[gate] Metodo admin variantes no encontrado: {$class}@{$method}\n");
        exit(1);
    }
}

echo "[gate] smoke classes/methods OK\n";


