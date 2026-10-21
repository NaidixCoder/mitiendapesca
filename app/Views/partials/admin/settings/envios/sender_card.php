<section class="av3-card av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Remitente fijo (para Crear envío)</h2>
  <p class="av3-type-caption av3-text-var-muted">Estos datos se usan en <code>/shipping/import</code>. No se editan por pedido.</p>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Nombre y apellido</div><input class="av3-input" type="text" name="shipping_sender_name" value="<?= e((string)($cfg['shipping_sender_name'] ?? '')) ?>" placeholder="Ej: Mi Tienda Pesca"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Email</div><input class="av3-input" type="email" name="shipping_sender_email" value="<?= e((string)($cfg['shipping_sender_email'] ?? '')) ?>" placeholder="Ej: ventas@..."></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Teléfono</div><input class="av3-input" type="text" name="shipping_sender_phone" value="<?= e((string)($cfg['shipping_sender_phone'] ?? '')) ?>" placeholder="Ej: 341-..."></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Celular (opcional)</div><input class="av3-input" type="text" name="shipping_sender_cellphone" value="<?= e((string)($cfg['shipping_sender_cellphone'] ?? '')) ?>" placeholder="Ej: 341-..."></label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra (cm)</div><input type="number" min="0" name="shipping_packaging_extra_cm" value="<?= (int)($cfg['shipping_packaging_extra_cm'] ?? 0) ?>" class="av3-input"><p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Se suma a alto/ancho/largo del paquete calculado (útil para caja/bolsa).</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra peso (%)</div><input type="number" min="0" max="100" step="0.1" name="shipping_packaging_extra_weight_pct" value="<?= e((string)($cfg['shipping_packaging_extra_weight_pct'] ?? 0)) ?>" class="av3-input"><p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Porcentaje extra sobre el peso calculado (ej: 5 = +5%).</p></label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3">
    <label class="av3-block av3-md-col-span-2"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Calle</div><input class="av3-input" type="text" name="shipping_sender_street" value="<?= e((string)($cfg['shipping_sender_street'] ?? '')) ?>" placeholder="Ej: Av. ..."></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Número</div><input class="av3-input" type="text" name="shipping_sender_number" value="<?= e((string)($cfg['shipping_sender_number'] ?? '')) ?>" placeholder="Ej: 1234"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">CP</div><input class="av3-input" type="text" name="shipping_sender_zip" value="<?= e((string)($cfg['shipping_sender_zip'] ?? '')) ?>" placeholder="Ej: 2000"></label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra (cm)</div><input type="number" min="0" name="shipping_packaging_extra_cm" value="<?= (int)($cfg['shipping_packaging_extra_cm'] ?? 0) ?>" class="av3-input"><p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Se suma a alto/ancho/largo del paquete calculado (útil para caja/bolsa).</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra peso (%)</div><input type="number" min="0" max="100" step="0.1" name="shipping_packaging_extra_weight_pct" value="<?= e((string)($cfg['shipping_packaging_extra_weight_pct'] ?? 0)) ?>" class="av3-input"><p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Porcentaje extra sobre el peso calculado (ej: 5 = +5%).</p></label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3">
    <label class="av3-block av3-md-col-span-2"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Ciudad</div><input class="av3-input" type="text" name="shipping_sender_city" value="<?= e((string)($cfg['shipping_sender_city'] ?? '')) ?>" placeholder="Ej: Rosario"></label>
    <label class="av3-block av3-md-col-span-2"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Provincia</div><input class="av3-input" type="text" name="shipping_sender_state" value="<?= e((string)($cfg['shipping_sender_state'] ?? '')) ?>" placeholder="Ej: Santa Fe"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Se mapea a <code>provinceCode</code> automáticamente.</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Piso (opcional)</div><input class="av3-input" type="text" name="shipping_sender_floor" value="<?= e((string)($cfg['shipping_sender_floor'] ?? '')) ?>"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Depto (opcional)</div><input class="av3-input" type="text" name="shipping_sender_apartment" value="<?= e((string)($cfg['shipping_sender_apartment'] ?? '')) ?>"></label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra (cm)</div><input type="number" min="0" name="shipping_packaging_extra_cm" value="<?= (int)($cfg['shipping_packaging_extra_cm'] ?? 0) ?>" class="av3-input"><p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Se suma a alto/ancho/largo del paquete calculado (útil para caja/bolsa).</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra peso (%)</div><input type="number" min="0" max="100" step="0.1" name="shipping_packaging_extra_weight_pct" value="<?= e((string)($cfg['shipping_packaging_extra_weight_pct'] ?? 0)) ?>" class="av3-input"><p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Porcentaje extra sobre el peso calculado (ej: 5 = +5%).</p></label>
  </div>
</section>
