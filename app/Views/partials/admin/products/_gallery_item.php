<?php
// USED: requerido por galería admin productos

/** @var array $im */ /** @var int $product_id */ /** @var int|null $variant_id */ ?>
<?php
  $imgId   = (int)($im['id'] ?? 0);
  $isCover = (int)($im['is_cover'] ?? 0) === 1;
  $path = (string)($im['path'] ?? '');
  $variantIdField = (int)($variant_id ?? 0);
  $variantIdField = $variantIdField > 0 ? (string)$variantIdField : '';

  

  $enableSort = (bool)($enableSort ?? true);
  $variantLabelMap = $variantLabelMap ?? [];
  $vidInt = $variantIdField !== '' ? (int)$variantIdField : 0;
  $scopeLabel = $vidInt > 0 ? (string)($variantLabelMap[$vidInt] ?? 'Variante') : 'General';
  $coverBadgeText = 'Portada · '.$scopeLabel;
// Opciones de asignación (General + Variantes del producto)
  $imgVariants = $imgVariants ?? [];
  $currentVariantSelect = 0;
  if (array_key_exists('variant_id', $im)) {
    $currentVariantSelect = ($im['variant_id'] === null || $im['variant_id'] === '') ? 0 : (int)$im['variant_id'];
  } elseif (isset($variant_id)) {
    $currentVariantSelect = ((int)$variant_id) > 0 ? (int)$variant_id : 0;
  }


  // Thumb (si existe) para mejorar performance en el admin
  $thumb = '';
  $base = preg_replace('/\.(jpe?g|png|webp)$/i', '', $path);
  if ($base) {
    foreach ([$base.'_thumb.webp', $base.'_thumb.jpg', $base.'_thumb.jpeg'] as $cand) {
      if (is_file(base_path('public_html/'.$cand))) { $thumb = $cand; break; }
    }
  }
  $src     = url($thumb ?: $path);
  $alt     = e($im['alt'] ?? '');
?>
<figure draggable="<?= $enableSort ? 'true' : 'false' ?>" tabindex="0" aria-grabbed="false"
        class="img-card"
        data-id="<?= $imgId ?>">
  <?php if ($enableSort): ?>
  <button type="button"
            class="js-drag-handle img-drag-handle"
            title="Arrastrar para reordenar" aria-label="Arrastrar">⋮⋮</button>
  <?php endif; ?>

  <label class="av3-absolute top-2 left-2 av3-z-10 av3-inline-flex av3-items-center av3-gap-2 av3-cursor-pointer av3-select-none"
         title="Seleccionar" aria-label="Seleccionar imagen">
    <input type="checkbox" class="img-check img-select" value="<?= $imgId ?>">
  </label>

  <img class="av3-width-full av3-aspect-square av3-object-cover av3-radius-card av3-bg-slate-50" src="<?= $src ?>" alt="<?= $alt ?>">
  <span class="cover-badge img-badge <?= $isCover ?'' : 'av3-hidden' ?>">
    <?= e($coverBadgeText) ?>
  </span>
  
  <?php if (!empty($imgVariants)): ?>
  <form class="img-assign av3-space-top-2 av3-flow-1" method="post" action="<?= url('/admin/products/image-assign') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
    <input type="hidden" name="image_id"  value="<?= $imgId ?>">
    <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
    <label class="img-label">Asignar a</label>
    <select name="variant_id" class="js-assign-select img-assign-select">
      <option value="0" <?= $currentVariantSelect === 0 ? 'selected' : '' ?>>General</option>
      <?php foreach ($imgVariants as $v): $vid = (int)($v['id'] ?? 0); if ($vid<=0) continue; $lbl = trim((string)($v['title'] ?? '')); if ($lbl==='') $lbl = trim((string)($v['sku'] ?? '')); if ($lbl==='') $lbl = 'Variante #'.$vid; ?>
        <option value="<?= $vid ?>" <?= $currentVariantSelect === $vid ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>

  <div class="img-actions">
    <form class="img-action" method="post" action="<?= url('/admin/products/image-delete') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
      <input type="hidden" name="variant_id" value="<?= e($variantIdField) ?>">
      <input type="hidden" name="image_id"  value="<?= $imgId ?>">
    <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
      <button type="submit" class="btn-xs btn-danger">Eliminar</button>
    </form>
    <form class="img-action" method="post" action="<?= url('/admin/products/image-cover') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
      <input type="hidden" name="variant_id" value="<?= e($variantIdField) ?>">
      <input type="hidden" name="image_id"  value="<?= $imgId ?>">
    <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
      <button type="submit" class="btn-xs btn-secondary js-cover <?= $isCover ?'av3-opacity-60 pointer-events-none' : '' ?>" <?= $isCover ? 'disabled' : '' ?>>
        <?= $isCover ? 'Portada' : 'Hacer portada' ?>
      </button>
    </form>
  </div>
</figure>
