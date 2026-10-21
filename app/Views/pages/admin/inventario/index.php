<?php
/** @var string $q */
/** @var array<int,array<string,mixed>> $items_prepared */
/** @var array<string,int|float> $totals */
?>
<div class="av3-max-w-6xl av3-mx-auto av3-pad-x4 av3-pad-y6">
  <?php
ob_start(); ?>Inventario<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Items vendibles (productos simples + variantes). Util para calcular capital invertido.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Inventario','href'=>url('/admin/inventario')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

  <div class="av3-space-top-4 av3-grid av3-grid-cols-1 av3-sm-grid-cols-2 av3-lg-grid-cols-4 av3-gap-3">
    <div class="av3-radius-card av3-border av3-surface-white av3-pad-4">
      <div class="av3-type-caption av3-text-slate-500">Capital (con IVA)</div>
      <div class="av3-type-xl av3-weight-strong av3-space-top-1"><?= money($totals['capital_final'] ?? 0) ?></div>
      <div class="av3-type-caption av3-text-slate-400 av3-space-top-1">Neto: <?= money($totals['capital'] ?? 0) ?></div>
    </div>
    <div class="av3-radius-card av3-border av3-surface-white av3-pad-4">
      <div class="av3-type-caption av3-text-slate-500">Valor de venta (precio x stock)</div>
      <div class="av3-type-xl av3-weight-strong av3-space-top-1"><?= money($totals['venta'] ?? 0) ?></div>
      <div class="av3-type-caption av3-text-slate-400 av3-space-top-1"><?= (int)($totals['items'] ?? 0) ?> items � <?= (int)($totals['stock_units'] ?? 0) ?> unidades</div>
    </div>
    <div class="av3-radius-card av3-border av3-surface-white av3-pad-4">
      <div class="av3-type-caption av3-text-slate-500">Margen potencial (con IVA)</div>
      <div class="av3-type-xl av3-weight-strong av3-space-top-1"><?= money($totals['margen_final'] ?? 0) ?></div>
      <div class="av3-type-caption av3-text-slate-400 av3-space-top-1">Neto: <?= money($totals['margen_neto'] ?? 0) ?></div>
    </div>
    <div class="av3-radius-card av3-border av3-surface-white av3-pad-4">
      <div class="av3-type-caption av3-text-slate-500">Nota</div>
      <div class="av3-type-body av3-text-slate-700 av3-space-top-1">Costo = <b>sin IVA</b>. El sistema deriva costo final usando <b>IVA del producto</b>.</div>
      <div class="av3-type-caption av3-text-slate-400 av3-space-top-1">Regla de Oro IVA</div>
    </div>
  </div>

  <form class="av3-space-top-5 av3-radius-card av3-border av3-surface-white av3-pad-4" method="get" action="<?= e(url('/admin/inventario')) ?>">
    <div class="av3-type-caption av3-weight-strong av3-text-slate-500 av3-space-bottom-2">BUSCAR</div>
    <div class="av3-flex av3-items-center av3-gap-2">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por SKU, nombre o variante" class="av3-width-full av3-radius-full av3-border av3-pad-x4 av3-pad-y2" />
      <button class="av3-radius-full av3-border av3-pad-x4 av3-pad-y2 av3-type-body av3-weight-strong">Buscar</button>
      <a class="av3-type-body av3-text-slate-500 av3-hover-text-slate-700" href="<?= e(url('/admin/inventario')) ?>">Limpiar</a>
    </div>
  </form>

  <div class="av3-space-top-5 av3-radius-card av3-border av3-surface-white av3-overflow-hidden">
    <div class="av3-pad-x4 av3-pad-y3 av3-border-bottom">
      <div class="av3-type-body av3-weight-strong">Listado</div>
      <div class="av3-type-caption av3-text-slate-500 av3-space-top-1">SKU � Descripcion � Stock � Costo (sin IVA) � IVA � Costo final � Venta � Capital (con IVA)</div>
    </div>

    <div class="max-h-[70vh] av3-overflow-auto">
      <table class="av3-width-full av3-type-body">
        <thead class="av3-sticky top-0 av3-surface-white av3-border-bottom">
          <tr class="av3-type-caption uppercase av3-text-slate-500">
            <th class="av3-text-left av3-pad-x4 av3-pad-y3 av3-w--140px">SKU</th>
            <th class="av3-text-left av3-pad-x4 av3-pad-y3">Descripcion</th>
            <th class="av3-text-right av3-pad-x4 av3-pad-y3 av3-w--110px">Stock</th>
            <th class="av3-text-right av3-pad-x4 av3-pad-y3 av3-w--140px">Costo (sin IVA)</th>
            <th class="av3-text-right av3-pad-x4 av3-pad-y3 av3-w--90px">IVA</th>
            <th class="av3-text-right av3-pad-x4 av3-pad-y3 av3-w--140px">Costo final</th>
            <th class="av3-text-right av3-pad-x4 av3-pad-y3 av3-w--140px">Precio venta</th>
            <th class="av3-text-right av3-pad-x4 av3-pad-y3 av3-w--170px">Capital (con IVA)</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($items_prepared)): ?>
          <tr>
            <td colspan="8" class="av3-pad-x4 av3-pad-y6 av3-text-center av3-text-slate-500">No hay items para mostrar.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($items_prepared as $it): ?>
          <tr class="av3-border-bottom last:border-b-0">
            <td class="av3-pad-x4 av3-pad-y3 av3-font-mono av3-type-caption av3-text-slate-700"><?= $it['sku'] !== '' ? e((string)$it['sku']) : '<span class="av3-text-slate-400">�</span>' ?></td>
            <td class="av3-pad-x4 av3-pad-y3">
              <div class="av3-weight-medium"><?= e((string)$it['description']) ?></div>
              <div class="av3-type-caption av3-text-slate-500 av3-mt-0_5"><?= e((string)$it['type_label']) ?></div>
            </td>
            <td class="av3-pad-x4 av3-pad-y3 av3-text-right av3-weight-strong"><?= (int)$it['stock'] ?></td>
            <td class="av3-pad-x4 av3-pad-y3 av3-text-right"><?= money((int)$it['cost']) ?></td>
            <td class="av3-pad-x4 av3-pad-y3 av3-text-right av3-type-caption av3-text-slate-600"><?= e((string)$it['iva_rate_percent']) ?></td>
            <td class="av3-pad-x4 av3-pad-y3 av3-text-right"><?= money((int)$it['cost_final']) ?></td>
            <td class="av3-pad-x4 av3-pad-y3 av3-text-right"><?= money((int)$it['price']) ?></td>
            <td class="av3-pad-x4 av3-pad-y3 av3-text-right av3-weight-strong">
              <?= money((int)$it['capital_final']) ?>
              <div class="av3-type-caption av3-text-slate-400">Neto: <?= money((int)$it['capital_neto']) ?></div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
