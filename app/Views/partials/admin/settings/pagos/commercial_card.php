<section id="ajustes-condiciones" class="av3-ui-card av3-settings-detail__panel av3-flow-4">
  <div class="av3-flow-3">
    <div class="av3-weight-strong">Financiacion comercial</div>

    <label class="av3-flex av3-items-start av3-gap-3">
      <input
        type="checkbox"
        name="pricing_installments_enabled"
        value="1"
        <?= (($cfg['pricing_installments_enabled'] ?? '1') === '1') ? 'checked' : '' ?>
      >
      <span>
        <div class="av3-weight-medium">Mostrar cuotas en sitio</div>
        <div class="av3-type-body av3-text-var-muted">
          Usa el <b>precio lista</b> como base financiada y el <b>precio</b> como valor por transferencia.
        </div>
      </span>
    </label>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
      <label class="av3-block">
        <span class="av3-block av3-weight-medium av3-space-bottom-1">Cantidad de cuotas</span>
        <input class="input av3-width-full" type="number" name="pricing_installments_count" min="2" max="24" value="<?= e((string)($cfg['pricing_installments_count'] ?? '3')) ?>">
        <div class="av3-type-body av3-text-var-muted av3-space-top-1">Se usa para el texto comercial en cards y ficha.</div>
      </label>

      <label class="av3-block">
        <span class="av3-block av3-weight-medium av3-space-bottom-1">Tasa global de referencia (%)</span>
        <input class="input av3-width-full" type="text" inputmode="decimal" name="pricing_interest_rate_percent" value="<?= e((string)($cfg['pricing_interest_rate_percent'] ?? '0')) ?>" placeholder="Ej: 26.81">
        <div class="av3-type-body av3-text-var-muted av3-space-top-1">Referencia manual para recalcular precios financiados en admin cuando haga falta.</div>
      </label>

      <label class="av3-block">
        <span class="av3-block av3-weight-medium av3-space-bottom-1">Descuento tarjeta debito (%)</span>
        <input class="input av3-width-full" type="text" inputmode="decimal" name="pricing_debit_discount_percent" value="<?= e((string)($cfg['pricing_debit_discount_percent'] ?? '0')) ?>" placeholder="Ej: 10">
        <div class="av3-type-body av3-text-var-muted av3-space-top-1">Se aplica sobre el <b>precio lista</b> para calcular el precio de debito en checkout.</div>
      </label>
    </div>
  </div>

  <div class="av3-pt-4 av3-border-top av3-border-slate-200 av3-flow-3">
    <div class="av3-weight-strong">Transferencia bancaria</div>

    <label class="av3-block av3-max-w-md">
      <span class="av3-block av3-weight-medium av3-space-bottom-1">Ventana de reserva de stock (horas)</span>
      <input class="input av3-width-full" type="number" name="payments_transfer_reservation_hours" min="1" max="720" value="<?= e((string)($cfg['payments_transfer_reservation_hours'] ?? '24')) ?>">
      <div class="av3-type-body av3-text-var-muted av3-space-top-1">
        Default: 24 horas. Pasado ese plazo, los pedidos pendientes por transferencia vencen y el stock se libera automáticamente.
      </div>
    </label>
  </div>
</section>
