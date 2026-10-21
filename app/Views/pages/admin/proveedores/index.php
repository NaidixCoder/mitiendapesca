<?php
/** @var string $q */
/** @var array $items */
?>
<?php section('partials/admin/subnav'); ?>

<div class="card">
  <?php
ob_start(); ?>Proveedores<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Administrá tus proveedores. (CUIT único, baja lógica)<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <a class="btn btn-primary" href="<?= e(url('/admin/proveedores/new')) ?>">Nuevo proveedor</a>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Proveedores','href'=>url('/admin/proveedores')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

  <div class="card-body">
    <form class="av3-flex av3-gap-2 av3-items-center av3-space-bottom-4" method="get" action="<?= e(url('/admin/proveedores')) ?>">
      <input class="input av3-width-full" type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o CUIT...">
      <button class="btn" type="submit">Buscar</button>
      <?php if ($q !== ''): ?>
        <a class="btn btn-ghost" href="<?= e(url('/admin/proveedores')) ?>">Limpiar</a>
      <?php endif; ?>
    </form>

    <div class="table-wrap" style="max-height: 70vh; overflow:auto;">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white">
          <tr>
            <th>Proveedor</th>
            <th>CUIT</th>
            <th>Condición IVA</th>
            <th>Contacto</th>
            <th class="av3-text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="5" class="muted">No hay proveedores.</td></tr>
          <?php endif; ?>

          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <div class="av3-weight-medium"><?= e($it['nombre'] ?? '') ?></div>
                <div class="muted av3-type-body">#<?= (int)$it['id'] ?></div>
              </td>
              <td><?= e($it['cuit'] ?? '') ?></td>
              <td><?= e(($it['condicion_iva'] ?? '') ?: '—') ?></td>
              <td>
                <div class="av3-type-body"><?= e(($it['email'] ?? '') ?: '—') ?></div>
                <div class="av3-type-body"><?= e(($it['telefono'] ?? '') ?: '—') ?></div>
              </td>
              <td class="av3-text-right">
                <a class="btn btn-sm" href="<?= e(url('/admin/proveedores/edit?id=' . (int)$it['id'])) ?>">Editar</a>

                <form method="post" action="<?= e(url('/admin/proveedores/delete')) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit"
                    onclick="return confirm('¿Eliminar proveedor? (baja lógica)')">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
