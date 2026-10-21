<?php /** @var ?array $job */ ?>
<section >
  <h3 >Ultimo import</h3>
  <?php if (empty($job)): ?>
    <p >Sin imports todavia.</p>
  <?php else: ?>
    <p >
      <?= e((string)($job['job_type'] ?? '-')) ?>
      <span class="<?= e((string)($job['status_badge_class'] ?? 'badge')) ?>"><?= e((string)($job['status'] ?? 'unknown')) ?></span>
    </p>
    <p ><?= e((string)($job['file_name'] ?? '')) ?></p>
    <p >
      OK: <?= (int)($job['ok_rows'] ?? 0) ?> - Errores: <?= (int)($job['err_rows'] ?? 0) ?> - Total: <?= (int)($job['total_rows'] ?? 0) ?>
    </p>
    <div >
      <div>
        <div >Inicio</div>
        <div><?= e((string)($job['created_at_label'] ?? '-')) ?></div>
      </div>
      <div>
        <div >Fin</div>
        <div><?= e((string)($job['finished_at_label'] ?? '-')) ?></div>
      </div>
    </div>
    <a  href="<?= url('/admin/imports') ?>">Ver historial</a>
  <?php endif; ?>
</section>
