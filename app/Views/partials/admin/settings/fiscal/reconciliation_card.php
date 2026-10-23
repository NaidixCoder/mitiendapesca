<section id="ajustes-conciliacion" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Conciliacion (M3)</h2>
  <p class="av3-type-body av3-text-var-muted">Estos estados se usan en <b>Reportes -> Conciliacion</b> para decidir que pedidos <b>deberian</b> tener comprobante y cuales se consideran <b>no facturables</b>. Es solo para alertas y listas operativas (no modifica pedidos ni emite).</p>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
    <div class="av3-radius-xl av3-border av3-border-slate-200 av3-pad-3">
      <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Estados facturables</div>
      <p class="av3-type-caption av3-text-var-muted av3-space-bottom-2">Si un pedido esta en alguno de estos estados y no tiene Factura emitida, aparecera en "Pedidos sin factura".</p>
      <div class="av3-grid av3-grid-cols-1 av3-gap-2">
        <?php foreach ($statusOptions as $opt): ?>
          <label class="av3-flex av3-items-center av3-gap-2 av3-type-body"><input type="checkbox" name="arca_facturable_statuses[]" value="<?= e((string)($opt['key'] ?? '')) ?>" <?= !empty($opt['facturable_checked']) ? 'checked' : '' ?>><span><?= e((string)($opt['label'] ?? '')) ?></span></label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="av3-radius-xl av3-border av3-border-slate-200 av3-pad-3">
      <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Estados NO facturables</div>
      <p class="av3-type-caption av3-text-var-muted av3-space-bottom-2">Si un pedido esta en alguno de estos estados y aun asi tiene Factura emitida, aparecera como alerta "No facturable con factura".</p>
      <div class="av3-grid av3-grid-cols-1 av3-gap-2">
        <?php foreach ($statusOptions as $opt): ?>
          <label class="av3-flex av3-items-center av3-gap-2 av3-type-body"><input type="checkbox" name="arca_non_facturable_statuses[]" value="<?= e((string)($opt['key'] ?? '')) ?>" <?= !empty($opt['non_facturable_checked']) ? 'checked' : '' ?>><span><?= e((string)($opt['label'] ?? '')) ?></span></label>
        <?php endforeach; ?>
      </div>
      <p class="av3-type-caption av3-text-var-muted av3-space-top-2">Si marcas un estado en ambas listas, prevalece como <b>facturable</b>.</p>
    </div>
  </div>
</section>
