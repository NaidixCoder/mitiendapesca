<?php
$pageTitle = 'Categorias';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('categories.index');
/** @var array $rows */
$q = trim((string)($q ?? ''));
$showInactive = !empty($showInactive);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php
ob_start(); ?>Categorias<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Jerarquia, orden y visibilidad.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
      
      <a class="btn-primary" href="<?= url('/admin/categories/new') ?>">Nueva categoria</a>
    <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Categorías','href'=>url('/admin/categories')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <form class="av3-card av3-pad-3" method="get" action="<?= url('/admin/categories') ?>">
      <div class="av3-flex av3-flex-wrap av3-items-end av3-gap-2">
        <div>
          <label class="label">Buscar</label>
          <input class="input av3-w-72" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o slug">
        </div>
        <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-body">
          <input type="checkbox" name="show_inactive" value="1" <?= $showInactive ? 'checked' : '' ?>>
          <span>Mostrar inactivas</span>
        </label>
        <button class="btn-secondary" type="submit">Buscar</button>
        <a class="btn-secondary" href="<?= url('/admin/categories') ?>">Limpiar</a>
      </div>
    </form>

    <?php if ($m = flash('ok')): ?>
      <div class="av3-alert ok"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
      <div class="av3-alert error"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('warn')): ?>
      <div class="av3-alert warn"><?= e($m) ?></div>
    <?php endif; ?>

    <section class="av3-card av3-overflow-x-auto">
      <table class="av3-table av3-width-full">
        <thead>
          <tr>
            <th>ID</th>
            <th style="width:110px">Portada</th>
            <th>Nombre</th>
            <th>Slug</th>
            <th>Padre</th>
            <th>Orden</th>
            <th>Activo</th>
            <th class="av3-text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($rows ?? []) as $r): ?>
            <?php $cover = trim((string)($r['cover_image'] ?? '')); ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td>
                <?php if ($cover !== ''): ?>
                  <img src="<?= e(public_url($cover)) ?>" alt="" style="width:72px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                <?php else: ?>
                  <span class="hint">-</span>
                <?php endif; ?>
              </td>
              <td class="av3-weight-medium"><?= e($r['name']) ?></td>
              <td><?= e($r['slug']) ?></td>
              <td><?= e($r['parent_name'] ?? '-') ?></td>
              <td><?= (int)($r['sort'] ?? 0) ?></td>
              <td>
                <?php $active = !empty($r['is_active']); ?>
                <span class="badge-status <?= $active ?'ok' : 'warn' ?>"><?= $active ? 'Si' : 'No' ?></span>
              </td>
              <td class="av3-text-right">
                <div class="av3-inline-flex av3-gap-2">
                  <a class="btn-secondary" href="<?= url('/admin/categories/edit?id='.(int)$r['id']) ?>">Editar</a>
                  <?php if ($active): ?>
                    <form method="post" action="<?= url('/admin/categories/toggle') ?>" class="av3-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="next" value="<?= e(current_url(true)) ?>">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="is_active" value="0">
                      <button class="btn-ghost" type="submit">Desactivar</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= url('/admin/categories/toggle') ?>" class="av3-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="next" value="<?= e(current_url(true)) ?>">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="is_active" value="1">
                      <button class="btn-primary" type="submit">Activar</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="av3-py-10 av3-text-center av3-text-var-admin-muted">Sin resultados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <div><a class="link av3-type-body" href="<?= url('/admin') ?>">Volver al dashboard</a></div>

  </div>
</section>
