<?php
/** @var array $seo */
/** @var array $category */
/** @var array $blocks */
/** @var array $products */
/** @var array $pager */
/** @var array $filters */
/** @var array $facets */

$basePath = '/categoria';
$pageId = 'catalog';
$total = (int)($total ?? ($pager['total'] ?? (is_array($products) ? count($products) : 0)));
$page = (int)($page ?? ($pager['page'] ?? 1));
$perPage = (int)($perPage ?? ($pager['per_page'] ?? 12));
$totalLabel = ($total === 1) ? 'producto' : 'productos';
$facets = $facets ?? ['categories' => []];
$categoryName = (string)($category['name'] ?? 'Categoria');
$categoryDescription = (string)($seo['meta']['description'] ?? '');
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
              <h1 class="catalog-header__title"><?= e($categoryName) ?></h1>
              <span class="catalog-header__results">(<?= $total ?>)</span>
            </div>
            <?php if ($categoryDescription !== ''): ?>
              <p class="catalog-header__description"><?= e($categoryDescription) ?></p>
            <?php endif; ?>
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
            <span>Categorias</span>
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
          <?php section('partials/catalogo/empty-state', ['clearUrl' => ($clearAll ?? url($basePath))]); ?>
        <?php else: ?>
          <?php section('partials/catalogo/product-grid', ['products' => $products]); ?>
          <?php section('partials/catalogo/pagination', compact('total', 'perPage', 'page', 'basePath')); ?>
        <?php endif; ?>
      </main>
    </div>
    <?php if (!empty($editorial)): ?>
      <?php section('partials/catalogo/editorial-blocks', [
        'editorial' => $editorial,
      ]); ?>
    <?php endif; ?>
    <?php if (is_file(base_path('app/Views/partials/catalogo/catalog-support.php'))): ?>
      <?php section('partials/catalogo/catalog-support', [
        'whatsUrl' => ($whatsUrl ?? ''),
      ]); ?>
    <?php endif; ?>
  </div>
</section>
