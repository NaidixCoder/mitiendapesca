<?php
/** @var array $filters */
/** @var array $facets */
/** @var string|null $basePath */
/** @var array{min?:int,max?:int}|null $priceBounds */
/** @var array|null $filterAside */

$basePath = $basePath ?? '/productos';
$priceBounds = $priceBounds ?? ['min' => 0, 'max' => 0];
$filterAside = is_array($filterAside ?? null) ? $filterAside : [];
?>

<aside
  id="catalog-filters"
  class="catalog-sidebar"
  aria-hidden="true"
  aria-label="Filtros de productos"
>
  <div class="catalog-sidebar__inner">
    <div class="catalog-sidebar__mobile-header">
      <h3 class="catalog-sidebar__mobile-title">Filtros</h3>

      <button
        type="button"
        class="catalog-sidebar__mobile-close"
        data-filter-toggle
        aria-label="Cerrar filtros"
      >
        <?= ui_icon('close-solid') ?>
      </button>
    </div>

    <div class="catalog-sidebar__panel">
      <div class="catalog-sidebar__main">
        <?php section('partials/catalogo/facet-active-summary', [
          'summary' => ($filterAside['active_summary'] ?? []),
        ]); ?>

        <?php section('partials/catalogo/facet-cats', [
          'filters' => $filters,
          'facets' => $facets,
          'basePath' => $basePath,
        ]); ?>
      </div>

      <div class="catalog-sidebar__footer">
        <?php section('partials/catalogo/facet-refine', [
          'filters' => $filters,
          'facets' => $facets,
          'basePath' => $basePath,
          'priceBounds' => $priceBounds,
          'filterAside' => $filterAside,
        ]); ?>
      </div>
    </div>
  </div>
</aside>
