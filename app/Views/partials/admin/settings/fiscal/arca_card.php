<section id="ajustes-arca" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">ARCA (facturacion electronica)</h2>
  <p class="av3-type-body av3-text-var-muted">Esta configuracion habilita la emision de comprobantes por <strong>WSFEv1</strong>. Los secretos (certificado/clave y CUIT emisor) se configuran por <code>.env</code>.</p>

  <?php if ($isNoFacturar): ?>
    <div class="av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-3 av3-type-caption av3-text-amber-800"><b>Nota:</b> Como el regimen esta en <b>No facturar</b>, la emision automatica queda deshabilitada y no se mostraran acciones de emision manual.</div>
  <?php endif; ?>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Entorno</div><select class="input" name="arca_env"><option value="homologacion" <?= ((string)($fiscalPayload['arca_env'] ?? 'homologacion') === 'homologacion') ? 'selected' : '' ?>>Homologacion</option><option value="produccion" <?= ((string)($fiscalPayload['arca_env'] ?? 'homologacion') === 'produccion') ? 'selected' : '' ?>>Produccion</option></select><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Primero proba en homologacion. Cambia a produccion cuando tengas todo validado.</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Punto de venta</div><input class="input" type="number" min="1" name="arca_pto_vta" value="<?= e((string)($fiscalPayload['arca_pto_vta'] ?? '1')) ?>" placeholder="1"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Debe coincidir con el punto de venta habilitado en ARCA.</p></label>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">WS service</div><input class="input" type="text" name="arca_ws_service" value="<?= e((string)($fiscalPayload['arca_ws_service'] ?? 'wsfe')) ?>" placeholder="wsfe"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Normalmente es <code>wsfe</code>.</p></label>
    <label class="av3-flex av3-items-start av3-gap-3 av3-pt-7"><input type="checkbox" class="av3-space-top-1" name="arca_auto_emit" value="1" <?= $isNoFacturar ? 'disabled' : '' ?> <?= !empty($fiscalPayload['arca_auto_emit_checked']) ? 'checked' : '' ?>><div><div class="av3-type-body av3-weight-medium">Auto-emision de comprobantes</div><p class="av3-type-caption av3-text-var-muted">Recomendacion: dejarlo apagado al inicio y emitir manualmente desde el Admin para validar.</p></div></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Momento de emision automatica</div><select class="input" name="arca_auto_emit_trigger" <?= $isNoFacturar ? 'disabled' : '' ?>><option value="paid" <?= ((string)($fiscalPayload['arca_auto_emit_trigger'] ?? 'paid') === 'paid') ? 'selected' : '' ?>>Al confirmar el pago (recomendado)</option><option value="shipped" <?= ((string)($fiscalPayload['arca_auto_emit_trigger'] ?? 'paid') === 'shipped') ? 'selected' : '' ?>>Al despachar (cuando el pedido pasa a Enviado)</option></select><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Si eliges "Al despachar", igual requiere que el pedido este <b>pagado</b>.</p></label>
  </div>

  <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-3 av3-type-caption av3-text-slate-700">
    <div class="av3-weight-strong av3-space-bottom-1">Variables .env necesarias (no se guardan en DB)</div>
    <ul class="list-disc av3-pad-left-5 av3-flow-1">
      <li><code>ARCA_CUIT</code> (CUIT del emisor)</li>
      <li><code>ARCA_CERT_PATH</code> (ruta al certificado .crt)</li>
      <li><code>ARCA_KEY_PATH</code> (ruta a la clave privada .key)</li>
      <li><code>ARCA_KEY_PASS</code> (si la clave tiene passphrase)</li>
    </ul>
  </div>

  <div class="av3-flex av3-items-center av3-gap-2"><a class="btn-secondary" target="_blank" href="<?= e(url('/admin/arca/test')) ?>">Probar conexion ARCA</a><p class="av3-type-caption av3-text-var-muted">Abre un diagnostico: WSAA + WSFE (no emite comprobantes).</p></div>
</section>
