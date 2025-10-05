<?php /** @var array $p */ /** @var array $brands */ /** @var array $cats */ ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <label class="block">SKU
    <input class="input w-full" name="sku" value="<?= e($p['sku'] ?? '') ?>" required>
  </label>

  <label class="block">Código de barras (GTIN/EAN)
    <input class="input w-full" name="barcode" value="<?= e($p['barcode'] ?? '') ?>">
  </label>

  <label class="block md:col-span-2">Nombre
    <input id="product-name" class="input w-full" name="name" value="<?= e($p['name'] ?? '') ?>" required>
  </label>

  <label class="block">Slug
    <input id="product-slug" class="input w-full" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto desde nombre si lo dejas vacío">
  </label>

  <label class="block">Marca
    <select name="brand_id" class="input w-full">
      <option value="">—</option>
      <?php foreach (($brands ?? []) as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= ((int)($p['brand_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
          <?= e($b['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="block">Categoría
    <select name="category_id" class="input w-full" required>
      <option value="">Seleccione…</option>
      <?php foreach (($cats ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)($p['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
          <?= e($c['name'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="block md:col-span-2">Descripción corta
    <input class="input w-full" name="short_desc" value="<?= e($p['short_desc'] ?? '') ?>">
  </label>

  <label class="block md:col-span-2">Descripción larga
    <textarea class="input w-full min-h-[140px]" name="long_desc"><?= e($p['long_desc'] ?? '') ?></textarea>
  </label>

  <label class="block">Precio
    <input class="input w-full" type="number" name="price" value="<?= (int)($p['price'] ?? 0) ?>" required>
  </label>
  <label class="block">Precio lista (MSRP)
    <input class="input w-full" type="number" name="list_price" value="<?= e($p['list_price'] ?? '') ?>">
  </label>
  <label class="block">Costo (interno)
    <input class="input w-full" type="number" name="cost" value="<?= e($p['cost'] ?? '') ?>">
  </label>

  <label class="inline-flex items-center gap-2 md:col-span-2">
    <input type="checkbox" name="is_active" <?= !empty($p['is_active']) ? 'checked' : '' ?>>
    <span>Activo</span>
  </label>
</div>
