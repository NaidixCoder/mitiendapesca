<?php
/** Fragmento de edición AJAX; se renderiza dentro del diálogo de Atributos. */
?>
<div class="av3-attributes__set-detail">
  <div>
    <div class="av3-attributes__set-title">Editar plantilla</div>
    <p class="av3-attributes__set-meta">La combinación categoría + marca determina dónde se puede importar esta plantilla.</p>
  </div>
  <form data-role="set-edit-form" class="av3-attributes__set-form">
    <input type="hidden" name="id" value="<?= (int)$set_id ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
    <div class="av3-attributes__set-fields">
      <label class="av3-ui-field av3-attributes__field">
        <span class="av3-ui-field-label">Nombre de la plantilla</span>
        <input class="av3-ui-control av3-attributes__input" type="text" name="name" maxlength="120" value="<?= e($set_name) ?>" required>
      </label>
      <label class="av3-ui-field av3-attributes__field">
        <span class="av3-ui-field-label">Categoría</span>
        <select class="av3-ui-control av3-attributes__input" name="category_id" required>
          <option value="0">Seleccionar categoría</option>
          <?php foreach ($category_options as $option): ?>
            <option value="<?= (int)$option['id'] ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="av3-ui-field av3-attributes__field">
        <span class="av3-ui-field-label">Marca</span>
        <select class="av3-ui-control av3-attributes__input" name="brand_id" required>
          <option value="0">Seleccionar marca</option>
          <?php foreach ($brand_options as $option): ?>
            <option value="<?= (int)$option['id'] ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="av3-attributes__set-group">
      <h3>Atributos y valores</h3>
      <p class="av3-attributes__set-meta">Seleccioná los valores reutilizables que quieras conservar en la plantilla.</p>
      <div class="av3-attributes__set-toolbar av3-attributes__set-toolbar--top">
        <label class="av3-ui-field av3-attributes__field">
          <span class="av3-ui-field-label">Incorporar atributo</span>
          <select class="av3-ui-control av3-attributes__input" name="add_attribute_id" data-role="set-add-attr-select">
            <option value="0">Seleccionar atributo</option>
            <?php foreach ($attribute_options as $option): ?>
              <option value="<?= (int)$option['id'] ?>"><?= e((string)$option['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="av3-ui-btn" type="button" data-role="set-add-attr">Incorporar</button>
      </div>
    </div>
    <?php if (empty($groups_prepared)): ?>
      <div class="av3-attributes__notice">Esta plantilla todavía no tiene valores. Incorporá un atributo y seleccioná al menos un valor.</div>
    <?php endif; ?>
    <?php foreach ($groups_prepared as $group): ?>
      <section class="av3-attributes__set-group" aria-label="<?= e((string)$group['attribute_name']) ?>">
        <h3><?= e((string)$group['attribute_name']) ?></h3>
        <input type="hidden" name="values[<?= (int)$group['attribute_id'] ?>][]" value="0">
        <div class="av3-attributes__set-options">
          <?php foreach (($group['values'] ?? []) as $value): ?>
            <label class="av3-attributes__set-option">
              <input type="checkbox" name="values[<?= (int)$group['attribute_id'] ?>][]" value="<?= (int)$value['id'] ?>" <?= !empty($value['checked']) ? 'checked' : '' ?>>
              <span><?= e((string)$value['value']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="av3-attributes__set-toolbar av3-attributes__set-toolbar--bottom">
          <label class="av3-ui-field av3-attributes__field">
            <span class="av3-ui-field-label">Agregar valores al atributo</span>
            <input class="av3-ui-control av3-attributes__input" type="text" placeholder="Ej.: Rojo, Verde, Azul" data-role="set-add-values-input" data-attr-id="<?= (int)$group['attribute_id'] ?>">
          </label>
          <button class="av3-ui-btn" type="button" data-role="set-add-values" data-attr-id="<?= (int)$group['attribute_id'] ?>">Agregar valores</button>
        </div>
      </section>
    <?php endforeach; ?>
    <div class="av3-attributes__notice">Los valores nuevos se crean en el catálogo al pulsar «Agregar valores», aunque luego canceles la edición de la plantilla.</div>
    <p class="av3-attributes__set-error" data-role="set-form-error" role="alert" hidden></p>
    <div class="av3-ui-actions av3-attributes__set-form-actions">
      <button type="button" class="av3-ui-btn" data-role="set-modal-close-2">Cancelar</button>
      <button class="av3-ui-btn av3-ui-btn--primary" type="submit" data-role="set-save">Guardar cambios</button>
    </div>
  </form>
</div>
