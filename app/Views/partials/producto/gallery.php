<?php /** @var array $product */ 
$images = $product['images'] ?? [];
?>
<div class="space-y-3">
  <div class="relative aspect-square rounded-2xl overflow-hidden bg-gray-100">
    <?php if (!empty($images)): ?>
      <img id="pg-main"
           class="w-full h-full object-cover"
           src="<?= public_url($images[0]) ?>"
           data-idx="0"
           alt="<?= e($product['title'] ?? '') ?>">
      <!-- Controles -->
      <button type="button" class="pg-prev absolute left-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded bg-white/70">‹</button>
      <button type="button" class="pg-next absolute right-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded bg-white/70">›</button>
    <?php else: ?>
      <img class="w-full h-full object-cover" src="<?= asset('img/placeholder.jpg') ?>" alt="Sin imagen">
    <?php endif; ?>
  </div>

  <?php if (count($images) > 1): ?>
    <div id="pg-thumbs" class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-2">
      <?php foreach ($images as $i => $p): ?>
        <button type="button"
                class="pg-thumb border rounded overflow-hidden <?= $i===0 ? 'ring-2 ring-brand' : '' ?>"
                data-idx="<?= $i ?>">
          <img class="w-full h-full object-cover" src="<?= public_url($p) ?>" alt="thumb <?= $i+1 ?>">
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- JS de la galería -->
<script src="<?= asset('js/catalog/product_gallery.js') ?>" defer></script>
