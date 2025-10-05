<?php
/** @var int $id */ /** @var array $imgs */
if (!$id): ?>
  <div class="card p-4">Primero guarda el producto para poder subir imágenes.</div>
<?php else: ?>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Subida -->
    <div class="card p-4">
      <h3 class="font-semibold mb-2">Subir imagen</h3>
      <form id="img-form" class="space-y-3" action="<?= url('/admin/products/image-upload') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int)$id ?>">
        <input class="input w-full" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
        <button class="btn-primary">Subir</button>
        <p class="text-sm text-[color:var(--muted)]">JPG/PNG/WEBP — máx. 3MB</p>
      </form>
      <div id="img-msg" class="text-sm mt-2"></div>
    </div>

    <!-- Galería -->
    <div class="lg:col-span-2 card p-4">
      <h3 class="font-semibold mb-3">Galería</h3>
      <div id="gallery" class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <?php if (!empty($imgs)): ?>
          <?php foreach ($imgs as $im): ?>
            <?php section('admin/products/_gallery_item', ['im'=>$im, 'product_id'=>$id]); ?>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-sm text-[color:var(--muted)]">Sin imágenes aún.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
