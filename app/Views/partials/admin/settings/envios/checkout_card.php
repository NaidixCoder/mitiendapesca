<section class="av3-card av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Checkout (cotización)</h2>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Código postal de origen</div>
      <input type="text" name="shipping_origin_postal_code" value="<?= e((string)($cfg['shipping_origin_postal_code'] ?? '')) ?>" class="av3-input" placeholder="Ej: 2000">
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Es el CP desde donde despacha tu tienda. Necesario para cotizar en /rates.</p>
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Envío gratis desde (ARS)</div>
      <input type="text" name="shipping_free_min_amount" value="<?= e((string)($cfg['shipping_free_min_amount'] ?? '0')) ?>" class="av3-input" placeholder="Ej: 15000">
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Si el subtotal del carrito supera este monto, el costo de envío se fuerza a $0 (sin llamar /rates).</p>
    </label>
  </div>

  <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-3 av3-flow-3">
    <label class="av3-inline-flex av3-items-center av3-gap-2">
      <input type="checkbox" name="shipping_local_pickup_enabled" <?= ((string)($cfg['shipping_local_pickup_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
      <span class="av3-type-body av3-weight-medium">Habilitar “Retirar en el local”</span>
    </label>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Titulo</div>
        <input type="text" name="shipping_local_pickup_label" value="<?= e((string)($cfg['shipping_local_pickup_label'] ?? 'Retirar en el local')) ?>" class="av3-input" placeholder="Retirar en el local">
      </label>

      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Costo (ARS)</div>
        <input type="text" name="shipping_local_pickup_cost" value="<?= e((string)($cfg['shipping_local_pickup_cost'] ?? '0')) ?>" class="av3-input" placeholder="0">
        <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Por defecto dejalo en 0. Lo usual es ofrecerlo gratis.</p>
      </label>
    </div>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Direccion / referencia del local</div>
      <input type="text" name="shipping_local_pickup_address" value="<?= e((string)($cfg['shipping_local_pickup_address'] ?? '')) ?>" class="av3-input" placeholder="Ej: Boulevard Colon 1359 - Firmat, Santa Fe">
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Notas</div>
      <input type="text" name="shipping_local_pickup_notes" value="<?= e((string)($cfg['shipping_local_pickup_notes'] ?? '')) ?>" class="av3-input" placeholder="Ej: Retiro en horario comercial">
    </label>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Google Maps URL</div>
        <input type="text" name="shipping_local_pickup_maps_url" value="<?= e((string)($cfg['shipping_local_pickup_maps_url'] ?? '')) ?>" class="av3-input" placeholder="Pega la URL compartida del negocio en Google Maps">
      </label>

      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Google Place ID</div>
        <input type="text" name="shipping_local_pickup_place_id" value="<?= e((string)($cfg['shipping_local_pickup_place_id'] ?? '')) ?>" class="av3-input" placeholder="Opcional, más preciso que la URL">
      </label>
    </div>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Latitud del mapa</div>
        <input type="text" name="shipping_local_pickup_map_lat" value="<?= e((string)($cfg['shipping_local_pickup_map_lat'] ?? '')) ?>" class="av3-input" placeholder="-33.4580">
      </label>

      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Longitud del mapa</div>
        <input type="text" name="shipping_local_pickup_map_lng" value="<?= e((string)($cfg['shipping_local_pickup_map_lng'] ?? '')) ?>" class="av3-input" placeholder="-61.4830">
      </label>

      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Zoom inicial</div>
        <input type="number" min="1" max="21" name="shipping_local_pickup_map_zoom" value="<?= e((string)($cfg['shipping_local_pickup_map_zoom'] ?? '17')) ?>" class="av3-input" placeholder="17">
      </label>
    </div>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra (cm)</div>
      <input type="number" min="0" name="shipping_packaging_extra_cm" value="<?= (int)($cfg['shipping_packaging_extra_cm'] ?? 0) ?>" class="av3-input">
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Se suma a alto/ancho/largo del paquete calculado (útil para caja/bolsa).</p>
    </label>
    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Packaging extra peso (%)</div>
      <input type="number" min="0" max="100" step="0.1" name="shipping_packaging_extra_weight_pct" value="<?= e((string)($cfg['shipping_packaging_extra_weight_pct'] ?? 0)) ?>" class="av3-input">
      <p class="av3-type-caption av3-text-gray-500 av3-space-top-1">Porcentaje extra sobre el peso calculado (ej: 5 = +5%).</p>
    </label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Peso por defecto (g)</div><input type="number" min="1" name="shipping_default_weight" value="<?= (int)($cfg['shipping_default_weight'] ?? 1000) ?>" class="av3-input"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Alto (cm)</div><input type="number" min="1" name="shipping_default_height" value="<?= (int)($cfg['shipping_default_height'] ?? 10) ?>" class="av3-input"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Ancho (cm)</div><input type="number" min="1" name="shipping_default_width" value="<?= (int)($cfg['shipping_default_width'] ?? 10) ?>" class="av3-input"></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Largo (cm)</div><input type="number" min="1" name="shipping_default_length" value="<?= (int)($cfg['shipping_default_length'] ?? 10) ?>" class="av3-input"></label>
  </div>

  <p class="av3-type-caption av3-text-gray-500">Nota: en FIX519 vamos a soportar peso/medidas por producto/variante y reglas de empaquetado para máxima precisión.</p>
</section>
