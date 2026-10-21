<?php
$pageTitle = 'Ajustes - Secciones Home';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('settings.section', ['name' => 'Secciones Home']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$config = is_array($config ?? null) ? $config : [];
$sections = is_array($sections ?? null) ? $sections : [];
$sectionProductsByKey = is_array($sectionProductsByKey ?? null) ? $sectionProductsByKey : [];
$youtubeProducts = is_array($youtubeProducts ?? null) ? $youtubeProducts : [];
$youtubeAvatarFallbackUrl = trim((string)($youtubeAvatarFallbackUrl ?? ''));
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4" data-home-sections-settings>
    <?php if ($m = flash('ok')): ?>
      <div class="av3-card av3-pad-3"><?= e((string)$m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
      <div class="av3-card av3-pad-3"><?= e((string)$m) ?></div>
    <?php endif; ?>

    <?php
$adminHeaderTitle='Secciones del inicio';
$adminHeaderDescription='Administrá las secciones dinámicas de productos del inicio.';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>'Secciones del inicio']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <input type="hidden" data-home-sections-products-search-url value="<?= e(url('/admin/marketing/email/products/search')) ?>">

    <form method="post" action="<?= e(url('/admin/settings/design/home-sections')) ?>" class="av3-flow-4" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <div class="av3-card av3-pad-4 av3-flow-3">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
          <h2 class="av3-weight-strong">Bloques dinamicos</h2>
          <button type="button" class="btn-secondary" data-home-section-create>Agregar seccion</button>
        </div>
        <p class="av3-type-caption av3-opacity-80">Cada bloque usa la grilla de productos del home. Maximo 12 productos por seccion.</p>
      </div>

      <div class="av3-flow-4" data-home-sections-list>
        <?php foreach ($sections as $idx => $section): ?>
          <?php
            $idx = (int)$idx;
            $key = (string)($section['key'] ?? ('section_' . ($idx + 1)));
            $title = (string)($section['title'] ?? 'Seccion');
            $subtitle = (string)($section['subtitle'] ?? 'Seleccionados para vos');
            $badge = (string)($section['badge'] ?? 'En stock');
            $items = is_array($sectionProductsByKey[$key] ?? null) ? $sectionProductsByKey[$key] : [];
          ?>
          <section class="av3-card av3-pad-4 av3-flow-3" data-home-section data-home-section-key="<?= e($key) ?>">
            <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
              <h3 class="av3-weight-strong" data-home-section-title-preview><?= e($title) ?></h3>
              <button type="button" class="btn-secondary" data-home-section-remove>Quitar seccion</button>
            </div>

            <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
              <label class="av3-flow-1">
                <span class="av3-type-body av3-weight-medium">Key</span>
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_key[<?= $idx ?>]" value="<?= e($key) ?>" maxlength="40" data-home-section-key-input>
              </label>
              <label class="av3-flow-1 av3-md-col-span-2">
                <span class="av3-type-body av3-weight-medium">Titulo</span>
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_title[<?= $idx ?>]" value="<?= e($title) ?>" maxlength="80" data-home-section-title>
              </label>
            </div>

            <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
              <label class="av3-flow-1 av3-md-col-span-2">
                <span class="av3-type-body av3-weight-medium">Subtitulo</span>
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_subtitle[<?= $idx ?>]" value="<?= e($subtitle) ?>" maxlength="120">
              </label>
              <label class="av3-flow-1">
                <span class="av3-type-body av3-weight-medium">Badge</span>
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_badge[<?= $idx ?>]" value="<?= e($badge) ?>" maxlength="40">
              </label>
              <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-body av3-space-top-6">
                <input type="checkbox" name="section_enabled[<?= $idx ?>]" value="1" <?= !empty($section['enabled']) ? 'checked' : '' ?>>
                Seccion activa
              </label>
            </div>

            <input type="hidden" name="section_product_ids[<?= $idx ?>]" value="<?= e(implode(',', array_map(static fn(array $p): int => (int)($p['id'] ?? 0), $items))) ?>" data-home-section-ids>
            <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
              <p class="av3-type-caption av3-opacity-80">Productos en orden de aparicion.</p>
              <button type="button" class="btn-secondary" data-home-section-add>Abrir catalogo</button>
            </div>
            <div class="av3-flow-2" data-home-section-list>
              <?php foreach ($items as $p): ?>
                <div class="av3-border av3-radius-control av3-pad-2 av3-flex av3-items-center av3-gap-3" data-home-product-row data-product-id="<?= (int)($p['id'] ?? 0) ?>">
                  <img class="av3-size-w-12 av3-size-h-12 av3-object-cover av3-radius-sm av3-border av3-surface-white" src="<?= e((string)($p['thumb'] ?? asset('img/placeholder.jpg'))) ?>" alt="">
                  <div class="av3-min-w-0 av3-flex-1">
                    <div class="av3-type-body av3-weight-medium av3-truncate"><?= e((string)($p['name'] ?? '')) ?></div>
                    <div class="av3-type-caption av3-opacity-70 av3-truncate">ID <?= (int)($p['id'] ?? 0) ?><?= !empty($p['sku']) ? ' - SKU: ' . e((string)$p['sku']) : '' ?></div>
                  </div>
                  <button type="button" class="btn-secondary" data-home-product-remove>Quitar</button>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </div>

      <section class="av3-card av3-pad-4 av3-flow-3" data-home-section data-home-section-key="youtube">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
          <h3 class="av3-weight-strong">YouTube: Productos usados en el video</h3>
        </div>
        <p class="av3-type-caption av3-opacity-80">Estos productos se muestran en la seccion "Canal de YouTube" del Home. Maximo 12.</p>

        <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols--minmax-0-1fr-220px av3-gap-4 av3-items-start">
          <label class="av3-flow-1">
            <span class="av3-type-body av3-weight-medium">Avatar fallback del canal</span>
            <input type="hidden" name="youtube_avatar_fallback_url" value="<?= e($youtubeAvatarFallbackUrl) ?>">
            <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="file" name="youtube_avatar_fallback" accept="image/jpeg,image/png,image/webp,image/gif">
            <span class="av3-block av3-type-caption av3-opacity-70">Se usa cuando falla la miniatura externa del canal. Maximo 4MB.</span>
            <?php if ($youtubeAvatarFallbackUrl !== ''): ?>
              <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-caption">
                <input type="checkbox" name="youtube_avatar_fallback_remove" value="1">
                Quitar imagen actual al guardar
              </label>
            <?php endif; ?>
          </label>

          <div class="av3-flow-2">
            <span class="av3-type-body av3-weight-medium av3-block">Vista actual</span>
            <div class="av3-size-w-24 av3-size-h-24 av3-radius-full av3-border av3-surface-white av3-overflow-hidden av3-grid place-items-center">
              <?php if ($youtubeAvatarFallbackUrl !== ''): ?>
                <img class="av3-width-full av3-height-full av3-object-cover" src="<?= e($youtubeAvatarFallbackUrl) ?>" alt="Avatar fallback YouTube">
              <?php else: ?>
                <div class="av3-size-w-10 av3-size-h-10 av3-text-red-600" aria-hidden="true"><?= ui_icon('youtube-brand') ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <input type="hidden" name="youtube_product_ids" value="<?= e(implode(',', array_map(static fn(array $p): int => (int)($p['id'] ?? 0), $youtubeProducts))) ?>" data-home-section-ids>
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
          <p class="av3-type-caption av3-opacity-80">Productos en orden de aparicion.</p>
          <button type="button" class="btn-secondary" data-home-section-add>Abrir catalogo</button>
        </div>
        <div class="av3-flow-2" data-home-section-list>
          <?php foreach ($youtubeProducts as $p): ?>
            <div class="av3-border av3-radius-control av3-pad-2 av3-flex av3-items-center av3-gap-3" data-home-product-row data-product-id="<?= (int)($p['id'] ?? 0) ?>">
              <img class="av3-size-w-12 av3-size-h-12 av3-object-cover av3-radius-sm av3-border av3-surface-white" src="<?= e((string)($p['thumb'] ?? asset('img/placeholder.jpg'))) ?>" alt="">
              <div class="av3-min-w-0 av3-flex-1">
                <div class="av3-type-body av3-weight-medium av3-truncate"><?= e((string)($p['name'] ?? '')) ?></div>
                <div class="av3-type-caption av3-opacity-70 av3-truncate">ID <?= (int)($p['id'] ?? 0) ?><?= !empty($p['sku']) ? ' - SKU: ' . e((string)$p['sku']) : '' ?></div>
              </div>
              <button type="button" class="btn-secondary" data-home-product-remove>Quitar</button>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <div class="av3-card av3-pad-4 av3-flex av3-items-center av3-justify-end av3-gap-2">
        <button type="submit" class="btn-primary">Guardar secciones</button>
      </div>
    </form>

    <template data-home-section-template>
      <section class="av3-card av3-pad-4 av3-flow-3" data-home-section data-home-section-key="">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
          <h3 class="av3-weight-strong" data-home-section-title-preview>Nueva seccion</h3>
          <button type="button" class="btn-secondary" data-home-section-remove>Quitar seccion</button>
        </div>

        <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
          <label class="av3-flow-1">
            <span class="av3-type-body av3-weight-medium">Key</span>
            <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_key[__IDX__]" value="section___IDX__" maxlength="40" data-home-section-key-input>
          </label>
          <label class="av3-flow-1 av3-md-col-span-2">
            <span class="av3-type-body av3-weight-medium">Titulo</span>
            <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_title[__IDX__]" value="Nueva seccion" maxlength="80" data-home-section-title>
          </label>
        </div>

        <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
          <label class="av3-flow-1 av3-md-col-span-2">
            <span class="av3-type-body av3-weight-medium">Subtitulo</span>
            <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_subtitle[__IDX__]" value="Seleccionados para vos" maxlength="120">
          </label>
          <label class="av3-flow-1">
            <span class="av3-type-body av3-weight-medium">Badge</span>
            <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="section_badge[__IDX__]" value="En stock" maxlength="40">
          </label>
          <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-body av3-space-top-6">
            <input type="checkbox" name="section_enabled[__IDX__]" value="1" checked>
            Seccion activa
          </label>
        </div>

        <input type="hidden" name="section_product_ids[__IDX__]" value="" data-home-section-ids>
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
          <p class="av3-type-caption av3-opacity-80">Productos en orden de aparicion.</p>
          <button type="button" class="btn-secondary" data-home-section-add>Abrir catalogo</button>
        </div>
        <div class="av3-flow-2" data-home-section-list></div>
      </section>
    </template>

    <div class="av3-fixed inset-0 av3-z--70 av3-hidden" data-home-sections-picker aria-hidden="true">
      <div class="av3-absolute inset-0 av3-bg-black-40" data-home-sections-picker-close></div>
      <div class="av3-absolute inset-x-0 top-10 av3-mx-auto av3-w--min-94vw-900px">
        <section class="av3-card av3-pad-4 av3-flow-3">
          <header class="av3-flex av3-items-center av3-justify-between av3-gap-2">
            <h3 class="av3-weight-strong">Elegir producto</h3>
            <button type="button" class="btn-secondary" data-home-sections-picker-close>Cerrar</button>
          </header>
          <div class="av3-flex av3-items-center av3-gap-2">
            <input type="text" class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" placeholder="Buscar por nombre, slug o sku..." data-home-sections-picker-q>
            <button type="button" class="btn-primary" data-home-sections-picker-search>Buscar</button>
          </div>
          <p class="av3-type-caption av3-opacity-80" data-home-sections-picker-status>Escribe y busca.</p>
          <div class="max-h-[56vh] av3-overflow-auto av3-border av3-radius-control" data-home-sections-picker-results></div>
        </section>
      </div>
    </div>
  </div>
</section>
