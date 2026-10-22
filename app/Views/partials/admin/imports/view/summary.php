<?php
$job = is_array($show['job'] ?? null) ? $show['job'] : [];
$statusKey = (string)($show['status_key'] ?? ($job['status'] ?? ''));
$statusLabel = ['finished' => 'Finalizada', 'ok' => 'Finalizada', 'failed' => 'No aplicada', 'dry-failed' => 'Validación fallida', 'processing' => 'Procesando', 'review_required' => 'Revisión requerida'][$statusKey] ?? ($statusKey ?: 'Sin estado');
$statusTone = in_array($statusKey, ['finished', 'ok'], true) ? '' : (in_array($statusKey, ['failed', 'dry-failed'], true) ? 'av3-ui-badge--danger' : 'av3-ui-badge--warning');
$totalRows = (int)($show['total_rows'] ?? ($job['total_rows'] ?? 0));
$okRows = (int)($show['ok_rows'] ?? ($job['ok_rows'] ?? 0));
$errRows = (int)($show['err_rows'] ?? ($job['err_rows'] ?? 0));
if (in_array($statusKey, ['finished', 'ok'], true) && $errRows > 0) {
    $statusLabel = 'Finalizada con errores';
    $statusTone = 'av3-ui-badge--warning';
}
$revertStatus = (string)($show['revert_status'] ?? ($job['revert_status'] ?? ''));
if (in_array($revertStatus, ['reverted', 'reverted_with_warnings'], true)) {
    $statusLabel = $revertStatus === 'reverted' ? 'Revertida' : 'Revertida con advertencias';
    $statusTone = $revertStatus === 'reverted' ? '' : 'av3-ui-badge--warning';
} elseif (in_array($revertStatus, ['blocked_conflict', 'blocked_not_last'], true)) {
    $statusLabel = 'Reversión bloqueada';
    $statusTone = 'av3-ui-badge--warning';
}
$actorName = (string)($show['actor_name'] ?? '');
?>
<section class="av3-ui-card av3-imports__block" aria-labelledby="imports-summary-heading">
  <header class="av3-imports__block-head"><h3 id="imports-summary-heading">Resumen de la importación</h3><span class="av3-ui-badge <?= e($statusTone) ?>"><?= e($statusLabel) ?></span></header>
  <div class="av3-imports__block-body">
    <dl class="av3-imports__summary-meta">
      <div><dt>Importación</dt><dd>#<?= (int)($job['id'] ?? 0) ?></dd></div>
      <div><dt>Archivo</dt><dd><?= e((string)($job['file_name'] ?? '—')) ?></dd></div>
      <div><dt>Tipo</dt><dd><?= e(match ((string)($job['job_type'] ?? '')) { 'products' => 'Productos y variantes', default => (string)($job['job_type'] ?? '—') }) ?></dd></div>
      <div><dt>Operador</dt><dd><?= e($actorName ?: 'Sin operador registrado en auditoría') ?></dd></div>
      <div><dt>Inicio</dt><dd><?= e(substr((string)($job['created_at'] ?? '—'), 0, 19)) ?></dd></div>
      <div><dt>Finalización</dt><dd><?= !empty($job['finished_at']) ? e(substr((string)$job['finished_at'], 0, 19)) : '—' ?></dd></div>
    </dl>
    <div class="av3-imports__meta-divider"></div>
    <dl class="av3-imports__metric-grid">
      <div class="av3-imports__metric"><dt>Filas procesadas</dt><dd><?= $totalRows ?></dd></div>
      <div class="av3-imports__metric"><dt><?= in_array((string)($job['status'] ?? ''), ['failed','dry-failed'], true) ? 'Filas válidas · no aplicadas' : 'OK reportadas' ?></dt><dd><?= $okRows ?></dd></div>
      <div class="av3-imports__metric"><dt>Con errores</dt><dd><?= $errRows ?></dd></div>
    </dl>
    <?php if (!empty($show['can_rollback'])): ?>
      <div class="av3-imports__revert-action">
        <p>Esta operación restaurará el punto de recuperación de la importación.</p>
        <form method="post" action="<?= e(url('/admin/imports/rollback')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)($job['id'] ?? 0) ?>">
          <button type="submit" class="av3-ui-btn av3-ui-btn--danger" data-confirm="¿Revertir esta importación? Se restaurarán los valores anteriores del punto de restauración.">Revertir importación</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
