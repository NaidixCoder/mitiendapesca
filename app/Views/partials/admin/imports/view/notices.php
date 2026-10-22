<?php
$job = is_array($show['job'] ?? null) ? $show['job'] : null;
$rollbackFileOk = !empty($show['rollback_file_ok']);
$rollbackVersion = $show['rollback_version'] ?? null;
$revertStatus = (string)($show['revert_status'] ?? ($job ? (string)($job['revert_status'] ?? 'not_reverted') : 'not_reverted'));
$lastRevertibleId = (int)($show['last_revertible_id'] ?? 0);
$revertSummary = is_array($show['revert_summary'] ?? null) ? $show['revert_summary'] : null;
?>
<?php if ($job && (string)($job['status'] ?? '') === 'review_required'): ?>
  <div class="av3-imports__notice av3-imports__notice--error" role="alert">
    <strong>Revisión manual obligatoria: resultado de commit incierto</strong>
    <p>No se pudo confirmar si MySQL aplicó los cambios. No revertir ni repetir automáticamente esta importación. Conservá el snapshot y solicitá una conciliación supervisada.</p>
  </div>
<?php endif; ?>
<?php if ($job && (string)($job['status'] ?? '') === 'failed'): ?>
  <div class="av3-imports__notice av3-imports__notice--error" role="alert">
    <strong>Importación no aplicada</strong>
    <p>La ejecución fue cancelada o revertida. No se confirmaron cambios de esta transacción.</p>
  </div>
<?php endif; ?>
<?php if ($job && !$rollbackFileOk && in_array((string)($job['status'] ?? ''), ['finished','ok'], true) && $revertStatus === 'not_reverted'): ?>
  <div class="av3-imports__notice av3-imports__notice--error" role="alert">
    <strong>Reversión no compatible</strong>
    <p><?= $rollbackVersion === null ? 'No existe un snapshot verificable para esta importación.' : ('El snapshot v' . (int)$rollbackVersion . ' no contiene todos los estados posteriores verificables.') ?> No se permite reversión automática; se requiere recuperación manual supervisada.</p>
  </div>
<?php endif; ?>

<?php if ($job && $rollbackFileOk && $revertStatus === 'not_reverted' && (int)($job['id'] ?? 0) !== $lastRevertibleId && $lastRevertibleId > 0): ?>
  <div class="av3-imports__notice av3-imports__notice--warning" role="status">
    <strong>Reversión no disponible por orden de importación</strong>
    <p>Por seguridad, solo puede revertirse la última importación elegible (LIFO).
      Última revertible: <a href="<?= e(url('/admin/imports/show?id=' . $lastRevertibleId)) ?>">#<?= $lastRevertibleId ?></a>.</p>
  </div>
<?php endif; ?>

<?php if ($job && in_array($revertStatus, ['blocked_not_last', 'blocked_conflict'], true)): ?>
  <div class="av3-imports__notice av3-imports__notice--error" role="alert">
    <strong><?= $revertStatus === 'blocked_conflict' ? (($revertSummary['blocked'] ?? '') === 'unverifiable_snapshot' ? 'Reversión sin estado verificable' : 'Reversión bloqueada por conflicto') : 'Reversión fuera de orden' ?></strong>
    <p><?= $revertStatus === 'blocked_conflict'
      ? (($revertSummary['blocked'] ?? '') === 'unverifiable_snapshot' ? 'El snapshot no es verificable. La reversión automática está bloqueada para proteger cambios posteriores.' : 'Se detectaron modificaciones posteriores o estados no verificables. No se modificó el catálogo.')
      : 'Solo se permite revertir la última importación elegible (LIFO).' ?></p>
    <?php if ($revertStatus === 'blocked_conflict' && $revertSummary && !empty($revertSummary['conflicts'])): ?>
      <ul>
        <?php foreach ((array)$revertSummary['conflicts'] as $conflict): ?>
          <li><?= e((string)($conflict['entity'] ?? 'Entidad')) ?> #<?= (int)($conflict['id'] ?? 0) ?> · <?= e(match ((string)($conflict['reason'] ?? '')) { 'state_changed' => 'Estado modificado después de importar', 'related_records_changed' => 'Se modificaron relaciones dependientes', 'missing_row' => 'Registro no encontrado', default => 'Postestado no verificable' }) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php elseif ($revertStatus === 'blocked_not_last' && $revertSummary && !empty($revertSummary['last_revertible_job_id'])): ?>
      <p>Última revertible: <a href="<?= e(url('/admin/imports/show?id=' . (int)$revertSummary['last_revertible_job_id'])) ?>">#<?= (int)$revertSummary['last_revertible_job_id'] ?></a></p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($job && in_array($revertStatus, ['reverted', 'reverted_with_warnings'], true)): ?>
  <div class="av3-imports__notice av3-imports__notice--<?= $revertStatus === 'reverted_with_warnings' ? 'warning' : 'success' ?>" role="status">
    <strong>Importación revertida<?= $revertStatus === 'reverted_with_warnings' ? ' con advertencias' : '' ?></strong>
    <?php if (!empty($job['reverted_at_utc'])): ?><p>Revertida el <?= e((string)$job['reverted_at_utc']) ?> UTC.</p><?php endif; ?>
    <?php if ($revertSummary): ?>
      <p>Registros restaurados: <?= (int)($revertSummary['restored'] ?? 0) ?> · Eliminados: <?= (int)($revertSummary['deleted'] ?? 0) ?>
        <?php if (!empty($revertSummary['warnings']) && is_array($revertSummary['warnings'])): ?> · Avisos: <?= count($revertSummary['warnings']) ?><?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php foreach (['ok' => 'success', 'error' => 'error', 'warn' => 'warning'] as $type => $tone): ?>
  <?php if ($message = flash($type)): ?>
    <div class="av3-imports__notice av3-imports__notice--<?= e($tone) ?>" role="<?= $tone === 'error' ? 'alert' : 'status' ?>"><?= e((string)$message) ?></div>
  <?php endif; ?>
<?php endforeach; ?>
