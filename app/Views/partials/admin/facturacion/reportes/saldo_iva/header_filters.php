<?php
/** @var array<string,mixed> $filters */
/** @var array<int,array<string,mixed>> $env_options */
/** @var string $apply_url */
/** @var string $reset_url */
/** @var string $export_url */
?>
<?php
ob_start(); ?>Saldo IVA (mensual)<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>IVA debito (ventas) - IVA credito (compras). Periodo por fecha de emision (issued_at).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

<section class="av3-fiscal-panel card av3-pad-4 av3-space-bottom-4">
  <header class="av3-fiscal-panel-heading"><h2>Filtrar resultados</h2><p>Ajustá los criterios para consultar registros.</p></header><form method="get" action="<?= e($apply_url) ?>" class="av3-grid av3-grid-cols-1 av3-md-grid-cols-12 av3-gap-3 av3-items-end">
    <div class="av3-md-col-span-3">
      <label class="label">Desde (mes)</label>
      <input class="input" type="month" name="from_month" value="<?= e((string)($filters['from_month'] ?? '')) ?>">
    </div>
    <div class="av3-md-col-span-3">
      <label class="label">Hasta (mes)</label>
      <input class="input" type="month" name="to_month" value="<?= e((string)($filters['to_month'] ?? '')) ?>">
    </div>
    <div class="av3-md-col-span-3">
      <label class="label">Entorno (ventas)</label>
      <select class="input" name="arca_env">
        <?php foreach ($env_options as $option): ?>
          <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="av3-type-caption av3-text-slate-500 av3-space-top-1">Compras manuales no tienen entorno.</p>
    </div>
    <div class="av3-md-col-span-3 av3-flex av3-gap-2 av3-justify-end">
      <button class="btn btn-primary" type="submit">Aplicar</button>
      <a class="btn btn-soft" href="<?= e($reset_url) ?>">Limpiar</a>
      <a class="btn btn-soft" href="<?= e($export_url) ?>">CSV</a>
    </div>
  </form>

  <div class="av3-type-caption av3-text-slate-600 av3-space-top-3">
    Rango: <strong><?= e((string)($filters['from_date'] ?? '')) ?></strong> -> <strong><?= e((string)($filters['to_date'] ?? '')) ?></strong>
  </div>
</section>
