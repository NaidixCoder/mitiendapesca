<?php
$pageTitle = 'Ajustes - Home hero';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('settings.section', ['name' => 'Home hero']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';
$rows = is_array($rows ?? null) ? $rows : [];
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4" data-home-hero-settings>
    <?php if ($m = flash('ok')): ?>
      <div class="av3-card av3-pad-3"><?= e((string)$m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
      <div class="av3-card av3-pad-3"><?= e((string)$m) ?></div>
    <?php endif; ?>

    <?php
$adminHeaderTitle='Hero del inicio';
$adminHeaderDescription='Gestioná imágenes, orden y enlaces del carrusel principal.';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>'Hero del inicio']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<div class="av3-card av3-pad-4"><div class="av3-space-top-3 av3-type-caption av3-opacity-80 av3-flow-1">
        <p><strong>Medidas recomendadas:</strong></p>
        <p>Desktop: <strong>1920x1080</strong> (16:9)</p>
        <p>Mobile: <strong>1080x1350</strong> (4:5) o <strong>1080x1440</strong> (3:4)</p>
        <p>Formato sugerido: JPG/WebP (calidad 75-82). Peso objetivo: desktop 250-450 KB, mobile 150-300 KB.</p>
      </div></div>

    <input type="hidden" data-hero-upload-url value="<?= e(url('/admin/settings/design/home-hero/upload-image')) ?>">
    <input type="hidden" data-hero-delete-url value="<?= e(url('/admin/settings/design/home-hero/delete-image')) ?>">
    <input type="hidden" data-hero-products-search-url value="<?= e(url('/admin/marketing/email/products/search')) ?>">

    <form method="post" action="<?= e(url('/admin/settings/design/home-hero')) ?>" class="av3-flow-4">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" data-home-hero-csrf>

      <section class="av3-card av3-pad-4 av3-flow-3">
        <h2 class="av3-weight-strong">Contenido principal del hero</h2>
        <div class="av3-grid av3-grid-cols-1 av3-gap-3">
          <label class="av3-flow-1">
            <span class="av3-type-body av3-weight-medium">Titulo</span>
            <input
              class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2"
              type="text"
              name="home_hero_title"
              maxlength="140"
              value="<?= e((string)($heroTitle ?? '')) ?>"
              placeholder="Equipá tu próxima pesca con productos de calidad"
            >
            <p class="av3-type-caption av3-opacity-80">
              Resaltado configurable: usa <code>[[accent]]texto[[/accent]]</code>.
              Ejemplo: <code>Equipá tu próxima [[accent]]pesca[[/accent]] con productos de calidad</code>.
            </p>
          </label>
          <label class="av3-flow-1">
            <span class="av3-type-body av3-weight-medium">Descripcion</span>
            <textarea
              class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2"
              name="home_hero_description"
              rows="3"
              maxlength="260"
              placeholder="<?= e(home_hero_description_default()) ?>"
            ><?= e((string)($heroDescription ?? '')) ?></textarea>
          </label>
        </div>
      </section>

      <?php foreach ($rows as $i => $row): ?>
        <?php $sort = (int)($row['sort_order'] ?? ($i + 1)); ?>
        <section class="av3-card av3-pad-4 av3-flow-3" data-hero-slide>
          <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
            <h2 class="av3-weight-strong">Slide <?= (int)($i + 1) ?></h2>
            <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-body">
              <input type="checkbox" name="slide_active[<?= (int)$i ?>]" value="1" <?= !empty($row['is_active']) ? 'checked' : '' ?>>
              Activo
            </label>
          </div>

          <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Titulo interno</span>
              <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="slide_title[<?= (int)$i ?>]" value="<?= e((string)($row['title'] ?? '')) ?>" placeholder="Promo temporada" data-hero-title>
            </label>
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Texto alt</span>
              <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="slide_alt[<?= (int)$i ?>]" value="<?= e((string)($row['alt_text'] ?? '')) ?>" placeholder="Imagen de pesca" data-hero-alt>
            </label>
          </div>

          <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Imagen desktop (obligatoria)</span>
              <div class="av3-flex av3-items-center av3-gap-2">
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="slide_image_desktop[<?= (int)$i ?>]" value="<?= e((string)($row['image_desktop'] ?? '')) ?>" placeholder="<?= e(asset('img/hero/hero-pesca-01.png')) ?>" data-hero-image-desktop>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-upload-btn="desktop">Subir</button>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-pick-product="desktop">Catalogo</button>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-clear-image="desktop">Limpiar</button>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-delete-file="desktop">Eliminar archivo</button>
              </div>
            </label>
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Imagen mobile (opcional)</span>
              <div class="av3-flex av3-items-center av3-gap-2">
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="slide_image_mobile[<?= (int)$i ?>]" value="<?= e((string)($row['image_mobile'] ?? '')) ?>" placeholder="<?= e(asset('img/hero/hero-pesca-01a.jpg')) ?>" data-hero-image-mobile>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-upload-btn="mobile">Subir</button>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-pick-product="mobile">Catalogo</button>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-clear-image="mobile">Limpiar</button>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-delete-file="mobile">Eliminar archivo</button>
              </div>
            </label>
          </div>

          <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
            <label class="av3-flow-1 av3-md-col-span-2">
              <span class="av3-type-body av3-weight-medium">URL destino (obligatoria)</span>
              <div class="av3-flex av3-items-center av3-gap-2">
                <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="text" name="slide_url[<?= (int)$i ?>]" value="<?= e((string)($row['target_url'] ?? '')) ?>" placeholder="/productos" data-hero-url>
                <button type="button" class="btn-secondary whitespace-nowrap" data-hero-pick-product="url">Abrir catalogo</button>
              </div>
            </label>
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Orden</span>
              <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="number" name="slide_sort[<?= (int)$i ?>]" value="<?= (int)$sort ?>" min="0" step="1">
            </label>
          </div>

          <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Inicio (opcional)</span>
              <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="datetime-local" name="slide_starts_at[<?= (int)$i ?>]" value="<?= e((string)($row['starts_at_local'] ?? '')) ?>">
            </label>
            <label class="av3-flow-1">
              <span class="av3-type-body av3-weight-medium">Fin (opcional)</span>
              <input class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" type="datetime-local" name="slide_ends_at[<?= (int)$i ?>]" value="<?= e((string)($row['ends_at_local'] ?? '')) ?>">
            </label>
          </div>

          <p class="av3-type-caption av3-opacity-80" data-hero-upload-status></p>
        </section>
      <?php endforeach; ?>

      <div class="av3-card av3-pad-4 av3-flex av3-items-center av3-justify-end av3-gap-2">
        <button type="submit" class="btn-primary">Guardar hero</button>
      </div>
    </form>

    <input type="file" accept="image/*" hidden data-hero-file-input>

    <div class="av3-fixed inset-0 av3-z--70 av3-hidden" data-hero-product-picker aria-hidden="true">
      <div class="av3-absolute inset-0 av3-bg-black-40" data-hero-picker-close></div>
      <div class="av3-absolute inset-x-0 top-10 av3-mx-auto av3-w--min-94vw-900px">
        <section class="av3-card av3-pad-4 av3-flow-3">
          <header class="av3-flex av3-items-center av3-justify-between av3-gap-2">
            <h3 class="av3-weight-strong">Elegir producto</h3>
            <button type="button" class="btn-secondary" data-hero-picker-close>Cerrar</button>
          </header>
          <div class="av3-flex av3-items-center av3-gap-2">
            <input type="text" class="av3-width-full av3-border av3-radius-control av3-pad-x3 av3-pad-y2" placeholder="Buscar por nombre, slug o sku..." data-hero-picker-q>
            <button type="button" class="btn-primary" data-hero-picker-search>Buscar</button>
          </div>
          <p class="av3-type-caption av3-opacity-80" data-hero-picker-status>Escribe y busca.</p>
          <div class="max-h-[56vh] av3-overflow-auto av3-border av3-radius-control" data-hero-picker-results></div>
        </section>
      </div>
    </div>
  </div>
</section>
