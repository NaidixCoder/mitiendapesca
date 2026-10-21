<?php
/**
 * Catalogo - listado principal.
 * Render puro: filtros, facetas y productos ya llegan preparados.
 */

if (!isset($filters['q']) && isset($filters['query'])) {
  $filters['q'] = $filters['query'];
}
if (!isset($filters['orden']) && isset($filters['order'])) {
  $filters['orden'] = $filters['order'];
}

$total = (int)($total ?? ($pager['total'] ?? 0));
$page = (int)($page ?? ($pager['page'] ?? 1));
$perPage = (int)($perPage ?? ($pager['per_page'] ?? 12));
$basePath = '/productos';
$pageId = 'catalog';
$facets = $facets ?? ['categories' => []];
$totalLabel = ($total === 1) ? 'producto' : 'productos';
?>

<section class="catalog-page">
  <div class="catalog-page__inner">
    <div class="catalog-page__layout">
      <?php section('partials/catalogo/sidebar', [
        'filters' => $filters,
        'facets' => $facets,
        'basePath' => $basePath,
        'priceBounds' => ($priceBounds ?? null),
        'filterAside' => ($filterAside ?? []),
      ]); ?>

      <main class="catalog-main" id="catalog-main">
        <header class="catalog-header">
          <div class="catalog-header__intro">
            <div class="catalog-header__title-row">
              <h1 class="catalog-header__title">Productos</h1>
              <span class="catalog-header__results">(<?= $total ?>)</span>
            </div>
          </div>

          <button
            type="button"
            class="catalog-header__toggle"
            data-filter-toggle
          aria-controls="catalog-filters"
          aria-expanded="false"
          aria-label="Abrir filtros"
        >
            <?= ui_icon('filter-sliders', 'catalog-header__toggle-icon') ?>
            <span>Filtrar</span>
          </button>
        </header>

        <?php if (!empty($products)): ?>
          <?php section('partials/catalogo/topbar', [
            'filters' => $filters,
            'basePath' => $basePath,
            'sortOptions' => ($sortOptions ?? []),
            'currentSortLabel' => ($currentSortLabel ?? 'Relevancia'),
          ]); ?>
        <?php endif; ?>

        <?php section('partials/catalogo/active-filters', [
          'chips' => ($chips ?? []),
          'clearAll' => ($clearAll ?? url($basePath)),
        ]); ?>

        <?php if (empty($products)): ?>
          <?php section('partials/catalogo/empty-state'); ?>
        <?php else: ?>
          <?php section('partials/catalogo/product-grid', compact('products')); ?>
          <?php section('partials/catalogo/pagination', compact('total', 'perPage', 'page', 'basePath')); ?>
        <?php endif; ?>

        <?php if (is_file(base_path('app/Views/partials/catalogo/catalog-support.php'))): ?>
          <?php section('partials/catalogo/catalog-support', [
            'whatsUrl' => ($whatsUrl ?? ''),
          ]); ?>
        <?php endif; ?>
      </main>
    </div>
  </div>
</section>
