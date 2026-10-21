<?php
$job = is_array($show['job'] ?? null) ? $show['job'] : [];
$statusBadge = (string)($show['status_badge'] ?? 'badge-pill badge-neutral');
$statusKey = (string)($show['status_key'] ?? ($job['status'] ?? ''));
$totalRows = (int)($show['total_rows'] ?? ($job['total_rows'] ?? 0));
$okRows = (int)($show['ok_rows'] ?? ($job['ok_rows'] ?? 0));
$errRows = (int)($show['err_rows'] ?? ($job['err_rows'] ?? 0));
$actorName = (string)($show['actor_name'] ?? '');
?>
<section class="av3-card">
  <div class="av3-grid av3-md-grid-cols-6 av3-gap-3">
    <div>
      <div class="av3-type-caption av3-text-var-muted">ID</div>
      <div class="av3-weight-strong">#<?= (int)($job['id'] ?? 0) ?></div>
    </div>
    <div>
      <div class="av3-type-caption av3-text-var-muted">Tipo</div>
      <div class="av3-weight-strong"><?= e((string)($job['job_type'] ?? '-')) ?></div>
    </div>
    <div>
      <div class="av3-type-caption av3-text-var-muted">Archivo</div>
      <div class="av3-weight-strong break-all"><?= e((string)($job['file_name'] ?? '-')) ?></div>
    </div>
    <div>
      <div class="av3-type-caption av3-text-var-muted">Estado</div>
      <div class="av3-weight-strong"><span class="<?= e($statusBadge) ?>"><?= e($statusKey ?: '-') ?></span></div>
    </div>
  </div>

  <div class="av3-space-top-4 av3-grid av3-md-grid-cols-3 av3-gap-3">
    <div class="av3-kpi"><div class="av3-kpi-label">Total</div><div class="av3-kpi-value"><?= $totalRows ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">OK</div><div class="av3-kpi-value"><?= $okRows ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Errores</div><div class="av3-kpi-value"><?= $errRows ?></div></div>
  </div>

  <div class="av3-space-top-3 av3-grid av3-md-grid-cols-3 av3-gap-3 av3-type-caption">
    <div><div class="av3-text-var-muted">Inicio</div><div><?= e(substr((string)($job['created_at'] ?? ''), 0, 19)) ?></div></div>
    <div><div class="av3-text-var-muted">Fin</div><div><?= !empty($job['finished_at']) ? e(substr((string)$job['finished_at'], 0, 19)) : '-' ?></div></div>
    <div><div class="av3-text-var-muted">Modificado por</div><div><?= e($actorName ?: '-') ?></div></div>
  </div>
</section>
