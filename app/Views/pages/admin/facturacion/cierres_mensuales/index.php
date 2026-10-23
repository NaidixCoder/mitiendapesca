<main class="av3-fiscal-page av3-container av3-max-w-6xl av3-mx-auto av3-pad-4">
  <?php render_partial('admin/subnav'); ?>
  <?php render_partial('admin/facturacion/cierres_mensuales/index/header', [
    'is_ri' => !empty($is_ri),
    'reportes_url' => (string)($reportes_url ?? ''),
  ]); ?>

  <?php render_partial('admin/facturacion/cierres_mensuales/index/close_form', [
    'period_options' => $period_options ?? [],
    'close_url' => (string)($close_url ?? ''),
    'csrf' => (string)($csrf ?? ''),
  ]); ?>

  <?php render_partial('admin/facturacion/cierres_mensuales/index/table', [
    'closures_prepared' => $closures_prepared ?? [],
    'has_closures' => !empty($has_closures),
    'closures_count' => (int)($closures_count ?? 0),
  ]); ?>
</main>