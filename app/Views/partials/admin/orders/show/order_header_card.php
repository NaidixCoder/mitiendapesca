<section class="av3-card" id="order-root"
  data-order-id="<?= (int)$orderView['id'] ?>"
  data-status-endpoint="<?= e((string)$orderView['status_endpoint']) ?>">
  <div class="av3-flex av3-flex-col av3-md-flex-row av3-md-items-center av3-md-justify-between av3-gap-3">
    <div class="av3-flow-1">
      <h3 class="av3-weight-strong">Pedido #<?= (int)$orderView['id'] ?></h3>
      <div class="av3-type-body av3-text-var-fg">
        Creado: <?= e((string)$orderView['created_short']) ?> · Cliente: <?= e((string)$orderView['customer_label']) ?>
      </div>
    </div>

    <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
      <span id="status-badge" class="<?= e((string)$orderView['badge_class']) ?>"><?= e($statusLabel) ?></span>

      <label for="order-status" class="sr-only">Estado</label>
      <input type="hidden" id="order-id" value="<?= (int)$orderView['id'] ?>">
      <select id="order-status" class="av3-border av3-radius-md av3-pad-x2 av3-pad-y1 av3-type-body" data-current="<?= e((string)$orderView['status']) ?>">
        <?php foreach ($opts as $opt): ?>
          <option value="<?= e((string)($opt['value'] ?? '')) ?>" <?= !empty($opt['selected']) ? 'selected' : '' ?>>
            <?= e((string)($opt['label'] ?? ($opt['value'] ?? ''))) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div id="order-status-debug" class="av3-space-top-2 av3-type-caption av3-text-slate-500 av3-hidden"></div>

      <?php if ($orderUnpaid): ?>
        <p id="shipping-unpaid-warn" class="av3-space-top-1 av3-type-caption av3-text-amber-700">Para pasar el envio a Despachado/En transito/Entregado, primero marca el pedido como pagado.</p>
      <?php else: ?>
        <p id="shipping-paid-tip" class="av3-space-top-1 av3-type-caption av3-text-var-fg">Tip: al despachar, tambien podes actualizar el estado del pedido a Enviado.</p>
      <?php endif; ?>

      <button type="button" id="order-status-apply" class="btn-primary" disabled>Aplicar</button>
      <div id="order-inline" class="av3-hidden av3-space-top-2 av3-width-full av3-border av3-radius-md av3-pad-x3 av3-pad-y2 av3-type-body"></div>

      <a class="btn-ghost" href="<?= e($auditUrl) ?>">Ver auditoria</a>
      <a class="btn-secondary" href="<?= e((string)$orderView['index_url']) ?>">Volver</a>
    </div>
  </div>

  <p class="av3-type-caption av3-text-var-fg av3-space-top-2">
    Tip: el boton <b>Aplicar</b> habilita cambios validos desde el estado actual.
  </p>
</section>
