<?php render_partial('admin/subnav'); ?>
<div class="av3-max-w-6xl av3-mx-auto av3-pad-x4 av3-pad-y6">
  <?php render_partial('admin/facturacion/reportes/saldo_iva/header_filters', [
    'filters' => $filters ?? [],
    'env_options' => $env_options ?? [],
    'apply_url' => (string)($apply_url ?? ''),
    'reset_url' => (string)($reset_url ?? ''),
    'export_url' => (string)($export_url ?? ''),
  ]); ?>

  <?php render_partial('admin/facturacion/reportes/saldo_iva/totals_cards', [
    'totals_cards' => $totals_cards ?? [],
  ]); ?>

  <?php render_partial('admin/facturacion/reportes/saldo_iva/table', [
    'rows_prepared' => $rows_prepared ?? [],
    'has_rows' => !empty($has_rows),
  ]); ?>
</div>
