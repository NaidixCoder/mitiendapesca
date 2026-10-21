<?php
$formView = is_array($proveedores_form_view ?? null) ? $proveedores_form_view : [];
extract($formView, EXTR_SKIP);
section('partials/admin/subnav');
?>

<div class="card">
  <?php
ob_start(); ?><?= !empty($is_edit) ? 'Editar proveedor' : 'Nuevo proveedor' ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Completa los datos basicos. El CUIT debe ser valido (11 digitos).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <a class="btn btn-ghost" href="<?= e(url('/admin/proveedores')) ?>">Volver</a>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Proveedores','href'=>url('/admin/proveedores')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

  <div class="card-body">
    <form method="post" action="<?= e(url('/admin/proveedores/save')) ?>" class="av3-grid av3-gap-4" style="max-width: 720px;">
      <?= csrf_field() ?>
      <?php if (!empty($is_edit)): ?>
        <input type="hidden" name="id" value="<?= (int)($item_id ?? 0) ?>">
      <?php endif; ?>

      <div>
        <label class="label">Nombre *</label>
        <input class="input av3-width-full <?= isset($errors['nombre']) ? 'input-error' : '' ?>" name="nombre" value="<?= e((string)($fields['nombre'] ?? '')) ?>" placeholder="Ej: Distribuidora Pesca SRL">
        <?php if (isset($errors['nombre'])): ?><div class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['nombre']) ?></div><?php endif; ?>
      </div>

      <div>
        <label class="label">CUIT *</label>
        <input class="input av3-width-full <?= isset($errors['cuit']) ? 'input-error' : '' ?>" name="cuit" value="<?= e((string)($fields['cuit'] ?? '')) ?>" placeholder="11 digitos">
        <?php if (isset($errors['cuit'])): ?><div class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['cuit']) ?></div><?php endif; ?>
      </div>

      <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
        <div>
          <label class="label">Email</label>
          <input class="input av3-width-full <?= isset($errors['email']) ? 'input-error' : '' ?>" name="email" value="<?= e((string)($fields['email'] ?? '')) ?>" placeholder="email@proveedor.com">
          <?php if (isset($errors['email'])): ?><div class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['email']) ?></div><?php endif; ?>
        </div>
        <div>
          <label class="label">Telefono</label>
          <input class="input av3-width-full" name="telefono" value="<?= e((string)($fields['telefono'] ?? '')) ?>" placeholder="+54 ...">
        </div>
      </div>

      <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
        <div>
          <label class="label">Condicion IVA</label>
          <select class="input av3-width-full" name="condicion_iva">
            <?php foreach (($condicion_iva_options ?? []) as $opt):
              $opt = is_array($opt) ? $opt : [];
              $value = (string)($opt['value'] ?? '');
              $label = (string)($opt['label'] ?? '');
              $selected = !empty($opt['selected']);
            ?>
              <option value="<?= e($value) ?>" <?= $selected ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="label">Estado</label>
          <select class="input av3-width-full" name="is_active">
            <?php foreach (($active_options ?? []) as $opt):
              $opt = is_array($opt) ? $opt : [];
              $value = (string)($opt['value'] ?? '');
              $label = (string)($opt['label'] ?? '');
              $selected = !empty($opt['selected']);
            ?>
              <option value="<?= e($value) ?>" <?= $selected ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="av3-flex av3-gap-2">
        <button class="btn btn-primary" type="submit"><?= !empty($is_edit) ? 'Guardar cambios' : 'Crear proveedor' ?></button>
        <a class="btn" href="<?= e(url('/admin/proveedores')) ?>">Cancelar</a>
      </div>
    </form>
  </div>
</div>

