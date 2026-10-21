<?php /** @var string $q */ ?>
<section class="catalog-panel catalog-search" aria-label="Buscar productos">
  <form method="get" action="<?= url('/admin/products') ?>" class="catalog-search__form">
    <label for="products-index-search">
      <span>Encontrar un producto</span>
      <span class="catalog-search__field">
        <span aria-hidden="true">⌕</span>
        <input id="products-index-search" name="q" type="search" value="<?= e($q) ?>" placeholder="Nombre, código o SKU">
      </span>
    </label>
    <button type="submit" class="catalog-button catalog-button--primary">Buscar productos</button>
    <?php if ($q !== ''): ?><a href="<?= url('/admin/products') ?>" class="catalog-button catalog-button--outline">Limpiar búsqueda</a><?php endif; ?>
  </form>
</section>
