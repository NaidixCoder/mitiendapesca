<?php
$pageTitle = 'Marcas';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('brands.index');
/** @var array $rows */
$q = trim((string)($q ?? ''));
?>
<section class="admin-surface">
  <div class="av3-wrap av3-page-stack">

    <?php
ob_start(); ?>Marcas<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Gestiona las marcas disponibles.<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
        <a class="btn-primary" href="<?= url('/admin/brands/new') ?>">Nueva marca</a>
      <?php $adminHeaderActions=ob_get_clean();
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Marcas','href'=>url('/admin/brands')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <?php if ($m = flash('ok')): ?>
      <div class="alert alert-ok"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
      <div class="alert alert-error"><?= e($m) ?></div>
    <?php endif; ?>

    <form class="av3-card" method="get" action="<?= url('/admin/brands') ?>">
      <div class="av3-form-grid">
        <div>
          <label class="av3-label">Buscar</label>
          <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o slug">
        </div>
      </div>
      <div class="av3-form-actions">
        <button class="btn-primary" type="submit">Buscar</button>
        <a class="btn-secondary" href="<?= url('/admin/brands') ?>">Limpiar</a>
      </div>
    </form>

    <section class="av3-card">
      <header class="av3-card-header">
        <h3>Marcas</h3>
        <div class="av3-card-meta"><?= (int)count($rows ?? []) ?> resultados</div>
      </header>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th style="width:90px">ID</th>
              <th style="width:110px">Portada</th>
              <th>Nombre</th>
              <th>Slug</th>
              <th style="width:90px">Orden</th>
              <th style="width:190px">Creada</th>
              <th style="width:130px">Acciones</th>
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
                <td class="av3-emphasis"><?= e($r['name']) ?></td>
                <td><?= e($r['slug']) ?></td>
                <td><?= (int)($r['sort'] ?? 0) ?></td>
                <td><?= e($r['created_at'] ?? '') ?></td>
                <td>
                  <a class="btn-secondary" href="<?= url('/admin/brands/edit?id='.(int)$r['id']) ?>">Editar</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
              <tr><td colspan="7" class="av3-empty">Sin resultados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <div><a class="link" href="<?= url('/admin') ?>">Volver al dashboard</a></div>

  </div>
</section>
