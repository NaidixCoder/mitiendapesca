<?php
/** @var array $crumbs */
/** @var array $rows_prepared */
/** @var int $rows_count */
/** @var string $back_url */
/** @var string $export_url */
?>

<div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto">
  <?php render_partial('admin/facturacion/reportes/desvios/header', [
    'back_url' => $back_url,
    'export_url' => $export_url,
    'rows_count' => $rows_count,
  ]); ?>

  <?php render_partial('admin/facturacion/reportes/desvios/table', [
    'rows_prepared' => $rows_prepared,
  ]); ?>

  <?php render_partial('admin/facturacion/reportes/desvios/footnote'); ?>
</div>
