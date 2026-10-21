<?php
$adminHeaderTitle=(string)($ui['title'] ?? 'Producto');
$adminHeaderDescription=(string)($ui['subtitle'] ?? 'Completá la información comercial del producto.');
$adminHeaderBreadcrumbs=admin_crumbs(!empty($ui['is_edit']) ? 'products.edit' : 'products.new', ['id'=>$id]);
$adminHeaderClass='product-editor-head';
ob_start(); ?>
  <div class="av3-header-right product-editor-head__tools">
    <div class="admin-step product-editor-progress" aria-label="Paso actual">
      <div class="admin-step-line">
        <span id="wizard-step" class="av3-step-text">Paso <?= (int)($ui['step_number'] ?? 1) ?> de <?= (int)($ui['total_steps'] ?? 6) ?> · <?= e((string)($ui['step_label'] ?? 'Básicos')) ?></span>
      </div>
      <div class="av3-progress" aria-hidden="true">
        <span id="wizard-progress" class="av3-progress-bar" style="width: <?= (int)($ui['progress_percent'] ?? 0) ?>%;"></span>
      </div>
    </div>
    <div class="av3-actions">
      <?php if ($id > 0 && (string)($ui['audit_url'] ?? '') !== ''): ?>
        <a class="catalog-button catalog-button--outline" href="<?= e((string)$ui['audit_url']) ?>">Ver auditoría</a>
      <?php endif; ?>
      <a class="catalog-button catalog-button--outline" href="<?= url('/admin/products') ?>">Cancelar</a>
      <button form="product-form" class="catalog-button catalog-button--primary"><?= !empty($ui['is_edit']) ? 'Guardar cambios' : 'Crear producto' ?></button>
    </div>
  </div>
<?php $adminHeaderActions=ob_get_clean();
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
