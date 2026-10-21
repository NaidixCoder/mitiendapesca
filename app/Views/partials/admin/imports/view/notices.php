<?php
$job = is_array($show['job'] ?? null) ? $show['job'] : null;
$rollbackFileOk = !empty($show['rollback_file_ok']);
$rollbackVersion = $show['rollback_version'] ?? null;
$revertStatus = (string)($show['revert_status'] ?? ($job ? (string)($job['revert_status'] ?? 'not_reverted') : 'not_reverted'));
$lastRevertibleId = (int)($show['last_revertible_id'] ?? 0);
$revertSummary = is_array($show['revert_summary'] ?? null) ? $show['revert_summary'] : null;
?>
<?php if ($job && !$rollbackFileOk && $rollbackVersion !== null && !in_array($revertStatus, ['reverted', 'reverted_with_warnings'], true)): ?>
  <div class="av3-alert error">
    <div class="av3-weight-strong">Rollback no compatible</div>
    <div class="av3-type-body av3-text-slate-700">Este run tiene rollback v<?= (int)$rollbackVersion ?> (legacy). Para poder revertir con Revert v2, volve a ejecutar la importacion para regenerar el punto de restauracion.</div>
  </div>
<?php endif; ?>

<?php if ($job && $rollbackFileOk && $revertStatus === 'not_reverted' && (int)($job['id'] ?? 0) !== $lastRevertibleId && $lastRevertibleId > 0): ?>
  <div class="av3-alert warn">
    <div class="av3-weight-strong">Revertir no disponible</div>
    <div class="av3-type-body av3-text-slate-700 av3-space-top-1">Por seguridad, solo se puede revertir el <b>ultimo</b> import revertible (LIFO).</div>
    <div class="av3-space-top-2 av3-type-body av3-text-slate-700">Ultimo revertible: <a class="btn-link" href="<?= url('/admin/imports/show?id='.$lastRevertibleId) ?>">#<?= (int)$lastRevertibleId ?></a></div>
  </div>
<?php endif; ?>

<?php if ($job && in_array($revertStatus, ['blocked_not_last', 'blocked_conflict'], true)): ?>
  <?php
  $sum = $revertSummary;
  $title = ($revertStatus === 'blocked_conflict') ? 'Revert bloqueado por conflicto' : 'Revert no disponible';
  $msg = ($revertStatus === 'blocked_conflict')
    ? 'Hubo ediciones manuales posteriores al import. Por seguridad, este run no se puede revertir automaticamente.'
    : 'Solo se puede revertir el ultimo import revertible (LIFO).';
  ?>
  <div class="av3-alert error">
    <div class="av3-weight-strong"><?= e($title) ?></div>
    <div class="av3-type-body av3-text-slate-700 av3-space-top-1"><?= e($msg) ?></div>
    <?php if ($revertStatus === 'blocked_conflict' && is_array($sum) && !empty($sum['conflicts'])): ?>
      <div class="av3-space-top-3 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-3">
        <div class="av3-type-body av3-weight-strong">Conflictos detectados</div>
        <ul class="av3-space-top-2 av3-type-body av3-text-slate-700 list-disc av3-pad-left-5 av3-flow-1">
          <?php foreach ((array)$sum['conflicts'] as $c): ?>
            <li><?= e((string)($c['entity'] ?? '')) ?> #<?= (int)($c['id'] ?? 0) ?> - actualizado: <?= e((string)($c['updated_at'] ?? '')) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php elseif ($revertStatus === 'blocked_not_last' && is_array($sum) && !empty($sum['last_revertible_job_id'])): ?>
      <div class="av3-space-top-2 av3-type-body av3-text-slate-700">Ultimo revertible: <a class="btn-link" href="<?= url('/admin/imports/show?id='.(int)$sum['last_revertible_job_id']) ?>">#<?= (int)$sum['last_revertible_job_id'] ?></a></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($job && in_array($revertStatus, ['reverted', 'reverted_with_warnings'], true)): ?>
  <?php $revAt = $job['reverted_at_utc'] ?? null; $sum = $revertSummary; ?>
  <div class="av3-alert ok">
    <div class="av3-weight-strong">
      Importacion revertida<?= $revAt ? ' - ' . e((string)$revAt) . ' UTC' : '' ?>
      <?= $revertStatus === 'reverted_with_warnings' ? ' - con advertencias' : '' ?>
    </div>
    <?php if (is_array($sum)): ?>
      <div class="av3-space-top-2 av3-type-body av3-text-slate-700">
        <span class="av3-weight-strong">Resumen:</span>
        Restaurados: <?= (int)($sum['restored'] ?? 0) ?> - Eliminados: <?= (int)($sum['deleted'] ?? 0) ?>
        <?php if (!empty($sum['warnings']) && is_array($sum['warnings'])): ?>
          - Advertencias: <?= count($sum['warnings']) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($m = flash('ok')): ?><div class="av3-alert ok"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="av3-alert error"><?= e($m) ?></div><?php endif; ?>
