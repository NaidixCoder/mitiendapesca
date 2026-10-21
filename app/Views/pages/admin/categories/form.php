<?php
$formView = is_array($categories_form_view ?? null) ? $categories_form_view : [];
extract($formView, EXTR_SKIP);

require BASE_PATH.'/app/Views/partials/admin/subnav.php';
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php
ob_start(); ?><?= e((string)($pageTitle ?? 'Categoria')) ?><?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Jerarquia, orden y visibilidad.<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
        <a class="btn btn-secondary" href="<?= url('/admin/categories') ?>">Volver</a>
      <?php $adminHeaderActions=ob_get_clean();
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Categorías','href'=>url('/admin/categories')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <?php if ($m = flash('error')): ?>
      <div class="alert alert-error"><?= e($m) ?></div>
    <?php endif; ?>

    <form class="av3-card" method="post" action="<?= url('/admin/categories/save') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($id ?? 0) ?>">

      <div class="av3-grid av3-md-grid-cols-2 av3-gap-4">
        <div>
          <label class="label">Nombre</label>
          <input class="input" name="name" value="<?= e((string)($categoryName ?? '')) ?>" required>
        </div>
        <div>
          <label class="label">Slug</label>
          <input class="input" name="slug" value="<?= e((string)($slug ?? '')) ?>" placeholder="auto desde nombre si lo dejas vacio">
        </div>

        <div>
          <label class="label">Padre</label>
          <select class="input" name="parent_id">
            <option value="0">-</option>
            <?php foreach (($parents ?? []) as $p): ?>
              <?php $pid = (int)($p['id'] ?? 0); ?>
              <option value="<?= $pid ?>" <?= ($pid === (int)($parentId ?? 0)) ? 'selected' : '' ?>><?= e((string)($p['name'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="label">Imagen portada (URL o /uploads/...)</label>
          <input class="input" name="cover_image" value="<?= e((string)($coverImage ?? ($cat['cover_image'] ?? ''))) ?>" placeholder="/uploads/categories/mi-categoria.jpg">
          <label class="label av3-space-top-2" for="cover_image_file">Subir imagen portada (JPG, PNG, WEBP)</label>
          <input class="input" id="cover_image_file" type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp">
          <p class="hint">Si subis una imagen, tiene prioridad sobre la URL manual.</p>
        </div>

        <div class="av3-grid av3-grid-cols-2 av3-gap-3">
          <div>
            <label class="label">Orden</label>
            <input type="number" class="input" name="sort" value="<?= (int)($order ?? 0) ?>">
          </div>
          <div>
            <label class="label">Activa</label>
            <select class="input" name="is_active">
              <option value="1" <?= ((int)($isActive ?? 1) === 1) ? 'selected' : '' ?>>Si</option>
              <option value="0" <?= ((int)($isActive ?? 1) === 0) ? 'selected' : '' ?>>No</option>
            </select>
          </div>
        </div>
      </div>

      <div class="av3-space-top-6">
        <?php section('partials/admin/seo/entity_panel', [
          'entityType' => 'category',
          'entity' => is_array($cat ?? null) ? $cat : [],
          'seoAudit' => is_array($seoAudit ?? null) ? $seoAudit : [],
          'seoSettings' => is_array($seoSettings ?? null) ? $seoSettings : [],
          'seoBlocks' => is_array($seoBlocks ?? null) ? $seoBlocks : ['bullets'=>[], 'specs'=>[], 'faq'=>[]],
        ]); ?>
      </div>

      <div class="av3-actions av3-space-top-4">
        <button class="btn btn-primary">Guardar cambios</button>
        <a class="btn btn-secondary" href="<?= url('/admin/categories') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</section>
