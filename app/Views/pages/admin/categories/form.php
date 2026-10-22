<?php
$formView = is_array($categories_form_view ?? null) ? $categories_form_view : [];
extract($formView, EXTR_SKIP);
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$isEdit = (int)($id ?? 0) > 0;
$parents = is_array($parents ?? null) ? $parents : [];
$cover = trim((string)($coverImage ?? ''));
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/categories.css')) ?>">
<section class="admin-surface av3-categories">
  <div class="av3-wrap av3-layout--form av3-categories__layout">
    <?php
    $adminHeaderTitle = $isEdit ? 'Editar categoría' : 'Nueva categoría';
    $adminHeaderDescription = '';
    ob_start(); ?>
      <a class="av3-ui-btn" href="<?= url('/admin/categories') ?>">Volver al listado</a>
    <?php $adminHeaderActions = trim(ob_get_clean());
    $adminHeaderBreadcrumbs = is_array($crumbs ?? null) && $crumbs ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Categorías','href'=>url('/admin/categories')],['label'=>$adminHeaderTitle]];
    require BASE_PATH . '/app/Views/partials/admin/page_header.php';
    ?>

    <?php if ($message = flash('error')): ?>
      <div class="av3-categories__notice av3-categories__notice--error" role="alert"><?= e($message) ?></div>
    <?php endif; ?>

    <form class="av3-categories__form" method="post" action="<?= url('/admin/categories/save') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($id ?? 0) ?>">

      <section class="av3-categories__form-panel" aria-labelledby="category-basic-heading">
        <header class="av3-categories__form-heading">
          <h2 id="category-basic-heading">Información general</h2>
          <p>Identificación, URL y ubicación dentro del catálogo.</p>
        </header>
        <div class="av3-categories__fields">
          <div class="av3-ui-field av3-categories__field">
            <label class="av3-ui-field-label" for="category-name">Nombre de la categoría <span aria-hidden="true">*</span></label>
            <input class="av3-ui-control" id="category-name" name="name" maxlength="120" value="<?= e((string)($categoryName ?? '')) ?>" required autocomplete="off" placeholder="Ej. Reeles de pesca">
          </div>
          <div class="av3-ui-field av3-categories__field">
            <label class="av3-ui-field-label" for="category-slug">Slug de la URL</label>
            <input class="av3-ui-control" id="category-slug" name="slug" maxlength="140" value="<?= e((string)($slug ?? '')) ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="Se genera si lo dejás vacío" spellcheck="false" autocapitalize="none">
            <small class="av3-ui-field-hint">Usá minúsculas, números y guiones. Cambiarlo puede modificar enlaces públicos.</small>
          </div>
          <div class="av3-ui-field av3-categories__field">
            <label class="av3-ui-field-label" for="category-parent">Categoría superior</label>
            <select class="av3-ui-control" id="category-parent" name="parent_id">
              <option value="0">Sin categoría superior (principal)</option>
              <?php foreach ($parents as $p): ?>
                <?php $pid = (int)($p['id'] ?? 0); $selectable = !array_key_exists('selectable', $p) || (bool)$p['selectable']; ?>
                <option value="<?= $pid ?>" <?= $pid === (int)($parentId ?? 0) ? 'selected' : '' ?> <?= !$selectable ? 'disabled' : '' ?>>
                  <?= e((string)($p['label'] ?? $p['name'] ?? '')) ?><?= !$selectable ? ' (no disponible)' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="av3-ui-field-hint">No es posible asignar una categoría debajo de sí misma ni de sus descendientes.</small>
          </div>
          <div class="av3-categories__field-row">
            <div class="av3-ui-field av3-categories__field">
              <label class="av3-ui-field-label" for="category-sort">Orden de visualización</label>
              <input class="av3-ui-control" id="category-sort" type="number" name="sort" value="<?= (int)($order ?? 0) ?>" step="1">
              <small class="av3-ui-field-hint">Los valores menores se muestran primero dentro del mismo nivel.</small>
            </div>
            <div class="av3-ui-field av3-categories__field">
              <label class="av3-ui-field-label" for="category-active">Visibilidad</label>
              <select class="av3-ui-control" id="category-active" name="is_active">
                <option value="1" <?= (int)($isActive ?? 1) === 1 ? 'selected' : '' ?>>Activa</option>
                <option value="0" <?= (int)($isActive ?? 1) === 0 ? 'selected' : '' ?>>Inactiva</option>
              </select>
              <small class="av3-ui-field-hint">Una categoría activa solo aparece en la tienda si todos sus ancestros también están activos.</small>
            </div>
          </div>
        </div>
      </section>

      <section class="av3-categories__form-panel" aria-labelledby="category-image-heading">
        <header class="av3-categories__form-heading">
          <h2 id="category-image-heading">Imagen de portada</h2>
          <p>Utilizada en las páginas de categoría y las piezas del catálogo.</p>
        </header>
        <div class="av3-categories__media">
          <div class="av3-categories__media-preview">
            <?php if ($cover !== ''): ?>
              <img data-categories-image src="<?= e(public_url($cover)) ?>" width="160" height="110" alt="Portada actual de la categoría" loading="lazy">
            <?php else: ?>
              <span>Sin portada</span>
            <?php endif; ?>
          </div>
          <div class="av3-categories__media-fields">
            <div class="av3-ui-field av3-categories__field">
              <label class="av3-ui-field-label" for="category-cover">URL o ruta de portada</label>
              <input class="av3-ui-control" id="category-cover" name="cover_image" maxlength="255" value="<?= e($cover) ?>" placeholder="/uploads/categories/imagen.webp">
            </div>
            <div class="av3-ui-field av3-categories__field">
              <label class="av3-ui-field-label" for="cover_image_file">Subir una imagen</label>
              <input id="cover_image_file" type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp">
              <small class="av3-ui-field-hint">JPG, PNG o WEBP · máximo 4 MB. La subida reemplaza la ruta indicada.</small>
            </div>
          </div>
        </div>
      </section>

      <details class="av3-categories__seo-panel">
        <summary>
          <span><strong>SEO y contenido editorial</strong><small class="av3-ui-field-hint">Metadatos, vista previa, texto y bloques enriquecidos</small></span>
          <span class="av3-categories__seo-expand" aria-hidden="true">Mostrar / ocultar</span>
        </summary>
        <div class="av3-categories__seo-content">
          <?php section('partials/admin/seo/entity_panel', [
            'entityType' => 'category',
            'entity' => is_array($cat ?? null) ? $cat : [],
            'seoAudit' => is_array($seoAudit ?? null) ? $seoAudit : [],
            'seoSettings' => is_array($seoSettings ?? null) ? $seoSettings : [],
            'seoBlocks' => is_array($seoBlocks ?? null) ? $seoBlocks : ['bullets'=>[], 'specs'=>[], 'faq'=>[]],
          ]); ?>
        </div>
      </details>

      <footer class="av3-categories__form-actions">
        <button class="av3-ui-btn av3-ui-btn--primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear categoría' ?></button>
        <a class="av3-ui-btn" href="<?= url('/admin/categories') ?>">Cancelar</a>
      </footer>
    </form>
  </div>
</section>
<script type="module" src="<?= e(asset('js/admin/categories/index.js')) ?>"></script>
