<?php
/** @var int $id */
$id = (int)($id ?? 0);
?>

<div class="av3-flow-4">

<?php if ($id <= 0): ?>
  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4">
    <h3 class="av3-type-base av3-weight-strong">Variantes</h3>
    <p class="av3-space-top-1 av3-type-body av3-text-var-muted">
      Guarda primero los <b>Basicos</b> para crear el producto. Despues vas a poder configurar opciones (por ejemplo <b>Color</b>),
      generar combinaciones y completar SKU/stock por variante.
    </p>
  </div>
<?php else: ?>
  <div id="variants-sale"
       data-product-id="<?= (int)$id ?>"
       data-endpoint-catalog="<?= url('/admin/attributes/catalog.json') ?>"
       data-endpoint-json="<?= url('/admin/products/variants.json?id='.(int)$id) ?>"
       data-endpoint-fragment="<?= url('/admin/products/variants/fragment?id='.(int)$id) ?>"
       data-endpoint-attr-add="<?= url('/admin/products/variants/attributes/add') ?>"
       data-endpoint-attr-remove-preview="<?= url('/admin/products/variants/attributes/remove-preview') ?>"
       data-endpoint-attr-remove="<?= url('/admin/products/variants/attributes/remove') ?>"
       data-endpoint-values-set="<?= url('/admin/products/variants/values/set') ?>"
       data-endpoint-values-create="<?= url('/admin/products/variants/values/create') ?>"
       data-endpoint-values-suggest-fragment="<?= url('/admin/products/variants/values/suggest-fragment') ?>"
       data-endpoint-sets-import="<?= url('/admin/products/variants/sets/import') ?>"
       data-endpoint-sets-create="<?= url('/admin/products/variants/sets/create') ?>"
       data-endpoint-value-delete-json="<?= url('/admin/attributes/value/delete.json') ?>"
       data-endpoint-attr-quick="<?= url('/admin/attributes/quick') ?>"
       data-endpoint-generate="<?= url('/admin/products/variants/generate') ?>"
       data-endpoint-variant-save="<?= url('/admin/products/variants/save') ?>"
       data-endpoint-variant-toggle="<?= url('/admin/products/variants/toggle') ?>"
       data-endpoint-variant-delete="<?= url('/admin/products/variants/delete') ?>"
       data-endpoint-delete-incomplete="<?= url('/admin/products/variants/delete-incomplete') ?>"
       data-endpoint-iva-update="<?= url('/admin/products/update-iva.json') ?>"
       data-endpoint-cleanup-obsolete="<?= url('/admin/products/variants/cleanup-obsolete') ?>">

    <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4 av3-type-body av3-text-var-muted">
      Cargando gestor de variantes...
    </div>
  </div>

<?php endif; ?>

</div>
