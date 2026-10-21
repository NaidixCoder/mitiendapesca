<?php
/** @var bool $is_edit */
/** @var int $item_id */
/** @var array<string,mixed> $values */
/** @var array<string,string> $errors */
/** @var array<int,array<string,mixed>> $supplier_options */
/** @var array<int,array<string,mixed>> $cbte_options */
/** @var array<int,array<string,mixed>> $status_options */
?>
<div class="card av3-pad-4">
  <form method="post" action="<?= e(base_url('admin/facturacion/compras/save')) ?>" class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
    <?= csrf_field() ?>
    <?php if ($is_edit): ?>
      <input type="hidden" name="id" value="<?= (int)$item_id ?>">
    <?php endif; ?>

    <div class="av3-md-col-span-2">
      <label class="label">Proveedor</label>
      <select class="input <?= isset($errors['supplier_id']) ? 'av3-border-red-500' : '' ?>" name="supplier_id" required>
        <option value="">Seleccionar...</option>
        <?php foreach ($supplier_options as $supplier): ?>
          <option value="<?= (int)$supplier['id'] ?>" <?= !empty($supplier['selected']) ? 'selected' : '' ?>>
            <?= e((string)$supplier['label']) ?> (<?= e((string)$supplier['cuit']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['supplier_id'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['supplier_id']) ?></p><?php endif; ?>
    </div>

    <div>
      <label class="label">Fecha de emision</label>
      <input class="input <?= isset($errors['issued_at']) ? 'av3-border-red-500' : '' ?>" type="date" name="issued_at" value="<?= e((string)$values['issued_at']) ?>" required>
      <?php if (isset($errors['issued_at'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['issued_at']) ?></p><?php endif; ?>
    </div>

    <div>
      <label class="label">Tipo de comprobante (AFIP)</label>
      <select class="input <?= isset($errors['cbte_tipo']) ? 'av3-border-red-500' : '' ?>" name="cbte_tipo" required>
        <?php foreach ($cbte_options as $option): ?>
          <option value="<?= (int)$option['value'] ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['cbte_tipo'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['cbte_tipo']) ?></p><?php endif; ?>
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Se detecta automaticamente si es factura o nota de credito (para el signo en reportes).</p>
    </div>

    <div>
      <label class="label">Punto de venta</label>
      <input class="input" type="number" name="pto_vta" min="0" value="<?= e((string)$values['pto_vta']) ?>">
    </div>

    <div>
      <label class="label">Numero</label>
      <input class="input <?= isset($errors['cbte_nro']) ? 'av3-border-red-500' : '' ?>" type="number" name="cbte_nro" min="1" value="<?= e((string)$values['cbte_nro']) ?>" required>
      <?php if (isset($errors['cbte_nro'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['cbte_nro']) ?></p><?php endif; ?>
    </div>

    <div>
      <label class="label">Neto (ARS)</label>
      <input class="input <?= isset($errors['imp_neto']) ? 'av3-border-red-500' : '' ?>" type="text" name="imp_neto" inputmode="decimal" placeholder="150000" value="<?= e((string)$values['imp_neto']) ?>">
      <?php if (isset($errors['imp_neto'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['imp_neto']) ?></p><?php endif; ?>
    </div>

    <div>
      <label class="label">IVA (ARS)</label>
      <input class="input <?= isset($errors['imp_iva']) ? 'av3-border-red-500' : '' ?>" type="text" name="imp_iva" inputmode="decimal" placeholder="31500" value="<?= e((string)$values['imp_iva']) ?>">
      <?php if (isset($errors['imp_iva'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['imp_iva']) ?></p><?php endif; ?>
    </div>

    <div>
      <label class="label">Percepcion IVA (ARS)</label>
      <input class="input <?= isset($errors['perc_iva']) ? 'av3-border-red-500' : '' ?>" type="text" name="perc_iva" inputmode="decimal" placeholder="0" value="<?= e((string)$values['perc_iva']) ?>">
      <?php if (isset($errors['perc_iva'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['perc_iva']) ?></p><?php endif; ?>
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Opcional. Se suma al total de la compra.</p>
    </div>

    <div>
      <label class="label">Percepcion IIBB (ARS)</label>
      <input class="input <?= isset($errors['perc_iibb']) ? 'av3-border-red-500' : '' ?>" type="text" name="perc_iibb" inputmode="decimal" placeholder="0" value="<?= e((string)$values['perc_iibb']) ?>">
      <?php if (isset($errors['perc_iibb'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['perc_iibb']) ?></p><?php endif; ?>
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Opcional. Se suma al total de la compra.</p>
    </div>

    <div>
      <label class="label">Retencion Ganancias (ARS)</label>
      <input class="input <?= isset($errors['ret_ganancias']) ? 'av3-border-red-500' : '' ?>" type="text" name="ret_ganancias" inputmode="decimal" placeholder="0" value="<?= e((string)$values['ret_ganancias']) ?>">
      <?php if (isset($errors['ret_ganancias'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['ret_ganancias']) ?></p><?php endif; ?>
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Opcional. Resta al total de la compra.</p>
    </div>

    <div>
      <label class="label">Total (ARS)</label>
      <input class="input <?= isset($errors['imp_total']) ? 'av3-border-red-500' : '' ?>" type="text" name="imp_total" inputmode="decimal" placeholder="181500" value="<?= e((string)$values['imp_total']) ?>">
      <?php if (isset($errors['imp_total'])): ?><p class="av3-type-body av3-text-red-600 av3-space-top-1"><?= e((string)$errors['imp_total']) ?></p><?php endif; ?>
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Si lo dejas vacio o en 0, se calcula como: Neto + IVA + percepciones - retenciones.</p>
    </div>

    <div>
      <label class="label">Estado</label>
      <select class="input" name="status">
        <?php foreach ($status_options as $option): ?>
          <option value="<?= e((string)$option['value']) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="av3-md-col-span-2">
      <label class="label">Notas</label>
      <textarea class="input" name="notes" rows="3" placeholder="Opcional"><?= e((string)$values['notes']) ?></textarea>
    </div>

    <div class="av3-md-col-span-2 av3-flex av3-gap-2 av3-justify-end av3-space-top-2">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/compras')) ?>">Cancelar</a>
    </div>
  </form>
</div>
