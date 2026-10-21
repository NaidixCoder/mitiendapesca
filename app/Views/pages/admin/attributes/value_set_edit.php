<?php
/** @var int $set_id */
/** @var string $set_name */
/** @var string $csrf_token */
/** @var array<int,array<string,mixed>> $category_options */
/** @var array<int,array<string,mixed>> $brand_options */
/** @var array<int,array<string,mixed>> $attribute_options */
/** @var array<int,array<string,mixed>> $groups_prepared */
?>
<div class="av3-flow-4">
  <div class="av3-flex av3-items-start av3-justify-between av3-gap-3">
    <div>
      <div class="av3-type-body av3-weight-strong">Editar plantilla</div>
      <div class="av3-type-caption av3-text-var-admin-muted">Se guardan como <strong>Categoria + Marca</strong>. Ajusta nombre, categoria/marca y los valores incluidos.</div>
    </div>
  </div>

  <form data-role="set-edit-form" class="av3-flow-4">
    <input type="hidden" name="id" value="<?= (int)$set_id ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">

    <div class="av3-grid av3-md-grid-cols-2 av3-gap-3">
      <div>
        <label class="label">Nombre</label>
        <input class="input" type="text" name="name" maxlength="120" value="<?= e($set_name) ?>" required>
      </div>
      <div class="av3-grid av3-grid-cols-2 av3-gap-3">
        <div>
          <label class="label">Categoria</label>
          <select class="input" name="category_id" required>
            <option value="0">Seleccionar...</option>
            <?php foreach ($category_options as $option): ?>
              <option value="<?= (int)$option['id'] ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="label">Marca</label>
          <select class="input" name="brand_id" required>
            <option value="0">Seleccionar...</option>
            <?php foreach ($brand_options as $option): ?>
              <option value="<?= (int)$option['id'] ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="av3-radius-xl av3-border av3-border-var-admin-border av3-bg--color-var---admin-bg av3-pad-4">
      <div class="av3-flex av3-flex-wrap av3-items-end av3-justify-between av3-gap-3">
        <div>
          <div class="av3-type-body av3-weight-strong">Atributos y valores</div>
          <div class="av3-type-caption av3-text-var-admin-muted">Marca los valores que queres incluir en esta plantilla.</div>
        </div>
        <div class="av3-flex av3-items-end av3-gap-2">
          <div>
            <label class="label">Agregar atributo</label>
            <select class="input" name="add_attribute_id" data-role="set-add-attr-select">
              <option value="0">Seleccionar...</option>
              <?php foreach ($attribute_options as $option): ?>
                <option value="<?= (int)$option['id'] ?>"><?= e((string)$option['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="button" class="btn-secondary" data-role="set-add-attr">Agregar</button>
        </div>
      </div>

      <div class="av3-space-top-4 av3-flow-4 max-h-[420px] av3-overflow-auto">
        <?php if (empty($groups_prepared)): ?>
          <div class="av3-type-body av3-text-var-admin-muted">No hay valores en esta plantilla todavia.</div>
        <?php else: ?>
          <?php foreach ($groups_prepared as $group): ?>
            <div class="av3-radius-card av3-border av3-border-var-admin-border av3-surface-white av3-pad-3">
              <div class="av3-type-body av3-weight-strong"><?= e((string)$group['attribute_name']) ?></div>
              <div class="av3-space-top-2 av3-grid av3-sm-grid-cols-2 av3-md-grid-cols-3 av3-gap-2">
                <?php foreach ((array)($group['values'] ?? []) as $value): ?>
                  <label class="av3-inline-flex av3-items-center av3-gap-2 av3-radius-control av3-border av3-border-var-admin-border av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-caption">
                    <input type="checkbox" name="values[<?= (int)$group['attribute_id'] ?>][]" value="<?= (int)$value['id'] ?>" <?= !empty($value['checked']) ? 'checked' : '' ?>>
                    <span><?= e((string)$value['value']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>

              <div class="av3-space-top-3 av3-flex av3-flex-wrap av3-items-end av3-gap-2">
                <div class="av3-flex-1 av3-min-w--220px">
                  <label class="label">Agregar valores (separados por coma)</label>
                  <input class="input" type="text" placeholder="Ej: LOR, PAR, REH" data-role="set-add-values-input" data-attr-id="<?= (int)$group['attribute_id'] ?>">
                </div>
                <button type="button" class="btn-secondary" data-role="set-add-values" data-attr-id="<?= (int)$group['attribute_id'] ?>">Agregar</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="av3-flex av3-items-center av3-justify-end av3-gap-2">
      <button type="button" class="btn-secondary" data-role="set-modal-close-2">Cancelar</button>
      <button type="submit" class="btn-primary" data-role="set-save">Guardar cambios</button>
    </div>
  </form>
</div>
