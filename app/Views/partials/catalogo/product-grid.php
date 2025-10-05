<div class="container mx-auto px-3 sm:px-4 lg:px-6">
  <div id="catalog-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 py-4">
    <?php foreach ($products as $p): section('catalogo/product-card', ['p'=>$p]); endforeach; ?>
  </div>
</div>
