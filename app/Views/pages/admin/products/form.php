<?php require BASE_PATH.'/app/Views/partials/admin/subnav.php'; ?>
<?php
/** @var array $product */
/** @var array $brands */
/** @var array $cats */
/** @var array $imgs */
/** @var array $inv */
/** @var int $variantsCount */

$p = is_array($product ?? null) ? $product : [];
$ui = is_array($product_form_ui ?? null) ? $product_form_ui : [];
$tabs = is_array($ui['tabs'] ?? null) ? $ui['tabs'] : [];
$activeTab = (string)($ui['active_tab'] ?? 'basicos');
$variantsEnabled = !empty($ui['variants_enabled']);
$id = (int)($ui['id'] ?? 0);
$err = (string)($ui['error'] ?? '');
$nextUrl = (string)($ui['next_url'] ?? '/admin/products');
?>

<section class="av3-max-w-6xl av3-mx-auto av3-pad-6 av3-flow-5 admin-v3-product-form">
  <?php require BASE_PATH.'/app/Views/partials/admin/products/form/header.php'; ?>

  <?php if ($err !== ''): ?>
    <div class="card av3-pad-3" style="border-left:4px solid var(--danger)"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="card av3-p-0" id="product-tabs-root" data-variants-enabled="<?= $variantsEnabled ? 1 : 0 ?>">
    <?php require BASE_PATH.'/app/Views/partials/admin/products/form/tabs_nav.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/products/form/form_tabs.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/products/form/unsaved_modal.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/products/form/wizard_nav.php'; ?>
  </div>
</section>
