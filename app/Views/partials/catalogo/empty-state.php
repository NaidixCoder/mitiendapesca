<div class="catalog-empty">
  <h3 class="catalog-empty__title">No encontramos resultados</h3>
  <div class="catalog-empty__visual" aria-hidden="true">
    <?= ui_illustration('empty-search', 'catalog-empty__visual-svg', ['aria-hidden' => 'true', 'focusable' => 'false']) ?>
  </div>
  
  <p class="catalog-empty__copy">
    Probá ajustando los filtros o cambiando la búsqueda para encontrar productos.
  </p>
  <div class="catalog-empty__actions">
    <a href="<?= e($clearUrl ?? url('/productos')) ?>" class="v2-btn v2-btn--primary">
      Ver todo el catálogo
    </a>
  </div>
</div>
