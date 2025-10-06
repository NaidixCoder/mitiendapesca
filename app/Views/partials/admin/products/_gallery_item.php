<?php /** @var array $im */ /** @var int $product_id */ ?>
<figure class="relative border rounded overflow-hidden" data-id="<?= (int)$im['id'] ?>">
  <img
    class="w-full h-full object-cover"
    src="<?= url($im['path']) ?>"
    alt="<?= e($im['alt'] ?? '') ?>"
  >
  <?php if ((int)$im['is_cover'] === 1): ?>
    <figcaption class="absolute top-2 left-2 px-2 py-1 text-xs bg-brand-yellow" style="border-radius:6px;">Portada</figcaption>
  <?php endif; ?>
  <div class="absolute right-2 bottom-2 flex gap-2">
    <!-- Hacer portada -->
    <form class="img-action" method="post" action="<?= url('/admin/products/image-cover') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
      <input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>">
      <button class="btn-secondary text-xs" <?= (int)$im['is_cover']===1 ? 'disabled' : '' ?>>
        <?= (int)$im['is_cover']===1 ? 'Portada' : 'Hacer portada' ?>
      </button>
    </form>
    <!-- Eliminar -->
    <form class="img-action" method="post" action="<?= url('/admin/products/image-delete') ?>" onsubmit="return confirm('Eliminar imagen?')">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
      <input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>">
      <button class="btn-secondary text-xs" style="border-color: var(--danger); color: var(--danger);">Eliminar</button>
    </form>
  </div>
</figure>
