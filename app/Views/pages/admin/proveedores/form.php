<?php
$formView = is_array($proveedores_form_view ?? null) ? $proveedores_form_view : [];
extract($formView, EXTR_SKIP);
section('partials/admin/subnav');
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/proveedores.css')) ?>">
<?php
$adminHeaderTitle = !empty($is_edit) ? 'Editar proveedor' : 'Nuevo proveedor';
$adminHeaderDescription = 'Datos de contacto, situación fiscal y estado comercial.';
$adminHeaderActions = '<a class="av3-ui-btn" href="' . e(url('/admin/proveedores')) . '">Volver</a>';
$adminHeaderBreadcrumbs = [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Proveedores','href'=>url('/admin/proveedores')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<div class="av3-proveedores av3-ui-page">
<section class="av3-ui-card">
<div class="av3-ui-card-body">
<form class="av3-proveedores__form" method="post" action="<?= e(url('/admin/proveedores/save')) ?>">
  <?= csrf_field() ?>
  <?php if (!empty($is_edit)): ?><input type="hidden" name="id" value="<?= (int)($item_id ?? 0) ?>"><?php endif; ?>
  <div class="av3-ui-field">
    <label class="av3-ui-field-label" for="supplier-name">Nombre *</label>
    <input id="supplier-name" class="av3-ui-control" name="nombre" maxlength="180" required value="<?= e((string)($fields['nombre'] ?? '')) ?>" autocomplete="organization" aria-invalid="<?= isset($errors['nombre']) ? 'true' : 'false' ?>">
    <?php if (isset($errors['nombre'])): ?><div class="av3-proveedores__error" role="alert"><?= e((string)$errors['nombre']) ?></div><?php endif; ?>
  </div>
  <div class="av3-ui-field">
    <label class="av3-ui-field-label" for="supplier-cuit">CUIT *</label>
    <input id="supplier-cuit" class="av3-ui-control" name="cuit" required inputmode="numeric" maxlength="20" value="<?= e((string)($fields['cuit'] ?? '')) ?>" placeholder="11 dígitos" aria-invalid="<?= isset($errors['cuit']) ? 'true' : 'false' ?>">
    <?php if (isset($errors['cuit'])): ?><div class="av3-proveedores__error" role="alert"><?= e((string)$errors['cuit']) ?></div><?php endif; ?>
  </div>
  <div class="av3-proveedores__form-grid">
    <div class="av3-ui-field">
      <label class="av3-ui-field-label" for="supplier-email">Email</label>
      <input id="supplier-email" class="av3-ui-control" name="email" type="email" maxlength="190" value="<?= e((string)($fields['email'] ?? '')) ?>">
      <?php if (isset($errors['email'])): ?><div class="av3-proveedores__error" role="alert"><?= e((string)$errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="av3-ui-field">
      <label class="av3-ui-field-label" for="supplier-phone">Teléfono</label>
      <input id="supplier-phone" class="av3-ui-control" name="telefono" type="tel" maxlength="64" value="<?= e((string)($fields['telefono'] ?? '')) ?>">
      <?php if (isset($errors['telefono'])): ?><div class="av3-proveedores__error" role="alert"><?= e((string)$errors['telefono']) ?></div><?php endif; ?>
    </div>
  </div>
  <div class="av3-proveedores__form-grid">
    <div class="av3-ui-field">
      <label class="av3-ui-field-label" for="supplier-iva">Condición IVA</label>
      <select id="supplier-iva" class="av3-ui-control" name="condicion_iva">
        <?php foreach (($condicion_iva_options ?? []) as $opt): ?>
          <option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['condicion_iva'])): ?><div class="av3-proveedores__error" role="alert"><?= e((string)$errors['condicion_iva']) ?></div><?php endif; ?>
    </div>
    <div class="av3-ui-field">
      <label class="av3-ui-field-label" for="supplier-active">Estado</label>
      <select id="supplier-active" class="av3-ui-control" name="is_active">
        <?php foreach (($active_options ?? []) as $opt): ?>
          <option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['is_active'])): ?><div class="av3-proveedores__error" role="alert"><?= e((string)$errors['is_active']) ?></div><?php endif; ?>
    </div>
  </div>
  <div class="av3-ui-actions">
    <button class="av3-ui-btn av3-ui-btn--primary" type="submit"><?= !empty($is_edit) ? 'Guardar cambios' : 'Crear proveedor' ?></button>
    <a class="av3-ui-btn" href="<?= e(url('/admin/proveedores')) ?>">Cancelar</a>
  </div>
</form>
</div>
</section>
</div>
