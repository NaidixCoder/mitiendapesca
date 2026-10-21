<?php
/** @var int $id */
/** @var array $imgs */
/** @var array $imgsImagesTab */
/** @var array $imgVariants */
/** @var bool $imagesVariantSupported */

$id   = (int)($id ?? 0);
$imgs = $imgs ?? [];

// Set completo para métricas + set filtrado para render
$imgsTabAll = $imgsImagesTab ?? $imgs ?? [];
$imgsTab = $imgsImagesTabFiltered ?? $imgsTabAll;
$imgFilterVariantId = $imgFilterVariantId ?? null; // null = Todas, 0 = General, >0 = Variante

$imgVariants = $imgVariants ?? [];
$imagesVariantSupported = (bool)($imagesVariantSupported ?? false);

// Mapa id->label para badges (evita confusión: hay una portada por alcance)
$__variantLabelMap = [];
if (is_array($imgVariants)) {
  foreach ($imgVariants as $__v) {
    $vid = (int)($__v['id'] ?? 0);
    if ($vid <= 0) continue;
    $label = (string)($__v['title'] ?? $__v['label'] ?? '');
    $sku = (string)($__v['sku'] ?? '');
    if ($label === '') $label = $sku !== '' ? $sku : ('Variante #'.$vid);
    $__variantLabelMap[$vid] = $label;
  }
}

// Upload: por defecto, siempre a General (variant_id NULL)
$variantIdField = '';

// Partial de cada ítem de la galería (server-render)
$__partial = __DIR__ . '/_gallery_item.php';

// Métricas simples (sobre el set completo)
$__total = is_array($imgsTabAll) ? count($imgsTabAll) : 0;
$__general = 0;
$__variantImages = 0;
$__variantsWithImages = [];
if (is_array($imgsTabAll)) {
  foreach ($imgsTabAll as $__im) {
    $vid = $__im['variant_id'] ?? null;
    if ($vid === null || $vid === '' || (int)$vid === 0) {
      $__general++;
    } else {
      $__variantImages++;
      $__variantsWithImages[(int)$vid] = true;
    }
  }
}
$__variantsTotal = is_array($imgVariants) ? count($imgVariants) : 0;
$__variantsCovered = count($__variantsWithImages);
$__next = url('/admin/products/edit?id=' . $id . '&tab=imagenes');
?>
<div class="av3-flow-4">

  <!-- ===== Subir imágenes (Opción B: arriba, full width) ===== -->
  <div class="card av3-pad-4 av3-md-pad-5" id="upload">
    <div class="av3-flex av3-flex-col av3-lg-flex-row av3-lg-items-start av3-lg-justify-between av3-gap-4">
      <div class="av3-min-w-0">
        <h3 class="av3-type-base av3-weight-strong">Imágenes del producto</h3>
        <p class="av3-type-caption av3-text-slate-500 av3-space-top-1">
          Subí todo en una sola pasada. Por defecto quedan en <span class="av3-weight-medium">General</span> y luego podés asignarlas a una variante desde cada tarjeta.
        </p>
      </div>
      <div class="av3-shrink-0">
        <span class="av3-inline-flex av3-items-center av3-gap-2 av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y1 av3-type-caption av3-text-slate-600">
          <span class="av3-inline-flex av3-size-h-2 av3-size-w-2 av3-radius-full av3-bg-brand"></span>
          Tip: ordená por alcance (General o Variante)
        </span>
      </div>
    </div>

    <form id="img-upload"
          method="post"
          action="<?= url('/admin/products/image-upload') ?>"
          class="av3-space-top-4"
          enctype="multipart/form-data"
          data-upload-url="<?= url('/admin/products/image-upload') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= $id ?>">
      <input type="hidden" name="variant_id" value="0">

      <div class="av3-grid av3-lg-grid-cols-12 av3-gap-4">
        <!-- Dropzone -->
        <div class="av3-lg-col-span-8">
          <label for="img-file"
                 class="av3-group av3-cursor-pointer av3-block av3-radius-xl av3-border av3-border-dashed av3-border-slate-300-80 av3-bg-white-70 av3-pad-6 av3-md-p-7 av3-text-center av3-transition av3-hover-surface-white focus-within:ring-2 focus-within:ring-brand/40 av3-hover-border-brand">
            <div class="av3-mx-auto av3-flex av3-h-11 av3-w-11 av3-items-center av3-justify-center av3-radius-full av3-bg-slate-50 av3-group-hover-bg-slate-100">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="av3-size-h-5 av3-size-w-5 av3-text-slate-500">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h10a4 4 0 004-4M7 10l5-5 5 5M12 5v14"/>
              </svg>
            </div>
            <div class="av3-space-top-3 av3-type-body av3-weight-strong av3-tracking-wide av3-text-slate-800">
              ARRASTRÁ Y SOLTÁ IMÁGENES O HACÉ CLIC PARA ELEGIR
            </div>
            <div class="av3-space-top-1 av3-type-caption av3-text-slate-500">JPG/PNG/WEBP — máx. 3MB c/u</div>
            <input id="img-file" name="file" type="file" accept="image/jpeg,image/png,image/webp" class="av3-hidden" multiple>
          </label>
        </div>

        <!-- Acciones + feedback -->
        <div class="av3-lg-col-span-4">
          <div class="av3-radius-xl av3-border av3-border-slate-200 av3-bg-white-70 av3-pad-4 av3-height-full">
            <div id="upload-actions" class="av3-hidden">
  <div class="av3-flex av3-items-center av3-gap-3">
    <button id="btn-upload" type="submit" class="btn btn-primary av3-width-full">
      Subir <span id="upload-count" class="av3-opacity-90"></span>
    </button>
    <button id="btn-upload-cancel" type="button" class="btn btn-secondary whitespace-nowrap">Cancelar</button>
  </div>
  <p class="av3-space-top-2 av3-type-caption av3-text-slate-500">
    Podés quitar imágenes de la cola con la <span class="av3-weight-strong">×</span>.
  </p>
</div>

<div id="upload-idle" class="">
  <p class="av3-type-body av3-text-slate-600">
    Elegí imágenes desde el cuadro de la izquierda. Cuando haya imágenes en la cola, acá vas a ver los botones para subir.
  </p>
</div>
            <p id="img-msg" class="av3-space-top-2 av3-type-body av3-text-slate-600"></p>
            <div class="av3-space-top-3 av3-type-caption av3-text-slate-500 av3-flow-1">
              <p>• Asigná a variantes desde el selector de cada tarjeta.</p>
              <p>• Usá selección masiva para mover o eliminar rápido.</p>
              <p>• La portada se define por alcance (General / Variante).</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Previsualizaciones (múltiples) -->
      <div id="img-preview-grid" class="av3-hidden av3-grid av3-grid-cols-2 av3-sm-grid-cols-3 av3-lg-grid-cols-6 av3-gap-3 av3-space-top-4"></div>
    </form>
  </div>

  <!-- ===== Galería (full width) ===== -->
  <div class="card av3-pad-4 av3-md-pad-5" id="gallery">
    <div class="av3-flex av3-flex-col av3-sm-flex-row av3-sm-items-start av3-sm-justify-between av3-gap-3 av3-space-bottom-4">
      <div class="av3-min-w-0">
        <h3 class="av3-weight-strong">Galería</h3>
        <p class="av3-type-caption av3-text-slate-500 av3-space-top-1">
          Subí todas las imágenes y luego, en cada una, elegí a qué variante pertenece. La portada se define por alcance (General y cada variante).
        </p>
      </div>

      <div class="av3-shrink-0">
        <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2 av3-justify-start av3-sm-justify-end av3-type-caption av3-text-slate-600">
          <span class="img-kpi">
            Total: <span id="img-count-total" class="av3-weight-strong av3-text-slate-800"><?= (int)$__total ?></span>
          </span>
          <span class="img-kpi">
            General: <span id="img-count-general" class="av3-weight-strong av3-text-slate-800"><?= (int)$__general ?></span>
          </span>
          <?php if (!empty($imgVariants)): ?>
            <span class="img-kpi">
              Variantes con imágenes: <span id="img-count-variants-covered" class="av3-weight-strong av3-text-slate-800"><?= (int)$__variantsCovered ?></span>/<span id="img-count-variants-total" class="av3-text-slate-700"><?= (int)$__variantsTotal ?></span>
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php
      // Para ordenar (drag&drop) exigimos filtrar por un alcance específico.
      $__sortEnabled = ($imgFilterVariantId !== null);
      $__sortVariantId = $__sortEnabled ? (int)$imgFilterVariantId : null; // 0 = General, >0 = Variante
    ?>

    <div class="img-toolbar">
      <div class="av3-flex av3-items-center av3-gap-2">
        <label for="img-filter-select" class="av3-type-caption av3-weight-strong av3-text-slate-600">Ver:</label>
        <select id="img-filter-select" name="img_filter_variant_id" class="input av3-w-auto">
          <option value="all" <?= ($imgFilterVariantId === null) ? 'selected' : '' ?>>Todas</option>
          <option value="0" <?= ($imgFilterVariantId === 0) ? 'selected' : '' ?>>General</option>
          <?php foreach (($imgVariants ?? []) as $__v): ?>
            <?php $vid = (int)($__v['id'] ?? 0); if ($vid <= 0) continue; ?>
            <?php $vlabel = (string)($__variantLabelMap[$vid] ?? ('Variante #'.$vid)); ?>
            <option value="<?= (int)$vid ?>" <?= ($imgFilterVariantId === $vid) ? 'selected' : '' ?>><?= e($vlabel) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="av3-type-caption av3-text-slate-500 av3-hidden av3-md-inline">Ordenar disponible solo por alcance (General o Variante).</span>
      </div>

      <div id="img-bulkbar" class="img-bulkbar is-idle">
        <div class="img-bulkbar__left">
          <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-caption av3-text-slate-700">
            <input type="checkbox" id="img-select-all" class="img-check">
            <span class="av3-weight-medium">Seleccionar todo</span>
          </label>
          <span class="img-bulkbar__count av3-type-caption av3-text-slate-700">
            <span id="img-selected-count" class="av3-weight-strong">0</span>
            <span class="av3-text-slate-600">seleccionada(s)</span>
          </span>
        </div>

        <div class="img-bulkbar__right">
          <form id="img-bulk-assign-form" method="post" action="<?= url('/admin/products/image-bulk-assign') ?>" class="av3-inline-flex av3-items-center av3-gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">
            <input type="hidden" name="image_ids" value="" data-role="bulk-ids" class="js-bulk-ids">
            <input type="hidden" name="next" value="<?= e($__next) ?>">
            <select name="variant_id" class="input av3-w-auto">
              <option value="0">General</option>
              <?php foreach (($imgVariants ?? []) as $__v): ?>
                <?php $vid = (int)($__v['id'] ?? 0); if ($vid <= 0) continue; ?>
                <?php $vlabel = (string)($__variantLabelMap[$vid] ?? ('Variante #'.$vid)); ?>
                <option value="<?= (int)$vid ?>"><?= e($vlabel) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Asignar</button>
          </form>

          <form id="img-bulk-delete-form" method="post" action="<?= url('/admin/products/image-bulk-delete') ?>" class="av3-inline-flex av3-items-center av3-gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">
            <input type="hidden" name="image_ids" value="" data-role="bulk-ids" class="js-bulk-ids">
            <input type="hidden" name="next" value="<?= e($__next) ?>">
            <button type="submit" class="btn btn-danger">Eliminar</button>
          </form>

          <button type="button" id="img-bulk-clear" class="btn btn-ghost">Limpiar</button>
        </div>
      </div>
    </div>

    
    <!-- Template para render JS post-upload (evita F5 y asegura select/checkbox/actions) -->
    <template id="tpl-gallery-item">
      <figure draggable="false" tabindex="0" aria-grabbed="false"
              class="img-card"
              data-id="">
        <button type="button"
                class="js-drag-handle img-drag-handle av3-hidden"
                title="Arrastrar para reordenar" aria-label="Arrastrar">⋮⋮</button>

        <label class="av3-absolute top-2 left-2 av3-z-10 av3-inline-flex av3-items-center av3-gap-2 av3-cursor-pointer av3-select-none"
               title="Seleccionar" aria-label="Seleccionar imagen">
          <input type="checkbox" class="img-check img-select" value="">
        </label>

        <img class="av3-width-full av3-aspect-square av3-object-cover av3-radius-card av3-bg-slate-50" src="" alt="">
        <span class="cover-badge av3-hidden img-badge">
          Portada · General
        </span>

        <?php if (!empty($imgVariants)): ?>
          <form class="img-assign av3-space-top-2 av3-flow-1" method="post" action="<?= url('/admin/products/image-assign') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">
            <input type="hidden" name="image_id"  value="">
            <input type="hidden" name="next" value="<?= e($__next) ?>">
            <label class="img-label">Asignar a</label>
            <select name="variant_id" class="js-assign-select img-assign-select">
              <option value="0">General</option>
              <?php foreach (($imgVariants ?? []) as $__v): ?>
                <?php $vid = (int)($__v['id'] ?? 0); if ($vid <= 0) continue; ?>
                <?php $lbl = trim((string)($__v['title'] ?? '')); if ($lbl==='') $lbl = trim((string)($__v['sku'] ?? '')); if ($lbl==='') $lbl = 'Variante #'.$vid; ?>
                <option value="<?= (int)$vid ?>"><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>

        <div class="img-actions">
          <form class="img-action" method="post" action="<?= url('/admin/products/image-delete') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">
            <input type="hidden" name="variant_id" value="">
            <input type="hidden" name="image_id"  value="">
            <input type="hidden" name="next" value="<?= e($__next) ?>">
            <button type="submit" class="btn-xs btn-danger">Eliminar</button>
          </form>

          <form class="img-action" method="post" action="<?= url('/admin/products/image-cover') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">
            <input type="hidden" name="variant_id" value="">
            <input type="hidden" name="image_id"  value="">
            <input type="hidden" name="next" value="<?= e($__next) ?>">
            <button type="submit" class="btn-xs btn-secondary js-cover">Hacer portada</button>
          </form>
        </div>
      </figure>
    </template>

<div id="img-gallery"
           class="av3-grid av3-grid-cols-2 av3-sm-grid-cols-3 av3-lg-grid-cols-4 av3-xl-grid-cols-5 av3-gap-4"
           data-disable-sort="<?= $__sortEnabled ? '0' : '1' ?>"
           data-variant-id="<?= $__sortEnabled ? (string)(int)$__sortVariantId : '' ?>"
           data-sort-url="<?= url('/admin/products/image-sort') ?>"
           data-delete-url="<?= url('/admin/products/image-delete') ?>"
           data-cover-url="<?= url('/admin/products/image-cover') ?>"
           data-assign-url="<?= url('/admin/products/image-assign') ?>">
        <?php if (empty($imgsTab)): ?>
          <div class="av3-col-span-full av3-radius-xl av3-border av3-border-dashed av3-border-slate-300-80 av3-bg-white-60 av3-p-10 av3-text-center">
        <div class="av3-mx-auto av3-flex av3-size-h-12 av3-size-w-12 av3-items-center av3-justify-center av3-radius-full av3-bg-slate-50 av3-space-bottom-3">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="av3-size-h-6 av3-size-w-6 av3-text-slate-500">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h3l2-2h8l2 2h3v12H3V7z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a3 3 0 100 6 3 3 0 000-6z"/>
          </svg>
        </div>
        <p class="av3-type-body av3-weight-medium av3-text-slate-800">Todavía no subiste imágenes.</p>
        <p class="av3-type-caption av3-text-slate-500 av3-space-top-1">Usá el panel de arriba para cargarlas. Después podés asignarlas a variantes desde cada imagen.</p>
      </div>
        <?php else: ?>
          <?php foreach ($imgsTab as $im): ?>
          <?php
            $product_id = $id;
            $variant_id = $im['variant_id'] ?? null; // scope real
            $enableSort = $__sortEnabled;
            $variantLabelMap = $__variantLabelMap;
            if (is_file($__partial)) include $__partial;
          ?>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
  </div>
</div>
