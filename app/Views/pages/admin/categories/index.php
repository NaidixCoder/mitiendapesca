<?php
$pageTitle = 'Categorías';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('categories.index');
$rows = is_array($rows ?? null) ? $rows : [];
$q = (string)($q ?? '');
$showInactive = (bool)($showInactive ?? false);
$summary = is_array($summary ?? null) ? $summary : [];
$per = (int)($per ?? 25);
$page = (int)($page ?? 1);
$pages = (int)($pages ?? 1);
$total = (int)($total ?? count($rows));
$nextReturn = current_url(true);
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/categories.css')) ?>">
<section class="admin-surface av3-categories">
  <div class="av3-wrap av3-layout--operational av3-categories__layout">
    <?php
    $adminHeaderTitle = 'Categorías';
    $adminHeaderDescription = '';
    ob_start(); ?>
      <a class="av3-ui-btn av3-ui-btn--primary" href="<?= url('/admin/categories/new') ?>">Nueva categoría</a>
    <?php $adminHeaderActions = trim(ob_get_clean());
    $adminHeaderBreadcrumbs = $crumbs ?: [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Categorías']];
    require BASE_PATH . '/app/Views/partials/admin/page_header.php';
    ?>

    <?php foreach (['ok'=>'success','error'=>'error','warn'=>'warning'] as $flashType=>$tone): ?>
      <?php if ($message = flash($flashType)): ?>
        <div class="av3-categories__notice av3-categories__notice--<?= $tone ?>" role="status"><?= e($message) ?></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="av3-categories__summary" aria-label="Resumen de categorías">
      <div class="av3-categories__metric">
        <span>Total de categorías</span><strong><?= (int)($summary['total'] ?? 0) ?></strong>
      </div>
      <div class="av3-categories__metric">
        <span>Activas</span><strong><?= (int)($summary['active'] ?? 0) ?></strong>
      </div>
      <div class="av3-categories__metric">
        <span>Visibles en tienda</span><strong><?= (int)($summary['public_visible'] ?? 0) ?></strong>
      </div>
      <div class="av3-categories__metric">
        <span>Categorías principales</span><strong><?= (int)($summary['roots'] ?? 0) ?></strong>
      </div>
    </div>

    <form class="av3-ui-filters av3-categories__filters" action="<?= url('/admin/categories') ?>" method="get">
      <div class="av3-ui-field av3-categories__search">
        <label class="av3-ui-field-label" for="categories-search">Buscar categorías</label>
        <input class="av3-ui-control" id="categories-search" type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre o slug" maxlength="100">
      </div>
      <label class="av3-categories__check">
        <input type="checkbox" name="show_inactive" value="1" <?= $showInactive ? 'checked' : '' ?>>
        <span>Incluir inactivas</span>
      </label>
      <div class="av3-ui-field av3-categories__per">
        <label class="av3-ui-field-label" for="categories-per">Por página</label>
        <select class="av3-ui-control" id="categories-per" name="per">
          <option value="25" <?= $per===25 ? 'selected' : '' ?>>25</option>
          <option value="50" <?= $per===50 ? 'selected' : '' ?>>50</option>
        </select>
      </div>
      <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Aplicar filtros</button>
      <a class="av3-ui-btn" href="<?= url('/admin/categories') ?>">Limpiar</a>
    </form>

    <section class="av3-categories__panel" aria-labelledby="categories-results-title">
      <header class="av3-categories__panel-head">
        <div>
          <h2 id="categories-results-title">Listado de categorías</h2>
          <p><?= $total ? (int)($start ?? 1) . '–' . (int)($end ?? $total) . ' de ' . $total . ' resultados' : 'Sin resultados' ?></p>
        </div>
      </header>
      <?php if (!$rows): ?>
        <div class="av3-ui-empty av3-categories__empty">
          <h3><?= $q !== '' || $showInactive ? 'No se encontraron categorías' : 'No hay categorías activas' ?></h3>
          <p>Probá con otra búsqueda o modificá los filtros.</p>
          <a href="<?= url('/admin/categories') ?>">Ver listado sin filtros</a>
        </div>
      <?php else: ?>
        <div class="av3-ui-table-scroll av3-categories__table-scroll">
          <table class="av3-ui-table av3-categories__table">
            <thead><tr><th>Categoría</th><th>Jerarquía</th><th>Productos directos</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $active = (int)($r['is_active'] ?? 0) === 1;
                $publicVisible = !empty($r['public_visible']);
                $cover = trim((string)($r['cover_image'] ?? ''));
                $editHref = url('/admin/categories/edit?id='.(int)$r['id']);
              ?>
              <tr>
                <td>
                  <div class="av3-categories__identity">
                    <?php if ($cover !== ''): ?>
                      <img data-categories-image src="<?= e(public_url($cover)) ?>" alt="" width="52" height="52" loading="lazy">
                    <?php else: ?>
                      <span class="av3-categories__placeholder" aria-hidden="true">C</span>
                    <?php endif; ?>
                    <div class="av3-categories__identity-text">
                      <a href="<?= $editHref ?>"><?= e((string)$r['name']) ?></a>
                      <small><?= e((string)$r['slug']) ?> · #<?= (int)$r['id'] ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="av3-categories__hierarchy"><?= !empty($r['parent_id']) ? e((string)($r['parent_name'] ?? 'Padre no disponible')) : 'Principal' ?></span>
                  <?php if ((int)($r['child_count'] ?? 0) > 0): ?>
                    <small class="av3-categories__hint"><?= (int)$r['child_count'] ?> subcategorías</small>
                  <?php endif; ?>
                </td>
                <td class="av3-categories__number"><?= (int)($r['product_count'] ?? 0) ?></td>
                <td class="av3-categories__number"><?= (int)$r['sort'] ?></td>
                <td>
                  <span class="av3-ui-badge <?= $active ? '' : 'av3-ui-badge--neutral' ?>"><?= $active ? 'Activa' : 'Inactiva' ?></span>
                  <?php if ($active && !$publicVisible): ?>
                    <small class="av3-categories__public-warning" title="No es visible por un ancestro inactivo, huérfano o ciclo">Oculta en tienda</small>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="av3-ui-actions av3-categories__actions">
                    <a class="av3-ui-btn" href="<?= $editHref ?>">Editar</a>
                    <?php require BASE_PATH . '/app/Views/partials/admin/categories/toggle_action.php'; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="av3-categories__mobile">
          <?php foreach ($rows as $r): ?>
            <?php $active = (int)($r['is_active'] ?? 0) === 1; $publicVisible = !empty($r['public_visible']); $cover = trim((string)($r['cover_image'] ?? '')); $editHref = url('/admin/categories/edit?id='.(int)$r['id']); ?>
            <article class="av3-categories__mobile-item">
              <div class="av3-categories__identity">
                <?php if ($cover !== ''): ?>
                  <img data-categories-image src="<?= e(public_url($cover)) ?>" alt="" width="52" height="52" loading="lazy">
                <?php else: ?>
                  <span class="av3-categories__placeholder" aria-hidden="true">C</span>
                <?php endif; ?>
                <div class="av3-categories__identity-text">
                  <a href="<?= $editHref ?>"><?= e((string)$r['name']) ?></a>
                  <small><?= e((string)$r['slug']) ?></small>
                </div>
                <div class="av3-categories__mobile-state">
                  <span class="av3-ui-badge <?= $active ? '' : 'av3-ui-badge--neutral' ?>"><?= $active ? 'Activa' : 'Inactiva' ?></span>
                  <?php if ($active && !$publicVisible): ?>
                    <small class="av3-categories__public-warning">Oculta en tienda</small>
                  <?php endif; ?>
                </div>
              </div>
              <p class="av3-categories__mobile-meta">
                <span><?= !empty($r['parent_id']) ? 'Dentro de ' . e((string)($r['parent_name'] ?? '–')) : 'Principal' ?></span>
                <span><?= (int)($r['product_count'] ?? 0) ?> productos</span>
                <span><?= (int)($r['child_count'] ?? 0) ?> subcategorías</span>
                <span>Orden <?= (int)$r['sort'] ?></span>
              </p>
              <div class="av3-ui-actions av3-categories__actions">
                <a class="av3-ui-btn" href="<?= $editHref ?>">Editar</a>
                <?php require BASE_PATH . '/app/Views/partials/admin/categories/toggle_action.php'; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($total > $per): ?>
        <footer class="av3-categories__pagination">
          <span>Página <?= $page ?> de <?= $pages ?></span>
          <div class="av3-ui-actions av3-categories__actions">
            <?php if (!empty($previousUrl)): ?><a class="av3-ui-btn" href="<?= e($previousUrl) ?>">Anterior</a><?php endif; ?>
            <?php if (!empty($nextUrl)): ?><a class="av3-ui-btn" href="<?= e($nextUrl) ?>">Siguiente</a><?php endif; ?>
          </div>
        </footer>
      <?php endif; ?>
    </section>
  </div>
</section>
<script type="module" src="<?= e(asset('js/admin/categories/index.js')) ?>"></script>
