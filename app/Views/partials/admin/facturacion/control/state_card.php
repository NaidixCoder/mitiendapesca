<article class="fisc-card">
  <header class="fisc-card-head">
    <div>
      <h2>Estado del periodo</h2>
      <p>Contexto y proximo paso.</p>
    </div>
  </header>
  <div class="fisc-card-body">
    <div class="fisc-state-grid">
      <div class="fisc-state-item">
        <p class="fisc-state-k">Periodo</p>
        <p class="fisc-state-v"><?= e($currentPeriod) ?></p>
      </div>
      <div class="fisc-state-item">
        <p class="fisc-state-k">Estado</p>
        <p class="fisc-state-v"><?= e($periodState) ?></p>
      </div>
      <div class="fisc-state-item">
        <p class="fisc-state-k">Alertas</p>
        <p class="fisc-state-v"><?= e((string)$totalAlerts) ?></p>
      </div>
      <div class="fisc-state-item">
        <p class="fisc-state-k">Ultimo cierre</p>
        <p class="fisc-state-v"><?= e($lastClosure !== '' ? $lastClosure : 'Sin datos') ?></p>
      </div>
    </div>

    <div class="fisc-btn-row fisc-mt-3">
      <a class="btn btn-primary" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Iniciar cierre</a>
      <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Ver cierres</a>
    </div>
  </div>
</article>
