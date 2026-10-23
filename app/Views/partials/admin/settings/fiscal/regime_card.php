<section id="ajustes-regimen" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Regimen del vendedor</h2>
  <p class="av3-type-body av3-text-var-muted">Regimen actual: <strong><?= e($regimeLabel) ?></strong>. Puedes cambiarlo cuando sea necesario: los pedidos anteriores mantienen su registro (snapshot por pedido).</p>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Regimen</div>
      <select class="input" name="seller_tax_regime">
        <?php foreach ($regimeOptions as $opt): ?>
          <option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>><?= e((string)($opt['label'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="av3-type-caption av3-text-var-muted av3-space-top-1">Puedes cambiarlo cuando sea necesario: los pedidos anteriores mantienen su registro.</p>

      <?php if ($isNoFacturar): ?>
        <div class="av3-space-top-2 av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-3 av3-type-caption av3-text-amber-800"><b>Facturacion desactivada.</b> No se emitiran comprobantes (ni automatica ni manual). Puedes volver a activarlo cuando quieras.</div>
      <?php endif; ?>
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">CUIT (vendedor)</div>
      <input class="input" type="text" name="seller_cuit" value="<?= e((string)($fiscalPayload['seller_cuit'] ?? '')) ?>" placeholder="Ej: 20-12345678-9">
      <p class="av3-type-caption av3-text-var-muted av3-space-top-1">Opcional por ahora. Se usara en facturacion.</p>
    </label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Razon social / Nombre</div><input class="input" type="text" name="seller_business_name" value="<?= e((string)($fiscalPayload['seller_business_name'] ?? '')) ?>" placeholder="Ej: Mi Pesca & Aventura"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Domicilio fiscal</div><input class="input" type="text" name="seller_fiscal_address" value="<?= e((string)($fiscalPayload['seller_fiscal_address'] ?? '')) ?>" placeholder="Ej: Calle 123, Ciudad, Provincia"></label>
  </div>
</section>
