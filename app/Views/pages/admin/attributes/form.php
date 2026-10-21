<?php
$af = is_array($attributes_form_view ?? null) ? $attributes_form_view : [];
$id = (int)($af['id'] ?? (int)($row['id'] ?? 0));
$title = (string)($af['title'] ?? ($id > 0 ? 'Editar atributo' : 'Nuevo atributo'));
$pageTitle = $title;

require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('attributes.form', ['id' => $id]);
$name = (string)($af['name'] ?? (string)($row['name'] ?? ''));
$code = (string)($af['code'] ?? (string)($row['code'] ?? ''));
$slug = (string)($af['slug'] ?? (string)($row['slug'] ?? ''));
$type = (string)($af['type'] ?? (string)($row['type'] ?? 'select'));
$unit = (string)($af['unit'] ?? (string)($row['unit'] ?? ''));
$types = is_array($af['types'] ?? null) ? $af['types'] : [['v' => 'select', 'l' => 'Seleccion']];
$values = is_array($af['values'] ?? null) ? $af['values'] : (is_array($values ?? null) ? $values : []);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php
ob_start(); ?><?= e($title) ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Defini una opcion reutilizable para variantes reales y luego carga sus valores. Los filtros livianos del catalogo se gestionan desde Tags.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
      
      <a class="btn-secondary" href="<?= url('/admin/attributes') ?>">Volver</a>
    <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Atributos','href'=>url('/admin/attributes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <?php if ($m = flash('ok')): ?>
      <div class="av3-alert ok"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
      <div class="av3-alert error"><?= e($m) ?></div>
    <?php endif; ?>

    <section class="av3-card">
      <form class="av3-grid av3-md-grid-cols-3 av3-gap-3" method="post" action="<?= url('/admin/attributes/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="av3-md-col-span-1">
          <label class="label">Nombre</label>
          <input class="input" name="name" value="<?= e($name) ?>" placeholder="Color">
        </div>
        <div class="av3-md-col-span-1">
          <label class="label">Code</label>
          <input class="input" name="code" value="<?= e($code) ?>" placeholder="color">
          <div class="help">Clave tecnica estable. Si lo dejas vacio, puede quedar nulo.</div>
        </div>
        <div class="av3-md-col-span-1">
          <label class="label">Slug</label>
          <input class="input" name="slug" value="<?= e($slug) ?>" placeholder="color">
          <div class="help">Si lo dejas vacio, se genera desde el nombre.</div>
        </div>
        <div class="av3-md-col-span-1">
          <label class="label">Tipo</label>
          <select class="input" name="type">
            <?php foreach ($types as $t): ?>
              <option value="<?= e((string)($t['v'] ?? 'select')) ?>" <?= ($type === (string)($t['v'] ?? 'select')) ? 'selected' : '' ?>><?= e((string)($t['l'] ?? 'Seleccion')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="av3-md-col-span-1">
          <label class="label">Unidad</label>
          <input class="input" name="unit" value="<?= e($unit) ?>" placeholder="cm">
        </div>

        <div class="av3-md-col-span-3">
          <div class="help">Este modulo define opciones para variantes reales. Si una clasificacion solo sirve para filtrar catalogo, gestionarla desde <strong>Tags</strong>.</div>
        </div>

        <div class="av3-md-col-span-3 av3-flex av3-items-center av3-justify-end av3-gap-2 av3-pt-1">
          <a class="btn-secondary" href="<?= url('/admin/attributes') ?>">Cancelar</a>
          <button class="btn-primary">Guardar</button>
        </div>
      </form>
    </section>

    <?php if ($id): ?>
    <section class="av3-card">
      <header class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-3">
        <div>
          <h2 class="av3-type-base av3-weight-strong">Valores</h2>
          <p class="av3-type-body av3-text-var-muted">Estos valores se usan en productos con variantes y en las combinaciones que luego completas con SKU, precio y stock.</p>
        </div>
      </header>

      <form class="av3-flex av3-flex-col av3-md-flex-row av3-gap-2 av3-md-items-end" method="post" action="<?= url('/admin/attributes/value/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="attribute_id" value="<?= (int)$id ?>">
        <input type="hidden" name="value_id" value="0">

        <div class="av3-flex-1">
          <label class="label">Valor</label>
          <input class="input" name="value" placeholder="Rojo">
        </div>
        <div class="av3-width-full av3-md-w-44">
          <label class="label">Orden</label>
          <input class="input" type="number" name="sort" value="0">
        </div>
        <div class="av3-width-full av3-md-size-w-40">
          <label class="label">Activo</label>
          <select class="input" name="is_active">
            <option value="1">Si</option>
            <option value="0">No</option>
          </select>
        </div>
        <div class="av3-flex av3-gap-2">
          <button class="btn-primary">Agregar</button>
        </div>
      </form>

      <div class="av3-space-top-4 av3-overflow-x-auto">
        <table class="av3-table av3-width-full av3-type-body">
          <thead>
            <tr>
              <th>Valor</th>
              <th class="av3-size-w-24 av3-text-right">Orden</th>
              <th class="av3-w-56">Uso</th>
              <th class="av3-size-w-24">Activo</th>
              <th class="av3-size-w-40">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($values as $v): ?>
            <tr class="av3-border-top av3-border-var-line-soft">
              <td class="av3-pad-y2 av3-pad-right-3 av3-weight-medium"><?= e((string)($v['value'] ?? '')) ?></td>
              <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= (int)($v['sort'] ?? 0) ?></td>
              <td class="av3-pad-y2 av3-pad-right-3 av3-type-caption av3-text-var-muted">
                <span>Productos: <?= (int)($v['selected_in_products'] ?? 0) ?></span>
                <span class="av3-mx-1">-</span>
                <span>Variantes: <?= (int)($v['used_in_variants'] ?? 0) ?></span>
                <span class="av3-mx-1">-</span>
                <span>Sets: <?= (int)($v['used_in_sets'] ?? 0) ?></span>
              </td>
              <td class="av3-pad-y2 av3-pad-right-3"><?= !empty($v['is_active']) ? '<span class="badge badge--ok">Si</span>' : '<span class="badge">No</span>' ?></td>
              <td class="av3-pad-y2 av3-pad-right-3">
                <form class="av3-inline" method="post" action="<?= url('/admin/attributes/value/delete') ?>" onsubmit="return confirm('Eliminar valor?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="value_id" value="<?= (int)($v['id'] ?? 0) ?>">
                  <input type="hidden" name="attribute_id" value="<?= (int)$id ?>">
                  <button class="btn-danger <?= !empty($v['in_use_hard']) ? 'av3-opacity-50 cursor-not-allowed' : '' ?>" type="submit" <?= !empty($v['in_use_hard']) ? 'disabled title="'.e((string)($v['delete_title'] ?? '')).'"' : '' ?>>Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($values)): ?>
            <tr><td colspan="5" class="av3-pad-y6 av3-text-var-muted">Todavia no hay valores.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="av3-space-top-3 av3-type-body av3-text-var-muted">
        Tip: despues asigna este atributo al producto desde <strong>Productos</strong> -> editar -> pestaña <strong>Variantes</strong>.
      </div>
    </section>
    <?php endif; ?>

  </div>
</section>
