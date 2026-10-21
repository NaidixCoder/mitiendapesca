<?php
// Pestana 6 - Publicacion
/** @var array $p */
/** @var int $id */
/** @var int $variantsCount */
/** @var array|null $wizardStatus */
/** @var array $publicationPreview */

$variantsCount = (int)($variantsCount ?? 0);
$isActiveRaw = !empty($p['is_active']);
$saleMode = (string)($p['sale_mode'] ?? 'simple');

$preview = is_array($publicationPreview ?? null) ? $publicationPreview : [];

$blockers = (array)($preview['blockers'] ?? []);
$publishable = array_key_exists('publishable', $preview) ? (bool)$preview['publishable'] : null;
$warnings = (array)($preview['warnings'] ?? []);
$metaImagesTotal = array_key_exists('metaImagesTotal', $preview) ? (int)$preview['metaImagesTotal'] : null;
$metaImagesGeneral = array_key_exists('metaImagesGeneral', $preview) ? (int)$preview['metaImagesGeneral'] : null;
$coverUrl = (string)($preview['coverUrl'] ?? '');
$previewPrice = (isset($preview['previewPrice']) && is_numeric($preview['previewPrice'])) ? (float)$preview['previewPrice'] : null;

$isEffectivelyVisible = $isActiveRaw && ($publishable !== false);
$disablePublish = ($publishable === false);
$statusLabel = $isEffectivelyVisible ? 'Activo' : 'Inactivo';
$statusBadgeClass = $isEffectivelyVisible
    ? 'av3-border-emerald-200 av3-bg-emerald-50 av3-text-emerald-700'
    : 'av3-border-slate-200 av3-bg-slate-50 av3-text-slate-600';
$publicUrl = function_exists('url') ? url('/producto?id=' . (int)$id) : ('/producto?id=' . (int)$id);
?>

<div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4">
  <div class="av3-radius-xl av3-border av3-border-white-10 av3-surface-white av3-pad-5 av3-md-pad-6 av3-shadow-sm">
    <div class="av3-flex av3-items-start av3-justify-between av3-gap-4">
      <div>
        <h3 class="av3-type-base av3-weight-strong av3-text-slate-900">Publicacion</h3>
        <p class="av3-space-top-1 av3-type-body av3-text-slate-600">
          Activa el producto <strong>cuando este listo para venderse</strong>. Si no cumple las condiciones de publicacion, permanece inactivo en la tienda.
        </p>
      </div>
      <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-px-2_5 av3-pad-y1 av3-type-caption av3-weight-medium <?= e($statusBadgeClass) ?>">
        <?= e($statusLabel) ?>
      </span>
    </div>

    <div class="av3-space-top-5">
      <?php if ($publishable === false && !empty($blockers)): ?>
        <div class="av3-space-bottom-4 av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-3">
          <div class="av3-type-body av3-weight-strong av3-text-amber-900">No podes publicar todavia</div>
          <p class="av3-space-top-1 av3-type-body av3-text-amber-900-80">Completa lo siguiente para habilitar la publicacion:</p>
          <ul class="av3-space-top-2 av3-flow-1 av3-type-body av3-text-amber-900-90 list-disc av3-pad-left-5">
            <?php foreach ($blockers as $b): ?>
              <li><?= e((string)$b) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($isActiveRaw && $publishable === false): ?>
        <div class="av3-space-bottom-4 av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-3 av3-type-body av3-text-amber-900">
          Este producto quedo marcado como activo internamente, pero hoy esta <strong>inactivo en la tienda</strong> porque no cumple las condiciones de publicacion.
          Guarda esta pestana para dejarlo tambien como inactivo en admin.
        </div>
      <?php endif; ?>

      <?php if (!empty($warnings)): ?>
        <div class="av3-space-bottom-3 av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-x3 av3-pad-y2 av3-type-body av3-text-amber-900">
          <div class="av3-weight-strong av3-space-bottom-1">Sugerencia</div>
          <ul class="list-disc av3-pad-left-5 av3-flow-1 av3-type-body">
            <?php foreach ($warnings as $w): ?>
              <li><?= e((string)$w) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($metaImagesTotal !== null && $metaImagesTotal > 0 && $metaImagesGeneral !== null && $metaImagesGeneral <= 0): ?>
            <div class="av3-space-top-2">
              <a class="av3-text-amber-900 underline" href="<?= e(url('/admin/products/edit?id=' . (int)$id . '&tab=imagenes')) ?>">
                Ir a Imagenes (General)
              </a>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <input type="hidden" name="is_active" value="0">

      <label class="av3-inline-flex av3-items-center av3-gap-3">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          class="av3-size-h-4 av3-size-w-4 accent-[var(--brand)]"
          <?= $isEffectivelyVisible ? 'checked' : '' ?>
          <?= $disablePublish ? 'disabled' : '' ?>
        >
        <span class="av3-type-body av3-text-slate-700 <?= $disablePublish ?'av3-opacity-60' : '' ?>">Publicar producto (visible en la tienda)</span>
      </label>

      <p class="av3-space-top-2 av3-type-caption av3-text-slate-500">
        Recomendacion: completa <strong>Venta</strong>, <strong>Imagenes</strong> y <strong>SEO</strong> antes de publicarlo.
      </p>
    </div>
  </div>

  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-5 av3-md-pad-6 av3-shadow-sm">
    <div class="av3-flex av3-items-start av3-justify-between av3-gap-4">
      <div>
        <h3 class="av3-type-base av3-weight-strong av3-text-slate-900">Vista previa</h3>
        <p class="av3-space-top-1 av3-type-body av3-text-slate-600">Asi lo va a ver el cliente en la tienda.</p>
      </div>
      <a class="btn-secondary <?= $isEffectivelyVisible ?'' : 'pointer-events-none av3-opacity-50' ?>" target="_blank" rel="noopener" href="<?= e($publicUrl) ?>" <?= $isEffectivelyVisible ? '' : 'aria-disabled="true"' ?>>Ver como cliente</a>
    </div>

    <div class="av3-space-top-5 av3-flex av3-gap-4">
      <?php if ($coverUrl): ?>
      <img src="<?= e($coverUrl) ?>" alt="Portada del producto" class="av3-size-h-16 av3-size-w-16 av3-radius-card av3-border av3-border-slate-200 av3-object-cover">
      <?php else: ?>
      <div class="av3-size-h-16 av3-size-w-16 av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-flex av3-items-center av3-justify-center av3-text-slate-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="av3-h-7 av3-w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M21 15V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10"/>
          <path d="M3 16l5-5a2 2 0 0 1 3 0l3 3"/>
          <path d="M14 14l1-1a2 2 0 0 1 3 0l3 3"/>
          <path d="M16 19h6"/>
        </svg>
      </div>
      <?php endif; ?>

      <?php if (!$coverUrl && $metaImagesTotal !== null && $metaImagesTotal > 0 && $metaImagesGeneral !== null && $metaImagesGeneral <= 0): ?>
        <div class="av3-space-top-2 av3-type-caption av3-text-amber-700">
          Hay imagenes por variante, pero no hay una imagen <strong>General</strong>. Recomendado: agrega 1 imagen general para que se vea mejor en listados/catalogo.
        </div>
      <?php endif; ?>

      <div class="av3-min-w-0 av3-flex-1">
        <div class="av3-type-body av3-weight-strong av3-text-slate-900 av3-truncate" title="<?= e($p['name'] ?? '') ?>">
          <?= e($p['name'] ?? 'Producto sin nombre') ?>
        </div>
        <div class="av3-space-top-1 av3-type-caption av3-text-slate-500 av3-truncate">
          <?= e($p['slug'] ?? '') ?>
        </div>

        <div class="av3-space-top-2 av3-type-body av3-text-slate-700">
          <?php if ($previewPrice !== null): ?>
            <?php if ($saleMode === 'variants'): ?>
              <span class="av3-weight-medium">Desde $<?= e(number_format((float)$previewPrice, 0, ',', '.')) ?></span>
            <?php else: ?>
              <span class="av3-weight-medium">$<?= e(number_format((float)$previewPrice, 0, ',', '.')) ?></span>
            <?php endif; ?>
          <?php else: ?>
            <span class="av3-text-slate-500">Precio: completa en <strong>Venta</strong></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="av3-space-top-4 av3-flex av3-items-center av3-gap-2">
      <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-pad-x2 av3-pad-yhalf av3-type-caption <?= $isEffectivelyVisible ?'av3-border-emerald-200 av3-bg-emerald-50 av3-text-emerald-700' : 'av3-border-slate-200 av3-bg-slate-50 av3-text-slate-600' ?>">
        <?= $isEffectivelyVisible ? 'Activo' : 'Inactivo' ?>
      </span>
      <span class="av3-type-caption av3-text-slate-500">Podes revisar el resultado antes de publicar.</span>
    </div>
  </div>
</div>
