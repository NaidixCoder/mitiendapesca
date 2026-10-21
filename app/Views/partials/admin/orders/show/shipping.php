<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h4 class="av3-weight-strong">Envio / Tracking</h4>
    <div class="av3-type-caption av3-text-slate-500">Correo Argentino (MiCorreo / PAQ.AR)</div>
  </header>

  <form id="shipping-form" method="post" action="<?= url('/admin/orders/shipping') ?>" class="av3-grid av3-gap-4 av3-md-grid-cols-2">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">

    <div>
      <label class="label">Estado de envio</label>
      <select name="shipping_status" class="input">
        <option value="preparando" <?= $shipStatus === 'preparando' ? 'selected' : '' ?>>Preparando</option>
        <option value="despachado" <?= $shipStatus === 'despachado' ? 'selected' : '' ?> <?= $orderUnpaid ? 'disabled' : '' ?>>Despachado</option>
      </select>
      <p class="help av3-space-top-1">Al pasar a <b>Despachado</b> se envia email automatico al cliente. La fecha de despacho se completa sola.</p>
    </div>

    <div>
      <label class="label">Estado segun Correo (tracking)</label>
      <div class="input av3-bg-slate-50" aria-readonly="true" id="ship-tracking-status">
        <?= e($trackingLabel !== '' ? $trackingLabel : ($trackingStatus !== '' ? $trackingStatus : '—')) ?>
      </div>
      <p class="help av3-space-top-1">Se actualiza con Actualizar tracking (Correo). No es editable.</p>
      <input type="hidden" name="shipped_at" value="<?= e($shipAt) ?>">
    </div>

    <div>
      <label class="label">Transportista</label>
      <select class="input" name="carrier" id="ship-carrier" data-carrier-key="<?= e($shipCarrierKey) ?>">
        <option value="correo_argentino" <?= $shipCarrierKey === 'correo_argentino' ? 'selected' : '' ?>>Correo Argentino</option>
        <option value="manual" <?= $shipCarrierKey === 'manual' ? 'selected' : '' ?>>Envio manual</option>
      </select>
      <p class="help av3-space-top-1">Por defecto usamos Correo Argentino. Si necesitas otro transportista sin API (o manual), elegi Envio manual.</p>
    </div>

    <div id="ship-service-wrap">
      <label class="label">Servicio / metodo</label>
      <input class="input" type="text" name="service" value="<?= e($shipService) ?>" placeholder="<?= e($servicePlaceholder) ?>" <?= $serviceReadonly ? 'readonly' : '' ?>>
      <p class="help av3-space-top-1"><?= e($serviceHelp) ?></p>
    </div>

    <div>
      <label class="label">Numero de seguimiento</label>
      <input class="input" type="text" name="tracking_number" value="<?= e($shipTn) ?>" placeholder="Ej: AB123456789AR" <?= $trackingReadonly ? 'readonly' : '' ?>>
      <p class="help av3-space-top-1"><?= e($trackingNumberHelp) ?></p>
    </div>

    <div id="ship-external-wrap">
      <label class="label">ShippingId (MiCorreo)</label>
      <input class="input" type="text" name="external_shipment_id" value="<?= e($shipExternalId) ?>" placeholder="Ej: 000500076393019A3G0C701" readonly>
      <p class="help av3-space-top-1">Lo devuelve /shipping/import. Se usa para tracking por API.</p>
    </div>

    <div id="ship-url-wrap">
      <label class="label">URL de seguimiento</label>
      <div class="av3-flex av3-gap-2">
        <input class="input av3-flex-1" type="url" name="tracking_url" value="<?= e($shipUrl) ?>" placeholder="https://..." <?= $trackingUrlReadonly ? 'readonly' : '' ?>>
        <a class="btn-secondary whitespace-nowrap" id="tracking-url-open" href="#" target="_blank" rel="noopener">Abrir</a>
      </div>
      <p class="help av3-space-top-1"><?= e($trackingUrlHelp) ?></p>
    </div>

    <div class="av3-md-col-span-2 av3-flex av3-items-center av3-justify-end av3-gap-2">
      <div class="av3-flex av3-flex-col av3-items-end" id="correo-actions">
        <button class="btn-secondary" type="button" id="correo-import-btn"
                data-endpoint="<?= e(url('/admin/orders/shipping/correo/import')) ?>"
                data-order-id="<?= (int)$orderView['id'] ?>"
                <?= $correoCanImport ? '' : 'disabled' ?>>
          Crear envio (Correo)
        </button>
        <?php if (!$correoCanImport && $correoBlocked !== ''): ?>
          <p class="help av3-space-top-1 av3-text-right"><?= e($correoBlocked) ?></p>
        <?php endif; ?>
      </div>

      <button class="btn-secondary" type="button" id="correo-track-btn"
              data-endpoint="<?= e(url('/admin/orders/shipping/correo/track')) ?>"
              data-order-id="<?= (int)$orderView['id'] ?>">
        Actualizar tracking (Correo)
      </button>

      <button class="btn-primary" type="button" id="ship-dispatch-btn" <?= $orderUnpaid ? 'disabled' : '' ?>>
        Marcar como despachado
      </button>

      <div id="ship-save-wrap">
        <button class="btn-primary" type="submit">Guardar envio</button>
      </div>
    </div>
  </form>
</section>
