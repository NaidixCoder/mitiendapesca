<?php
/** @var array $items */
$q = (string)($q ?? '');
$status = (string)($status ?? 'active');
$per = (int)($per ?? 25);
$page = (int)($page ?? 1);
$pages = (int)($pages ?? 1);
$total = (int)($total ?? count($items ?? []));
section('partials/admin/subnav');
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/proveedores.css')) ?>">
<?php
$adminHeaderTitle = 'Proveedores';
$adminHeaderDescription = 'Contactos, datos fiscales y gestión de proveedores.';
$adminHeaderActions = '<a class="av3-ui-btn av3-ui-btn--primary" href="' . e(url('/admin/proveedores/new')) . '">Nuevo proveedor</a>';
$adminHeaderBreadcrumbs = [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Proveedores','href'=>url('/admin/proveedores')]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<div class="av3-proveedores av3-ui-page">
  <section class="av3-ui-card">
    <div class="av3-ui-card-body">
      <form class="av3-proveedores__filters" method="get" action="<?= e(url('/admin/proveedores')) ?>">
        <div class="av3-ui-field">
          <label class="av3-ui-field-label" for="supplier-q">Buscar proveedor</label>
          <input id="supplier-q" class="av3-ui-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre o CUIT">
        </div>
        <div class="av3-ui-field">
          <label class="av3-ui-field-label" for="supplier-status">Estado</label>
          <select id="supplier-status" class="av3-ui-control" name="status">
            <?php foreach (['active'=>'Activos','inactive'=>'Inactivos','all'=>'Todos'] as $value=>$label): ?>
              <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="av3-ui-field">
          <label class="av3-ui-field-label" for="supplier-per">Por página</label>
          <select id="supplier-per" class="av3-ui-control" name="per">
            <?php foreach ([25,50] as $value): ?><option value="<?= $value ?>" <?= $per === $value ? 'selected' : '' ?>><?= $value ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="av3-proveedores__filter-actions">
          <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Aplicar filtros</button>
          <a class="av3-ui-btn" href="<?= e(url('/admin/proveedores')) ?>">Limpiar</a>
        </div>
      </form>
      <div class="av3-proveedores__summary"><?= $total ?> proveedor<?= $total === 1 ? '' : 'es' ?> encontrado<?= $total === 1 ? '' : 's' ?></div>
      <?php if (!$items): ?>
        <div class="av3-ui-empty">No se encontraron proveedores con estos filtros.</div>
      <?php else: ?>
        <div class="av3-ui-table-scroll av3-proveedores__desktop">
          <table class="av3-ui-table">
            <thead><tr><th>Proveedor</th><th>CUIT</th><th>Condición IVA</th><th>Contacto</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><strong><?= e($it['nombre'] ?? '') ?></strong><div class="av3-proveedores__muted">#<?= (int)$it['id'] ?></div></td>
                <td><?= e($it['cuit'] ?? '') ?></td>
                <td><?= e(($it['condicion_iva'] ?? '') ?: '—') ?></td>
                <td><div><?= e(($it['email'] ?? '') ?: '—') ?></div><div class="av3-proveedores__muted"><?= e(($it['telefono'] ?? '') ?: '') ?></div></td>
                <td><span class="av3-ui-badge av3-ui-badge--neutral"><?= !empty($it['is_active']) ? 'Activo' : 'Inactivo' ?></span></td>
                <td><?php require BASE_PATH.'/app/Views/partials/admin/proveedores/row_actions.php'; ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="av3-proveedores__mobile">
          <?php foreach ($items as $it): ?>
          <article class="av3-proveedores__item">
            <div class="av3-proveedores__item-top"><strong><?= e($it['nombre'] ?? '') ?></strong><span class="av3-ui-badge av3-ui-badge--neutral"><?= !empty($it['is_active']) ? 'Activo' : 'Inactivo' ?></span></div>
            <div class="av3-proveedores__muted">CUIT <?= e($it['cuit'] ?? '') ?></div>
            <div><?= e(($it['email'] ?? '') ?: ($it['telefono'] ?? 'Sin datos de contacto')) ?></div>
            <?php require BASE_PATH.'/app/Views/partials/admin/proveedores/row_actions.php'; ?>
          </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($pages > 1): ?>
      <nav class="av3-proveedores__pager" aria-label="Paginación">
        <span>Página <?= $page ?> de <?= $pages ?></span>
        <?php if ($page > 1): ?><a class="av3-ui-btn av3-ui-btn--small" href="<?= e($prevUrl) ?>">Anterior</a><?php endif; ?>
        <?php if ($page < $pages): ?><a class="av3-ui-btn av3-ui-btn--small" href="<?= e($nextUrl) ?>">Siguiente</a><?php endif; ?>
      </nav>
      <?php endif; ?>
    </div>
  </section>
</div>
