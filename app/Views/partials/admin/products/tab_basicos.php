<?php /** @var array $p */ /** @var array $brands */ /** @var array $cats */ /** @var int $id */ ?>
<?php
$catalogTagGroups = is_array($catalogTagGroups ?? null) ? $catalogTagGroups : [];
$productTagIds = array_map('intval', is_array($productTagIds ?? null) ? $productTagIds : []);
?>

<div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
  <label class="av3-block av3-md-col-span-2">Nombre
    <input id="product-name" class="input av3-width-full" name="name" value="<?= e($p['name'] ?? '') ?>" required>
  </label>

  <label class="av3-block">Slug
    <input id="product-slug" class="input av3-width-full" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto desde nombre si lo dejas vacio">
  </label>

  <label class="av3-block">Codigo interno
    <input class="input av3-width-full" name="internal_code" value="<?= e($p['internal_code'] ?? '') ?>" placeholder="Opcional (unico). Ej: INT-000123">
    <p class="av3-space-top-1 av3-type-caption av3-text-gray-500">Opcional. Util para tu gestion interna. No se muestra al cliente.</p>
  </label>

  <div class="av3-block">
    <div class="av3-flex av3-items-end av3-justify-between av3-gap-2">
      <label class="av3-block av3-flex-1">Marca
        <select name="brand_id" class="input av3-width-full" id="product-brand-select">
          <option value="">-</option>
          <?php foreach (($brands ?? []) as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= ((int)($p['brand_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
              <?= e($b['name'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <button type="button"
              class="btn btn-soft av3-size-h-10 whitespace-nowrap"
              id="brand-quick-open"
              aria-haspopup="dialog"
              aria-controls="brand-quick-modal">
        + Agregar
      </button>
    </div>
    <p class="av3-space-top-1 av3-type-caption av3-text-gray-500">Si la marca no existe, puedes crearla sin salir del editor.</p>
  </div>

  <div id="brand-quick-modal" class="av3-fixed inset-0 av3-z-50 av3-hidden" role="dialog" aria-modal="true" aria-labelledby="brand-quick-title">
    <div class="av3-absolute inset-0 av3-bg-black-40" data-brand-quick-close></div>

    <div class="av3-relative av3-mx-auto av3-mt-24 av3-w--92 av3-width-max-lg av3-radius-xl av3-surface-white av3-shadow-xl">
      <div class="av3-pad-5 av3-border-bottom">
        <h3 id="brand-quick-title" class="av3-type-lg av3-weight-strong">Agregar marca</h3>
        <p class="av3-space-top-1 av3-type-body av3-text-gray-600">Escribe el nombre. El slug se genera automaticamente.</p>
      </div>

      <div class="av3-pad-5 av3-flow-3">
        <label class="av3-block">
          <span class="av3-type-body av3-weight-medium">Nombre de la marca</span>
          <input id="brand-quick-name" class="input av3-width-full av3-space-top-1" placeholder="Ej: Shimano" autocomplete="off" maxlength="120">
        </label>

        <p id="brand-quick-error" class="av3-hidden av3-type-body av3-text-var-danger"></p>
      </div>

      <div class="av3-pad-5 av3-border-top av3-flex av3-items-center av3-justify-end av3-gap-2">
        <button type="button" class="btn btn-soft" data-brand-quick-close>Cancelar</button>
        <button type="button" class="btn btn-brand" id="brand-quick-save">Guardar marca</button>
      </div>
    </div>
  </div>

  <label class="av3-block av3-md-col-span-2">Categoria
    <div class="av3-flex av3-items-center av3-gap-2">
      <select name="category_id" id="product-category-select" class="input av3-width-full" required>
        <option value="">Seleccione...</option>
        <?php foreach (($cats ?? []) as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($p['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-soft whitespace-nowrap" id="category-quick-open">+ Agregar</button>
    </div>
  </label>

  <label class="av3-block">Proveedor
    <select class="input av3-width-full" name="supplier_id">
      <option value="">- Sin proveedor -</option>
      <?php foreach (($suppliers ?? []) as $s): ?>
        <?php $sid = (int)($s['id'] ?? 0); ?>
        <option value="<?= $sid ?>" <?= (int)($p['supplier_id'] ?? 0) === $sid ? 'selected' : '' ?>>
          <?= e($s['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="av3-space-top-1 av3-type-caption av3-text-gray-500">Puedes asignar un proveedor ahora o dejarlo vacio y completarlo luego.</p>
  </label>

  <div id="category-quick-modal" class="av3-fixed inset-0 av3-z--70 av3-hidden" aria-hidden="true">
    <div class="av3-absolute inset-0 av3-bg-black-40" data-category-quick-close></div>
    <div class="av3-relative av3-mx-auto av3-mt-24 av3-w--92vw av3-width-max-lg av3-radius-xl av3-surface-white av3-shadow-xl">
      <div class="av3-pad-5 av3-border-bottom">
        <div class="av3-flex av3-items-center av3-justify-between">
          <h3 class="av3-type-lg av3-weight-strong">Crear categoria</h3>
          <button type="button" class="btn btn-ghost" data-category-quick-close>x</button>
        </div>
        <p class="av3-space-top-1 av3-type-body av3-text-slate-600">Elige si quieres crearla como categoria raiz o asignarle una categoria padre. Luego podras editarla desde Categorias.</p>
      </div>

      <div class="av3-pad-5 av3-flow-3">
        <label class="av3-block">
          <span class="av3-type-body av3-text-slate-700">Nombre</span>
          <input type="text" id="category-quick-name" class="input av3-width-full" placeholder="Ej: Reeles" maxlength="120" />
        </label>
        <label class="av3-block">
          <span class="av3-type-body av3-text-slate-700">Categoria padre</span>
          <select id="category-quick-parent" class="input av3-width-full">
            <option value="">(Sin padre / raiz)</option>
            <?php foreach (($cats ?? []) as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= e($c['name'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
          <p class="av3-space-top-1 av3-type-caption av3-text-slate-500">Opcional. Puedes crearla como raiz o colgarla de una categoria existente.</p>
        </label>
        <p id="category-quick-error" class="av3-type-body av3-text-var-danger av3-hidden"></p>
      </div>

      <div class="av3-pad-5 av3-border-top av3-flex av3-items-center av3-justify-end av3-gap-2">
        <button type="button" class="btn btn-soft" data-category-quick-close>Cancelar</button>
        <button type="button" class="btn btn-brand" id="category-quick-save">Guardar categoria</button>
      </div>
    </div>
  </div>

  <label class="av3-block av3-md-col-span-2">Descripcion corta
    <input class="input av3-width-full" name="short_desc" value="<?= e($p['short_desc'] ?? '') ?>">
  </label>

  <?php
  $highlightTokensRaw = (string)($p['highlight_tokens_json'] ?? '');
  $highlightTokens = [];
  if ($highlightTokensRaw !== '') {
    $decoded = json_decode($highlightTokensRaw, true);
    if (is_array($decoded)) {
      foreach ($decoded as $token) {
        $token = trim((string)$token);
        if ($token !== '') $highlightTokens[] = $token;
      }
    }
  }
  ?>
  <label class="av3-block av3-md-col-span-2">Tokens destacados
    <textarea class="input av3-width-full min-h-[96px]" name="highlight_tokens" placeholder="Un token por linea. Ej:\nPotente\nEconomico\nTerminacion cuidada"><?= e(implode("\n", $highlightTokens)) ?></textarea>
    <p class="av3-space-top-1 av3-type-caption av3-text-gray-500">Se muestran debajo de la descripcion corta en la ficha del producto.</p>
  </label>

  <div class="av3-block av3-md-col-span-2">
    <div class="av3-radius-xl av3-border av3-border-var-line-soft av3-bg--color-var---panel-subtle av3-pad-4">
      <div class="av3-space-bottom-3">
        <h3 class="av3-type-base av3-weight-strong">Clasificacion del catalogo</h3>
        <p class="av3-space-top-1 av3-type-body av3-text-var-muted">Asigna tags generales al producto. Sirven para filtro y clasificacion. No generan variantes.</p>
      </div>

      <?php if ($catalogTagGroups !== []): ?>
        <div class="av3-grid av3-gap-4 av3-md-grid-cols-2">
          <?php foreach ($catalogTagGroups as $group): ?>
            <fieldset class="av3-radius-xl av3-border av3-border-var-line-soft av3-surface-white av3-pad-4">
              <legend class="av3-px-1 av3-type-body av3-weight-strong"><?= e((string)($group['name'] ?? '')) ?></legend>
              <div class="av3-space-top-2 av3-grid av3-gap-2">
                <?php foreach ((array)($group['tags'] ?? []) as $tag): ?>
                  <?php $tagId = (int)($tag['id'] ?? 0); ?>
                  <label class="av3-flex av3-items-center av3-gap-2 av3-type-body">
                    <input type="checkbox" name="catalog_tag_ids[]" value="<?= $tagId ?>" <?= in_array($tagId, $productTagIds, true) ? 'checked' : '' ?>>
                    <span><?= e((string)($tag['name'] ?? '')) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="av3-radius-xl av3-border av3-border-dashed av3-border-var-line-soft av3-surface-white av3-pad-x4 av3-py-5 av3-type-body av3-text-var-muted">
          <p>Todavia no hay grupos y tags activos para asignar.</p>
          <p class="av3-space-top-1">Primero crea grupos y tags en <a class="av3-weight-medium av3-text--color-var---brand underline" href="<?= url('/admin/catalog-tags') ?>">Tags</a>. Cuando existan, apareceran aqui para este producto.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <label class="av3-block av3-md-col-span-2">Descripcion larga
    <textarea class="input av3-width-full min-h-[140px]" name="long_desc"><?= e($p['long_desc'] ?? '') ?></textarea>
  </label>

  <!-- El estado Activo se define en la pestana Publicacion (Paso 6). -->
</div>
