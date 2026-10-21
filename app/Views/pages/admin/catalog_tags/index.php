<?php
$pageTitle = 'Tags';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('catalog-tags.index');
$groups = is_array($groups ?? null) ? $groups : [];
$tags = is_array($tags ?? null) ? $tags : [];
$groupRow = is_array($groupRow ?? null) ? $groupRow : null;
$tagRow = is_array($tagRow ?? null) ? $tagRow : null;
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">
    <?php
ob_start(); ?>Tags de catalogo<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Crea grupos y tags para clasificar productos sin afectar variantes ni SKU.<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Tags','href'=>url('/admin/catalog-tags')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <?php if ($m = flash('ok')): ?>
      <div class="av3-alert ok"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
      <div class="av3-alert error"><?= e($m) ?></div>
    <?php endif; ?>

    <div class="av3-grid av3-xl-grid-cols-2 av3-gap-4">
      <section class="av3-card">
        <header class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-4">
          <div>
            <h2 class="av3-type-base av3-weight-strong"><?= $groupRow ? 'Editar grupo' : 'Nuevo grupo' ?></h2>
            <p class="av3-type-body av3-text-var-muted">Ejemplos: Modalidad, Tecnica, Formato.</p>
          </div>
        </header>

        <form class="av3-grid av3-md-grid-cols-2 av3-gap-3" method="post" action="<?= url('/admin/catalog-tags/group/save') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($groupRow['id'] ?? 0) ?>">

          <label class="av3-block av3-md-col-span-2">
            <span class="label">Nombre</span>
            <input class="input av3-width-full" name="name" value="<?= e((string)($groupRow['name'] ?? '')) ?>" placeholder="Modalidad">
          </label>

          <label class="av3-block">
            <span class="label">Slug</span>
            <input class="input av3-width-full" name="slug" value="<?= e((string)($groupRow['slug'] ?? '')) ?>" placeholder="modalidad">
          </label>

          <label class="av3-block">
            <span class="label">Orden</span>
            <input class="input av3-width-full" type="number" min="0" name="sort" value="<?= (int)($groupRow['sort'] ?? 0) ?>">
          </label>

          <label class="av3-block av3-md-col-span-2">
            <span class="label">Activo</span>
            <select class="input av3-width-full" name="is_active">
              <option value="1" <?= !isset($groupRow['is_active']) || !empty($groupRow['is_active']) ? 'selected' : '' ?>>Si</option>
              <option value="0" <?= isset($groupRow['is_active']) && empty($groupRow['is_active']) ? 'selected' : '' ?>>No</option>
            </select>
          </label>

          <div class="av3-md-col-span-2 av3-flex av3-items-center av3-justify-end av3-gap-2">
            <?php if ($groupRow): ?>
              <a class="btn-secondary" href="<?= url('/admin/catalog-tags') ?>">Cancelar</a>
            <?php endif; ?>
            <button class="btn-primary"><?= $groupRow ? 'Guardar grupo' : 'Crear grupo' ?></button>
          </div>
        </form>

        <div class="av3-space-top-5 av3-overflow-x-auto">
          <table class="av3-table av3-width-full av3-type-body">
            <thead>
              <tr>
                <th>Grupo</th>
                <th class="av3-size-w-24 av3-text-right">Orden</th>
                <th class="av3-size-w-24 av3-text-right">Tags</th>
                <th class="av3-size-w-24">Activo</th>
                <th class="av3-size-w-24">Accion</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
              <tr class="av3-border-top av3-border-var-line-soft">
                <td class="av3-pad-y2 av3-pad-right-3">
                  <div class="av3-weight-medium"><?= e((string)($group['name'] ?? '')) ?></div>
                  <div class="av3-type-caption av3-text-var-muted"><?= e((string)($group['slug'] ?? '')) ?></div>
                </td>
                <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= (int)($group['sort'] ?? 0) ?></td>
                <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= (int)($group['tags_count'] ?? 0) ?></td>
                <td class="av3-pad-y2 av3-pad-right-3"><?= !empty($group['is_active']) ? '<span class="badge badge--ok">Si</span>' : '<span class="badge">No</span>' ?></td>
                <td class="av3-pad-y2 av3-pad-right-3"><a class="btn-secondary" href="<?= url('/admin/catalog-tags?group_id=' . (int)($group['id'] ?? 0)) ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($groups === []): ?>
              <tr><td colspan="5" class="av3-pad-y6 av3-text-var-muted">Todavia no hay grupos.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="av3-card">
        <header class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-4">
          <div>
            <h2 class="av3-type-base av3-weight-strong"><?= $tagRow ? 'Editar tag' : 'Nuevo tag' ?></h2>
            <p class="av3-type-body av3-text-var-muted">Ejemplos: Baitcast, Pejerrey, Telescopica.</p>
          </div>
        </header>

        <form class="av3-grid av3-md-grid-cols-2 av3-gap-3" method="post" action="<?= url('/admin/catalog-tags/tag/save') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($tagRow['id'] ?? 0) ?>">

          <label class="av3-block av3-md-col-span-2">
            <span class="label">Grupo</span>
            <select class="input av3-width-full" name="group_id">
              <option value="">Seleccione...</option>
              <?php foreach ($groups as $group): ?>
                <?php $gid = (int)($group['id'] ?? 0); ?>
                <option value="<?= $gid ?>" <?= ((int)($tagRow['group_id'] ?? 0) === $gid) ? 'selected' : '' ?>>
                  <?= e((string)($group['name'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="av3-block av3-md-col-span-2">
            <span class="label">Nombre</span>
            <input class="input av3-width-full" name="name" value="<?= e((string)($tagRow['name'] ?? '')) ?>" placeholder="Baitcast">
          </label>

          <label class="av3-block">
            <span class="label">Slug</span>
            <input class="input av3-width-full" name="slug" value="<?= e((string)($tagRow['slug'] ?? '')) ?>" placeholder="baitcast">
          </label>

          <label class="av3-block">
            <span class="label">Orden</span>
            <input class="input av3-width-full" type="number" min="0" name="sort" value="<?= (int)($tagRow['sort'] ?? 0) ?>">
          </label>

          <label class="av3-block av3-md-col-span-2">
            <span class="label">Activo</span>
            <select class="input av3-width-full" name="is_active">
              <option value="1" <?= !isset($tagRow['is_active']) || !empty($tagRow['is_active']) ? 'selected' : '' ?>>Si</option>
              <option value="0" <?= isset($tagRow['is_active']) && empty($tagRow['is_active']) ? 'selected' : '' ?>>No</option>
            </select>
          </label>

          <div class="av3-md-col-span-2 av3-flex av3-items-center av3-justify-end av3-gap-2">
            <?php if ($tagRow): ?>
              <a class="btn-secondary" href="<?= url('/admin/catalog-tags') ?>">Cancelar</a>
            <?php endif; ?>
            <button class="btn-primary"><?= $tagRow ? 'Guardar tag' : 'Crear tag' ?></button>
          </div>
        </form>

        <div class="av3-space-top-5 av3-overflow-x-auto">
          <table class="av3-table av3-width-full av3-type-body">
            <thead>
              <tr>
                <th>Tag</th>
                <th>Grupo</th>
                <th class="av3-size-w-24 av3-text-right">Orden</th>
                <th class="av3-size-w-24 av3-text-right">Uso</th>
                <th class="av3-size-w-24">Activo</th>
                <th class="av3-size-w-24">Accion</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($tags as $tag): ?>
              <tr class="av3-border-top av3-border-var-line-soft">
                <td class="av3-pad-y2 av3-pad-right-3">
                  <div class="av3-weight-medium"><?= e((string)($tag['name'] ?? '')) ?></div>
                  <div class="av3-type-caption av3-text-var-muted"><?= e((string)($tag['slug'] ?? '')) ?></div>
                </td>
                <td class="av3-pad-y2 av3-pad-right-3"><?= e((string)($tag['group_name'] ?? '')) ?></td>
                <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= (int)($tag['sort'] ?? 0) ?></td>
                <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= (int)($tag['products_count'] ?? 0) ?></td>
                <td class="av3-pad-y2 av3-pad-right-3"><?= !empty($tag['is_active']) ? '<span class="badge badge--ok">Si</span>' : '<span class="badge">No</span>' ?></td>
                <td class="av3-pad-y2 av3-pad-right-3"><a class="btn-secondary" href="<?= url('/admin/catalog-tags?tag_id=' . (int)($tag['id'] ?? 0)) ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($tags === []): ?>
              <tr><td colspan="6" class="av3-pad-y6 av3-text-var-muted">Todavia no hay tags.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</section>
