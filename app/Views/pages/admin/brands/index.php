<?php
$pageTitle = 'Marcas';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('brands.index');
$rows = is_array($rows ?? null) ? $rows : [];
$q = (string)($q ?? '');
$total = (int)($total ?? count($rows));
$perPage = (int)($perPage ?? 25);
$page = (int)($page ?? 1);
$pages = (int)($pages ?? 1);
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/brands.css')) ?>">
<section class="admin-surface">
  <div class="av3-wrap av3-layout--operational av3-brands av3-brands--index">
    <?php
    $adminHeaderTitle = 'Marcas';
    $adminHeaderDescription = 'Gestioná las marcas del catálogo.';
    $adminHeaderActions = '<a class="av3-ui-btn av3-ui-btn--primary" href="' . e(url('/admin/brands/new')) . '">Nueva marca</a>';
    $adminHeaderBreadcrumbs = $crumbs ?: [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Marcas','href'=>url('/admin/brands')]];
    require BASE_PATH . '/app/Views/partials/admin/page_header.php';
    ?>

    <?php foreach (['ok' => 'success', 'error' => 'error', 'warn' => 'warning'] as $type => $tone): ?>
      <?php if ($message = flash($type)): ?>
        <div class="av3-brands__notice av3-brands__notice--<?= e($tone) ?>" role="status"><?= e((string)$message) ?></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <section class="av3-ui-card av3-brands__panel" aria-label="Listado de marcas">
      <form class="av3-brands__toolbar" action="<?= e(url('/admin/brands')) ?>" method="get">
        <div class="av3-ui-field av3-brands__search">
          <label class="av3-ui-field-label" for="brands-search">Buscar marcas</label>
          <input class="av3-ui-control" id="brands-search" type="search" name="q" value="<?= e($q) ?>" maxlength="120" placeholder="Nombre o slug" autocomplete="off">
        </div>
        <div class="av3-ui-field av3-brands__per">
          <label class="av3-ui-field-label" for="brands-per">Mostrar</label>
          <select class="av3-ui-control" id="brands-per" name="per">
            <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 por página</option>
            <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 por página</option>
          </select>
        </div>
        <button class="av3-ui-btn av3-ui-btn--primary av3-brands__submit" type="submit">Buscar</button>
        <?php if ($q !== '' || $perPage !== 25): ?>
          <a class="av3-ui-btn av3-brands__reset" href="<?= e(url('/admin/brands')) ?>">Limpiar</a>
        <?php endif; ?>
      </form>

      <div class="av3-brands__list-head">
        <div>
          <h2>Marcas del catálogo</h2>
          <p><?= $total === 1 ? '1 marca encontrada' : number_format($total, 0, ',', '.') . ' marcas encontradas' ?></p>
        </div>
        <?php if ($total > 0): ?>
          <span class="av3-brands__range"><?= (int)($start ?? 1) ?>–<?= (int)($end ?? $total) ?> de <?= $total ?></span>
        <?php endif; ?>
      </div>

      <?php if (!$rows): ?>
        <div class="av3-ui-empty av3-brands__empty">
          <span class="av3-brands__empty-icon" aria-hidden="true">◇</span>
          <h3><?= $q !== '' ? 'No encontramos marcas' : 'Todavía no hay marcas' ?></h3>
          <p><?= $q !== '' ? 'Probá con otro nombre o borrá los filtros.' : 'Creá una marca para organizar los productos de tu catálogo.' ?></p>
          <a class="av3-ui-btn <?= $q !== '' ? '' : 'av3-ui-btn--primary' ?>" href="<?= e(url($q !== '' ? '/admin/brands' : '/admin/brands/new')) ?>"><?= $q !== '' ? 'Ver todas' : 'Crear marca' ?></a>
        </div>
      <?php else: ?>
        <div class="av3-ui-table-scroll av3-brands__table-wrap">
          <table class="av3-ui-table av3-brands__table">
            <thead><tr>
              <th scope="col">Marca</th>
              <th scope="col">Slug</th>
              <th scope="col">Productos</th>
              <th scope="col">Orden</th>
              <th scope="col">Creación</th>
              <th scope="col" class="av3-brands__action-head">Acciones</th>
            </tr></thead>
            <tbody>
              <?php foreach ($rows as $brand): ?>
                <?php
                  $brandId = (int)$brand['id'];
                  $cover = trim((string)($brand['cover_image'] ?? ''));
                  $editUrl = url('/admin/brands/edit?id=' . $brandId);
                  $productCount = (int)$brand['product_count'];
                  $activeCount = (int)$brand['active_product_count'];
                ?>
                <tr>
                  <td>
                    <div class="av3-brands__identity">
                      <?php if ($cover !== ''): ?>
                        <img class="av3-brands__thumb" src="<?= e(\App\Services\Admin\Brands\BrandCoverUrl::resolve($cover)) ?>" alt="" loading="lazy" decoding="async">
                      <?php else: ?>
                        <span class="av3-brands__thumb av3-brands__thumb--placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string)$brand['name'], 0, 1))) ?></span>
                      <?php endif; ?>
                      <span class="av3-brands__identity-text"><a href="<?= e($editUrl) ?>"><?= e((string)$brand['name']) ?></a><small>#<?= $brandId ?></small></span>
                    </div>
                  </td>
                  <td><span class="av3-brands__slug"><?= e((string)$brand['slug']) ?></span></td>
                  <td><span class="av3-brands__count"><?= $productCount ?></span><small class="av3-brands__muted"><?= $activeCount ?> activos</small></td>
                  <td class="av3-brands__numeric"><?= (int)$brand['sort'] ?></td>
                  <td class="av3-brands__date"><?= e((string)($brand['created_at'] ?? '—')) ?></td>
                  <td class="av3-brands__actions"><a class="av3-ui-btn av3-ui-btn--small" href="<?= e($editUrl) ?>">Editar</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="av3-brands__mobile-list">
          <?php foreach ($rows as $brand): ?>
            <?php
              $brandId = (int)$brand['id'];
              $cover = trim((string)($brand['cover_image'] ?? ''));
            ?>
            <a class="av3-brands__mobile-item" href="<?= e(url('/admin/brands/edit?id=' . $brandId)) ?>">
              <?php if ($cover !== ''): ?>
                <img class="av3-brands__thumb" src="<?= e(\App\Services\Admin\Brands\BrandCoverUrl::resolve($cover)) ?>" alt="" loading="lazy" decoding="async">
              <?php else: ?>
                <span class="av3-brands__thumb av3-brands__thumb--placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string)$brand['name'], 0, 1))) ?></span>
              <?php endif; ?>
              <span class="av3-brands__mobile-main">
                <strong><?= e((string)$brand['name']) ?></strong>
                <small><?= e((string)$brand['slug']) ?></small>
                <span class="av3-brands__mobile-meta"><?= (int)$brand['product_count'] ?> productos · <?= (int)$brand['active_product_count'] ?> activos · Orden <?= (int)$brand['sort'] ?></span>
              </span>
              <span class="av3-brands__mobile-arrow" aria-hidden="true">›</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($pages > 1): ?>
        <nav class="av3-brands__pagination" aria-label="Paginación de marcas">
          <span>Página <?= $page ?> de <?= $pages ?></span>
          <div class="av3-ui-actions">
            <?php if (!empty($prevUrl)): ?><a class="av3-ui-btn av3-ui-btn--small" rel="prev" href="<?= e($prevUrl) ?>">Anterior</a><?php endif; ?>
            <?php if (!empty($nextUrl)): ?><a class="av3-ui-btn av3-ui-btn--small" rel="next" href="<?= e($nextUrl) ?>">Siguiente</a><?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>
    </section>
  </div>
</section>
