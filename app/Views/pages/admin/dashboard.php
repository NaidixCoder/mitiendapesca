<?php
/** @var array $kpis */
/** @var array $checks */
/** @var array $lowStock */
/** @var array $lastImport */
/** @var array $events */
/** @var array $featured */
/** @var string $env */
/** @var int|string $usuariosTot */

$pageTitle = 'Admin — Dashboard';
?>
<section class="max-w-[120rem] mx-auto p-6 space-y-6">
  <?php section('admin/dashboard/header_actions'); ?>
  <?php section('admin/dashboard/kpis', ['kpis'=>$kpis]); ?>

  <div class="grid grid-cols-1 2xl:grid-cols-3 gap-4">
    <div class="2xl:col-span-2 space-y-4">
      <?php section('admin/dashboard/chart_placeholder'); ?>
      <?php section('admin/dashboard/featured_products', ['rows'=>$featured]); ?>
      <?php section('admin/dashboard/system_health', ['checks'=>$checks]); ?>
      <?php section('admin/dashboard/low_stock', ['rows'=>$lowStock]); ?>
    </div>
    <div class="space-y-4">
      <?php section('admin/dashboard/last_import', ['job'=>$lastImport]); ?>
      <?php section('admin/dashboard/activity', ['events'=>$events]); ?>
      <?php section('admin/dashboard/tasks'); ?>
    </div>
  </div>
</section>

<script>
  // Hook opcional para gráfico
  // const el = document.querySelector('[data-chart="line"]');
  // if (el) renderChart(el, window.__DASH_DATA || []);
</script>
