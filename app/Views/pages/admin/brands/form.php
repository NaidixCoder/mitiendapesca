<?php
$pageTitle = !empty($brand['id']) ? 'Editar marca' : 'Nueva marca';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('brands.edit', ['name' => (string)($brand['name'] ?? '')]);
$id   = (int)($brand['id'] ?? 0);
$name = (string)($brand['name'] ?? '');
$slug = (string)($brand['slug'] ?? '');
$sort = (int)($brand['sort'] ?? 0);
$coverImage = (string)($brand['cover_image'] ?? '');
?>
<section class="admin-surface">
  <div class="av3-wrap av3-page-stack">

    <?php
ob_start(); ?><?= $id ? 'Editar marca' : 'Nueva marca' ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Nombre visible + slug amigable para URL.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
      
      <a class="btn-secondary" href="<?= url('/admin/brands') ?>">Volver</a>
    <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Marcas','href'=>url('/admin/brands')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <?php if ($m = flash('error')): ?>
      <div class="av3-alert danger"><?= e($m) ?></div>
    <?php endif; ?>

    <form class="av3-card" method="post" action="<?= url('/admin/brands/save') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">

      <div class="av3-form-columns">
        <div>
          <label class="label" for="name">Nombre</label>
          <input class="input" id="name" name="name" value="<?= e($name) ?>" required>
        </div>
        <div>
          <label class="label" for="slug">Slug</label>
          <input class="input" id="slug" name="slug" value="<?= e($slug) ?>" placeholder="auto si lo dejas vacio">
          <p class="hint">Ej: <span class="av3-code">shimano</span>, <span class="av3-code">rapala</span>.</p>
        </div>
        <div>
          <label class="label" for="sort">Orden</label>
          <input class="input" id="sort" type="number" name="sort" value="<?= (int)$sort ?>">
          <p class="hint">Menor valor aparece primero en Home y listados.</p>
        </div>
        <div>
          <label class="label" for="cover_image">Imagen portada (URL o /uploads/...)</label>
          <input class="input" id="cover_image" name="cover_image" value="<?= e($coverImage) ?>" placeholder="/uploads/brands/mi-marca.jpg">
          <label class="label av3-field-followup" for="cover_image_file">Subir imagen portada (JPG, PNG, WEBP)</label>
          <input class="input" id="cover_image_file" type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp">
          <p class="hint">Si subis una imagen, tiene prioridad sobre la URL manual.</p>
        </div>
      </div>

      <div class="av3-section-space">
        <?php section('partials/admin/seo/entity_panel', [
          'entityType' => 'brand',
          'entity' => $brand,
          'seoAudit' => ($seoAudit ?? null),
          'seoSettings' => ($seoSettings ?? []),
          'seoBlocks' => ($seoBlocks ?? ['bullets'=>[], 'specs'=>[], 'faq'=>[]]),
        ]); ?>
      </div>

      <div class="av3-form-actions-end">
        <a class="btn-secondary" href="<?= url('/admin/brands') ?>">Cancelar</a>
        <button class="btn-primary">Guardar</button>
      </div>
    </form>

  </div>
</section>
