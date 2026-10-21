<article class="fisc-card">
  <header class="fisc-card-head">
    <div>
      <h2>Accion recomendada</h2>
      <p>Resolve pendientes antes de cerrar el periodo.</p>
    </div>
    <span class="fisc-pill">
      <span class="fisc-dot <?= e($periodPillDotClass) ?>"></span>
      <?= e($periodPillLabel) ?>
    </span>
  </header>
  <div class="fisc-card-body">
    <?php if ($totalAlerts > 0): ?>
      <div class="fisc-alert-box">
        <div class="fisc-alert-icon">!</div>
        <div>
          <h3 class="fisc-alert-title">Tenes pendientes que pueden bloquear el cierre.</h3>
          <ul class="fisc-alert-list">
            <li>Compras sin validar: <strong><?= e((string)$cLoaded) ?></strong></li>
            <li>Compras sin adjunto: <strong><?= e((string)$cMissing) ?></strong></li>
            <li>Meses con movimientos sin cierre: <strong><?= e((string)$cUnclosed) ?></strong></li>
          </ul>
          <div class="fisc-alert-actions fisc-btn-row">
            <a class="btn btn-primary" href="<?= e(base_url('admin/facturacion/alertas')) ?>">Ver alertas</a>
            <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Ir a cierres</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="fisc-btn-row">
        <span class="fisc-pill"><span class="fisc-dot is-ok"></span>No hay pendientes criticos para el cierre.</span>
        <a class="btn btn-primary" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Ir a cierres</a>
      </div>
    <?php endif; ?>
  </div>
</article>
