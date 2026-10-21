<?php
$pageTitle = 'Productos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$vm = is_array($products_index ?? null) ? $products_index : [];
$rowsPrepared = is_array($vm['rows_prepared'] ?? null) ? $vm['rows_prepared'] : (is_array($rows ?? null) ? $rows : []);
$pagerLinks = is_array($vm['pager_links'] ?? null) ? $vm['pager_links'] : [];
$q = trim((string)($vm['query'] ?? ($q ?? '')));
$pages = (int)($vm['pages'] ?? ($pages ?? 1));
$total = (int)($vm['total'] ?? ($total ?? 0));
?>
<section class="admin-surface admin-v3-products">
  <?php render_partial('admin/products/index/header', [
    'total' => $total,
  ]); ?>

  <?php render_partial('admin/products/index/filters', [
    'q' => $q,
  ]); ?>

  <section class="av3-card av3-p-0 av3-overflow-hidden products-index-list">
    <header class="av3-card-header">
      <div><p class="catalog-eyebrow">RESULTADOS</p><h2 class="av3-card-title">Todos los productos</h2></div><span class="catalog-list-count"><?= (int)$total ?> registrados</span>
    </header>

    <?php render_partial('admin/products/index/table', [
      'rows_prepared' => $rowsPrepared,
    ]); ?>

    <?php render_partial('admin/products/index/pager', [
      'pager_links' => $pagerLinks,
      'pages' => $pages,
    ]); ?>
  </section>
</section>
