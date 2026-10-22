<?php
$pageTitle = !empty($brand['id']) ? 'Editar marca' : 'Nueva marca';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('brands.edit', ['name' => (string)($brand['name'] ?? '')]);
$id = (int)($brand['id'] ?? 0);
$name = (string)($brand['name'] ?? '');
$slug = (string)($brand['slug'] ?? '');
$sort = (int)($brand['sort'] ?? 0);
$coverImage = (string)($brand['cover_image'] ?? '');
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/brands.css')) ?>">
<section class="admin-surface">
  <div class="av3-wrap av3-layout--operational av3-brands av3-brands--form">
    <?php
      $adminHeaderTitle = $id ? 'Editar marca' : 'Nueva marca';
      $adminHeaderDescription = 'Identidad, presentación y contenido SEO.';
      $adminHeaderActions = '<a class="av3-ui-btn" href="' . e(url('/admin/brands')) . '">Volver a marcas</a>';
      $adminHeaderBreadcrumbs = $crumbs ?: [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Marcas','href'=>url('/admin/brands')],['label'=>$adminHeaderTitle]];
      require BASE_PATH . '/app/Views/partials/admin/page_header.php';
    ?>
    <?php foreach (['error'=>'error','warn'=>'warning'] as $type => $tone): ?>
      <?php if ($message = flash($type)): ?>
        <div class="av3-brands__notice av3-brands__notice--<?= e($tone) ?>" role="alert"><?= e((string)$message) ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!empty($hadValidationError)): ?>
      <p class="av3-brands__draft-warning" role="status">Recuperamos tus datos. Si adjuntaste una imagen, tendrás que seleccionarla nuevamente.</p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/brands/save')) ?>" enctype="multipart/form-data" class="av3-brands__editor" data-brands-form data-brands-editor>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">

      <section class="av3-ui-card av3-brands__identity-panel" aria-labelledby="brand-identity-title">
        <header class="av3-ui-card-header av3-brands__section-heading">
          <div>
            <h2 id="brand-identity-title">Datos de la marca</h2>
            <p>Información principal y portada.</p>
          </div>
          <?php if ($id): ?><span class="av3-ui-badge av3-ui-badge--neutral">Marca #<?= $id ?></span><?php endif; ?>
        </header>
        <div class="av3-brands__identity-grid">
          <div class="av3-ui-field av3-brands__field-name">
            <label class="av3-ui-field-label" for="name">Nombre de la marca <span aria-hidden="true">*</span></label>
            <input class="av3-ui-control" type="text" id="name" name="name" maxlength="120" value="<?= e($name) ?>" placeholder="Ej.: Shimano" autocomplete="off" required>
          </div>
          <div class="av3-ui-field av3-brands__field-slug">
            <label class="av3-ui-field-label" for="slug">Slug de la URL</label>
            <input class="av3-ui-control" type="text" id="slug" name="slug" maxlength="140" value="<?= e($slug) ?>" placeholder="Se genera si queda vacío" autocapitalize="off" autocomplete="off" spellcheck="false">
            <?php if ($id): ?><small class="av3-ui-field-hint">Los cambios conservan el historial SEO.</small><?php endif; ?>
          </div>
          <div class="av3-brands__identity-tools">
            <div class="av3-ui-field av3-brands__field-sort">
              <label class="av3-ui-field-label" for="sort">Orden</label>
              <input class="av3-ui-control" type="number" id="sort" name="sort" step="1" min="-2147483648" max="2147483647" value="<?= e((string)$sort) ?>">
            </div>
            <div class="av3-brands__field-cover">
            <span class="av3-brands__cover-label">Imagen de portada</span>
            <div class="av3-brands__cover-inline">
              <div class="av3-brands__cover-preview">
                <?php if ($coverImage !== ''): ?>
                  <img src="<?= e(\App\Services\Admin\Brands\BrandCoverUrl::resolve($coverImage)) ?>" alt="Portada actual de <?= e($name !== '' ? $name : 'la marca') ?>" loading="lazy">
                <?php else: ?>
                  <span>Sin portada</span>
                <?php endif; ?>
              </div>
              <div class="av3-brands__cover-controls">
                <div class="av3-ui-field av3-brands__cover-file">
                  <label class="av3-brands__sr-only" for="cover_image_file">Seleccionar archivo de imagen</label>
                  <input class="av3-ui-control" id="cover_image_file" type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp" aria-describedby="brand-cover-file-hint">
                  <small class="av3-ui-field-hint" id="brand-cover-file-hint">JPG, PNG o WEBP · máximo 5 MB</small>
                </div>
                <div class="av3-ui-field av3-brands__cover-url">
                  <label class="av3-brands__sr-only" for="cover_image">O indicar URL de imagen</label>
                  <input class="av3-ui-control" type="text" id="cover_image" name="cover_image" maxlength="255" value="<?= e($coverImage) ?>" placeholder="O URL de imagen" aria-label="O indicar URL de imagen">
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="av3-ui-card av3-brands__seo-section" aria-labelledby="brand-seo-title">
        <header class="av3-ui-card-header av3-brands__section-heading">
          <div>
            <h2 id="brand-seo-title">SEO y posicionamiento</h2>
            <p>Metadatos, diagnóstico y contenido de la ficha.</p>
          </div>
        </header>
        <?php section('partials/admin/brands/seo_editor', [
          'entityType' => 'brand',
          'entity' => $brand,
          'seoAudit' => ($seoAudit ?? null),
          'seoSettings' => ($seoSettings ?? []),
          'seoBlocks' => ($seoBlocks ?? ['bullets'=>[], 'specs'=>[], 'faq'=>[]]),
        ]); ?>
      </section>

      <div class="av3-ui-actions av3-brands__form-actions" data-brands-savebar>
        <a class="av3-ui-btn" href="<?= e(url('/admin/brands')) ?>">Cancelar</a>
        <button class="av3-ui-btn av3-ui-btn--primary" type="submit"><?= $id ? 'Guardar cambios' : 'Crear marca' ?></button>
      </div>
    </form>
  </div>
</section>
<script type="module" src="<?= e(asset('js/admin/brands/form.js')) ?>"></script>
