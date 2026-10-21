<?php
/** @var bool $is_edit */
/** @var int $item_id */
/** @var array $crumbs */
/** @var array $values */
/** @var array $errors */
/** @var array $supplier_options */
/** @var array $attachments_prepared */
/** @var array $cbte_options */
/** @var array $status_options */
?>

<?php render_partial('admin/subnav'); ?>
<div class="av3-width-max-4xl av3-mx-auto av3-pad-4">
  <?php render_partial('admin/facturacion/compras/form/header', [
    'is_edit' => $is_edit,
  ]); ?>

  <?php render_partial('admin/facturacion/compras/form/main_form', [
    'is_edit' => $is_edit,
    'item_id' => $item_id,
    'values' => $values,
    'errors' => $errors,
    'supplier_options' => $supplier_options,
    'cbte_options' => $cbte_options,
    'status_options' => $status_options,
  ]); ?>

  <?php render_partial('admin/facturacion/compras/form/attachments', [
    'is_edit' => $is_edit,
    'item_id' => $item_id,
    'attachments_prepared' => $attachments_prepared,
  ]); ?>
</div>
