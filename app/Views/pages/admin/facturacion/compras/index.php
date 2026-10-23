<?php render_partial('admin/subnav'); ?>
<div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto av3-pad-4">
  <?php
ob_start(); ?>Compras<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderDescription='';
ob_start(); ?>
    
    <div class="av3-flex av3-gap-2 av3-flex-wrap">
      <a class="btn btn-primary" href="<?= e((string)($new_url ?? '')) ?>">Nueva compra</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="card av3-pad-4 av3-space-bottom-4">
    <form method="get" action="<?= e((string)($apply_url ?? '')) ?>" class="av3-grid av3-grid-cols-1 av3-md-grid-cols-6 av3-gap-3">
      <div>
        <label class="label">Desde</label>
        <input class="input" type="date" name="from" value="<?= e((string)($filters['from'] ?? '')) ?>">
      </div>
      <div>
        <label class="label">Hasta</label>
        <input class="input" type="date" name="to" value="<?= e((string)($filters['to'] ?? '')) ?>">
      </div>
      <div class="av3-md-col-span-2">
        <label class="label">Proveedor</label>
        <select class="input" name="supplier_id">
          <?php foreach (($supplier_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Tipo</label>
        <select class="input" name="kind">
          <?php foreach (($kind_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Estado</label>
        <select class="input" name="status">
          <?php foreach (($status_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="av3-md-col-span-4">
        <label class="label">Buscar (proveedor / CUIT / nro)</label>
        <input class="input" type="text" name="q" placeholder="Ej: Acme / 30... / 000123" value="<?= e((string)($filters['q'] ?? '')) ?>">
      </div>

      <div class="av3-md-col-span-2 av3-flex av3-items-end av3-gap-2">
        <button class="btn btn-primary av3-width-full" type="submit">Filtrar</button>
        <a class="btn btn-secondary av3-width-full" href="<?= e((string)($reset_url ?? '')) ?>">Limpiar</a>
      </div>
    </form>
  </div>

  <div class="card av3-p-0 av3-overflow-hidden">
    <div class="max-h-[70vh] av3-overflow-auto">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white av3-z-10">
          <tr>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Comprobante</th>
            <th class="av3-text-right">Neto</th>
            <th class="av3-text-right">IVA</th>
            <th class="av3-text-right">Total</th>
            <th>Estado</th>
            <th class="av3-text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($has_items)): ?>
            <tr><td colspan="8" class="av3-text-center av3-pad-y6 av3-text-gray-500">No hay compras para esos filtros.</td></tr>
          <?php endif; ?>

          <?php foreach (($items_prepared ?? []) as $row): ?>
            <tr class="<?= !empty($row['is_nc']) ? 'av3-bg-amber-50' : '' ?>">
              <td class="whitespace-nowrap"><?= e((string)($row['issued_at'] ?? '')) ?></td>
              <td>
                <div class="av3-weight-medium"><?= e((string)($row['supplier_nombre'] ?? '')) ?></div>
                <div class="av3-type-caption av3-text-gray-500"><?= e((string)($row['supplier_cuit'] ?? '')) ?></div>
              </td>
              <td class="whitespace-nowrap">
                <div class="av3-type-body">Tipo <?= (int)($row['cbte_tipo'] ?? 0) ?> � PV <?= (int)($row['pto_vta'] ?? 0) ?> � N� <?= (int)($row['cbte_nro'] ?? 0) ?></div>
                <div class="av3-type-caption av3-text-gray-500"><?= e((string)($row['kind_label'] ?? '')) ?></div>
              </td>
              <td class="av3-text-right"><?= e((string)($row['neto_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['iva_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['total_label'] ?? '$ 0,00')) ?></td>
              <td class="whitespace-nowrap"><span class="badge <?= e((string)($row['status_badge_class'] ?? 'badge-secondary')) ?>"><?= e((string)($row['status_label'] ?? '')) ?></span></td>
              <td class="av3-text-right whitespace-nowrap">
                <a class="btn btn-secondary btn-sm" href="<?= e((string)($row['edit_url'] ?? '')) ?>">Editar</a>
                <?php if (!empty($row['show_validate'])): ?>
                  <form method="post" action="<?= e((string)($row['validate_url'] ?? '')) ?>" class="av3-inline">
                    <?= $csrf_field ?? '' ?>
                    <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>">
                    <button class="btn btn-outline btn-sm" type="submit">Validar</button>
                  </form>
                <?php endif; ?>
                <?php if (!empty($row['show_void'])): ?>
                  <form method="post" action="<?= e((string)($row['void_url'] ?? '')) ?>" class="av3-inline">
                    <?= $csrf_field ?? '' ?>
                    <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>">
                    <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('�Anular esta compra?')">Anular</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <nav class="av3-fiscal-pager" aria-label="Paginación de compras">
    <p>Mostrando <?= (int)($pagination['from'] ?? 0) ?>–<?= (int)($pagination['to'] ?? 0) ?> de <?= (int)($pagination['total'] ?? 0) ?> compras</p>
    <div>
      <?php if (!empty($pagination['prev_url'])): ?><a class="btn btn-secondary" rel="prev" href="<?= e((string)$pagination['prev_url']) ?>">Anterior</a><?php endif; ?>
      <span>Página <?= (int)($pagination['page'] ?? 1) ?> de <?= (int)($pagination['pages'] ?? 1) ?></span>
      <?php if (!empty($pagination['next_url'])): ?><a class="btn btn-secondary" rel="next" href="<?= e((string)$pagination['next_url']) ?>">Siguiente</a><?php endif; ?>
    </div>
  </nav>

  <p class="av3-type-caption av3-text-gray-500 av3-space-top-3">Nota: las Notas de credito se muestran en negativo (restan).</p>
</div>
