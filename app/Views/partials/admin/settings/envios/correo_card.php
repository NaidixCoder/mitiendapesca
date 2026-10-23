<section id="ajustes-correo" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Correo Argentino</h2>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Activar integración</div>
      <label class="av3-inline-flex av3-items-center av3-gap-2">
        <input type="checkbox" name="correo_enabled" <?= $enabled?'checked':'' ?>>
        <span class="av3-type-body">Habilitar Correo Argentino</span>
      </label>
      <p class="av3-type-caption av3-text-var-muted av3-space-top-1">Si está desactivado, no se mostrarán acciones automáticas en pedidos.</p>
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Modo</div>
      <select class="input" name="correo_mode">
        <option value="test" <?= $mode==='test'?'selected':'' ?>>Test (apitest)</option>
        <option value="prod" <?= $mode==='prod'?'selected':'' ?>>Producción</option>
      </select>
      <p class="av3-type-caption av3-text-var-muted av3-space-top-1">Cambia la base URL automáticamente según el modo.</p>
    </label>
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

  <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-3 av3-type-body av3-text-slate-700">
    <div class="av3-weight-medium">Seguridad</div>
    <ul class="list-disc av3-pad-left-5 av3-space-top-1 av3-type-caption av3-text-slate-600 av3-flow-1">
      <li>Recomendado: guardar secretos en <code>.env</code> (no en base de datos).</li>
      <li>El botón “Probar conexión” nunca registra secretos en logs/auditoría.</li>
    </ul>
  </div>

  <label class="av3-block">
    <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Usar credenciales desde <code>.env</code> (recomendado)</div>
    <label class="av3-inline-flex av3-items-center av3-gap-2">
      <input type="checkbox" name="correo_use_env" <?= $useEnv?'checked':'' ?>>
      <span class="av3-type-body">Usar variables de entorno</span>
    </label>
    <p class="av3-type-caption av3-text-var-muted av3-space-top-1">Si lo desmarcás, podrás guardar credenciales en DB (no recomendado).</p>
  </label>

  <?php if (!$useEnv): ?>
    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">API Username</div>
        <input class="input" type="text" name="correo_api_username" value="<?= e((string)($cfg['correo_api_username'] ?? '')) ?>">
      </label>
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">API Password</div>
        <input class="input" type="password" name="correo_api_password" value="<?= e((string)($cfg['correo_api_password'] ?? '')) ?>">
      </label>
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Email usuario (MiCorreo)</div>
        <input class="input" type="email" name="correo_user_email" value="<?= e((string)($cfg['correo_user_email'] ?? '')) ?>">
      </label>
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Password usuario (MiCorreo)</div>
        <input class="input" type="password" name="correo_user_password" value="<?= e((string)($cfg['correo_user_password'] ?? '')) ?>">
      </label>
    </div>
  <?php else: ?>
    <div class="av3-radius-card av3-border av3-border-emerald-200 av3-bg-emerald-50 av3-pad-3 av3-type-caption av3-text-emerald-800">
      <b>Variables esperadas en .env</b>
      <div class="av3-space-top-1 av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-1">
        <code>CORREO_API_USERNAME=...</code>
        <code>CORREO_API_PASSWORD=...</code>
        <code>CORREO_USER_EMAIL=...</code>
        <code>CORREO_USER_PASSWORD=...</code>
      </div>
    </div>
  <?php endif; ?>

  <div class="av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-3">
    <div class="av3-type-body av3-weight-medium">QA / Desarrollo</div>
    <p class="av3-type-caption av3-text-var-muted av3-space-top-1">
      En <b>Test</b>, Correo puede no devolver <code>customerId</code> (validate=406) y bloquear <code>/shipping/import</code>.
      Este modo permite <b>simular</b> la creación del envío para probar el flujo Admin/DB sin depender de terceros.
    </p>

    <label class="av3-inline-flex av3-items-center av3-gap-2 av3-space-top-2">
      <input type="checkbox" name="correo_mock_import" <?= $mockImport?'checked':'' ?> <?= $mode!=='test'?'disabled':'' ?>>
      <span class="av3-type-body">Simular “Crear envío” (solo modo Test)</span>
    </label>

    <?php if ($mode !== 'test'): ?>
      <p class="av3-type-caption av3-text-slate-500 av3-space-top-1">Disponible solo en <b>Test</b>. En Producción siempre se intenta crear el envío real.</p>
    <?php endif; ?>
  </div>
</section>
