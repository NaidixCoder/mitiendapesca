<?php
$af = is_array($attributes_form_view ?? null) ? $attributes_form_view : [];
$id = (int)($af['id'] ?? ($row['id'] ?? 0));
$title = $id > 0 ? 'Editar atributo' : 'Nuevo atributo';
$pageTitle = $title;
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('attributes.form', ['id' => $id]);
$name = (string)($af['name'] ?? '');
$code = (string)($af['code'] ?? '');
$slug = (string)($af['slug'] ?? '');
$type = (string)($af['type'] ?? 'select');
$unit = (string)($af['unit'] ?? '');
$types = is_array($af['types'] ?? null) ? $af['types'] : [];
$values = is_array($af['values'] ?? null) ? $af['values'] : [];
$adminHeaderTitle = $title;
$adminHeaderDescription = 'Configuración de opciones reutilizables en el catálogo.';
$adminHeaderBreadcrumbs = $crumbs ?: [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Atributos','href'=>url('/admin/attributes')],['label'=>$title]];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e(url('/admin/attributes')).'">Volver a atributos</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-attributes av3-attributes--form">
    <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/flashes.php'; ?>
    <section class="av3-ui-card av3-attributes__panel" aria-labelledby="attr-config-title">
      <div class="av3-ui-card-header av3-attributes__panel-head">
        <div>
          <h2 id="attr-config-title">Datos del atributo</h2>
          <p>Nombre, identificadores y tipo de valor. La clave y el slug pueden ser utilizados por integraciones.</p>
        </div>
      </div>
      <form class="av3-attributes__form" method="post" action="<?= e(url('/admin/attributes/save')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="av3-attributes__form-grid">
          <label class="av3-ui-field av3-attributes__field av3-attributes__field--wide">
            <span class="av3-ui-field-label">Nombre <em>Obligatorio</em></span>
            <input class="av3-ui-control av3-attributes__input" name="name" maxlength="120" value="<?= e($name) ?>" required placeholder="Ej.: Color">
          </label>
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Código técnico</span>
            <input class="av3-ui-control av3-attributes__input" name="code" value="<?= e($code) ?>" placeholder="color">
            <small>Identificador estable; opcional.</small>
          </label>
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Slug</span>
            <input class="av3-ui-control av3-attributes__input" name="slug" value="<?= e($slug) ?>" placeholder="color">
            <small>Si está vacío, se genera desde el nombre.</small>
          </label>
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Tipo de dato</span>
            <select class="av3-ui-control av3-attributes__input" name="type">
              <?php foreach ($types as $t): ?>
              <option value="<?= e((string)($t['v'] ?? 'select')) ?>" <?= $type === (string)($t['v'] ?? 'select') ? 'selected' : '' ?>><?= e((string)($t['l'] ?? 'Selección')) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Unidad de medida</span>
            <input class="av3-ui-control av3-attributes__input" name="unit" value="<?= e($unit) ?>" placeholder="Ej.: cm, g, lb">
          </label>
        </div>
        <?php if ($id > 0): ?>
        <div class="av3-attributes__notice">Antes de cambiar el slug o el tipo de un atributo usado por productos, verificá sus dependencias: estos identificadores pueden intervenir en el catálogo y las variantes.</div>
        <?php endif; ?>
        <div class="av3-ui-actions av3-attributes__form-actions">
          <a class="av3-ui-btn" href="<?= e(url('/admin/attributes')) ?>">Cancelar</a>
          <button class="av3-ui-btn av3-ui-btn--primary" type="submit"><?= $id > 0 ? 'Guardar cambios' : 'Crear atributo' ?></button>
        </div>
      </form>
    </section>
    <?php if ($id > 0): ?>
    <section class="av3-ui-card av3-attributes__panel" aria-labelledby="attr-values-title">
      <div class="av3-ui-card-header av3-attributes__panel-head">
        <div>
          <h2 id="attr-values-title">Valores del atributo</h2>
          <p>Administrá el nombre y el orden. Los valores usados en productos, variantes o plantillas no se eliminan desde esta pantalla.</p>
        </div>
        <span class="av3-ui-badge av3-ui-badge--neutral av3-attributes__count"><?= count($values) ?> valores</span>
      </div>
      <form class="av3-attributes__value-create" method="post" action="<?= e(url('/admin/attributes/value/save')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="attribute_id" value="<?= $id ?>">
        <input type="hidden" name="value_id" value="0">
        <label class="av3-ui-field av3-attributes__field">
          <span class="av3-ui-field-label">Nuevo valor</span>
          <input class="av3-ui-control av3-attributes__input" name="value" required placeholder="Ej.: Rojo">
        </label>
        <label class="av3-ui-field av3-attributes__field av3-attributes__field--sort">
          <span class="av3-ui-field-label">Orden</span>
          <input class="av3-ui-control av3-attributes__input" type="number" name="sort" value="0" step="1">
        </label>
        <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Agregar valor</button>
      </form>
      <div class="av3-attributes__table-wrap">
        <table class="av3-ui-table av3-attributes__table av3-attributes__table--values">
          <thead><tr><th scope="col">Valor</th><th scope="col">Orden</th><th scope="col">Uso actual</th><th scope="col" class="av3-attributes__actions-col">Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($values as $v):
            $vid = (int)($v['id'] ?? 0);
            $fid = 'av3-attr-value-'.$vid;
            $usage = (int)($v['selected_in_products'] ?? 0) + (int)($v['used_in_variants'] ?? 0) + (int)($v['used_in_sets'] ?? 0);
          ?>
          <tr>
            <td data-label="Valor">
              <form id="<?= $fid ?>" method="post" action="<?= e(url('/admin/attributes/value/save')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="attribute_id" value="<?= $id ?>">
                <input type="hidden" name="value_id" value="<?= $vid ?>">
                <input class="av3-ui-control av3-attributes__input av3-attributes__input--compact" name="value" value="<?= e((string)($v['value'] ?? '')) ?>" aria-label="Nombre del valor <?= e((string)($v['value'] ?? '')) ?>" required>
              </form>
            </td>
            <td data-label="Orden"><input class="av3-ui-control av3-attributes__input av3-attributes__input--compact av3-attributes__input--sort" type="number" name="sort" step="1" form="<?= $fid ?>" value="<?= (int)($v['sort'] ?? 0) ?>" aria-label="Orden de <?= e((string)($v['value'] ?? '')) ?>"></td>
            <td class="av3-attributes__cell-secondary" data-label="Uso">
              <div class="av3-attributes__usage">
                <span>Productos: <?= (int)($v['selected_in_products'] ?? 0) ?></span>
                <span>Variantes: <?= (int)($v['used_in_variants'] ?? 0) ?></span>
                <span>Plantillas: <?= (int)($v['used_in_sets'] ?? 0) ?></span>
              </div>
            </td>
            <td class="av3-attributes__cell-actions">
              <div class="av3-ui-actions av3-attributes__row-actions">
                <button class="av3-ui-btn av3-ui-btn--small" type="submit" form="<?= $fid ?>">Guardar</button>
                <form method="post" action="<?= e(url('/admin/attributes/value/delete')) ?>" data-confirm-value-delete>
                  <?= csrf_field() ?>
                  <input type="hidden" name="attribute_id" value="<?= $id ?>">
                  <input type="hidden" name="value_id" value="<?= $vid ?>">
                  <button class="av3-ui-btn av3-ui-btn--small av3-ui-btn--danger" type="submit" <?= $usage > 0 ? 'disabled title="Este valor tiene relaciones activas"' : '' ?>>Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($values)): ?>
          <tr class="av3-attributes__empty-row"><td colspan="4"><strong>Todavía no hay valores</strong><span>Agregá el primero desde el formulario superior.</span></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>
  </div>
</section>
