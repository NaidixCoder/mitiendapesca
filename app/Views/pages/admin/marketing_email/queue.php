<?php
$pageTitle = 'Cola de envío';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$counts = is_array($counts ?? null) ? $counts : [];
$q = (string)($q ?? '');
$status = (string)($status ?? 'queued');
$statusOptions = is_array($status_options ?? null) ? $status_options : [];
$rows = is_array($rows_prepared ?? null) ? $rows_prepared : [];
$logs = is_array($logs_prepared ?? null) ? $logs_prepared : [];
$links = is_array($links ?? null) ? $links : [];
$adminHeaderTitle = 'Cola de envío';
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
 ['label'=>'Cola'],
];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e((string)($links['settings'] ?? url('/admin/settings?tab=email-marketing'))).'">Ajustes</a>';
?>
<section class="admin-surface">
 <div class="av3-wrap av3-marketing-email" data-email-queue>
  <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <?php if ($m=flash('error')): ?><div class="av3-alert error" role="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m=flash('ok')): ?><div class="av3-alert ok" role="status"><?= e($m) ?></div><?php endif; ?>
  <section class="av3-email-queue-overview" aria-label="Estado de la cola">
    <?php foreach ([['Por enviar','queued'],['En proceso','sending'],['Enviados','sent'],['Fallidos','failed'],['Cancelados','cancelled'],['Total','total']] as [$label,$key]): ?>
      <div class="av3-email-metric"><span><?= e($label) ?></span><strong><?= (int)($counts[$key] ?? 0) ?></strong></div>
    <?php endforeach; ?>
  </section>
  <section class="av3-email-panel av3-email-queue-board" aria-labelledby="email-queue-title">
    <header class="av3-email-panel__header">
      <div><h2 id="email-queue-title">Mensajes en la cola</h2><p><?= e((string)($range_label ?? '')) ?></p></div>
      <details class="av3-email-danger-action">
        <summary>Procesamiento manual</summary>
        <p>Esta operación programa mensajes automáticos y puede enviar correos reales a los destinatarios.</p>
        <form method="post" action="<?= e((string)($links['run'] ?? url('/admin/marketing/email/cola/procesar'))) ?>">
          <?= csrf_field() ?>
          <button class="av3-ui-btn" type="submit">Procesar pendientes ahora</button>
        </form>
      </details>
    </header>
    <form class="av3-email-filters-form" method="get" action="<?= e((string)($links['queue'] ?? url('/admin/marketing/email/cola'))) ?>">
      <div class="av3-email-field av3-email-field--search"><label for="email-queue-q">Buscar destinatario o asunto</label><input type="search" id="email-queue-q" name="q" value="<?= e($q) ?>" placeholder="Email, asunto o plantilla"></div>
      <div class="av3-email-field"><label for="email-queue-status">Estado</label><select id="email-queue-status" name="status">
        <?php foreach ($statusOptions as $option): ?><option value="<?= e((string)$option['value']) ?>"<?= !empty($option['selected']) ? ' selected' : '' ?>><?= e((string)$option['label']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="av3-email-field av3-email-field--per"><label for="email-queue-per">Por página</label><select id="email-queue-per" name="per">
        <?php foreach ([25,50] as $n): ?><option value="<?= $n ?>"<?= (int)($per ?? 25)===$n ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
      </select></div>
      <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Filtrar</button>
      <a class="av3-email-textlink" href="<?= e((string)($links['queue'] ?? url('/admin/marketing/email/cola'))) ?>">Limpiar</a>
    </form>
    <?php if (!$rows): ?>
      <div class="av3-email-empty"><h3>No hay mensajes con estos filtros</h3><p>Probá otro estado o una búsqueda diferente.</p></div>
    <?php else: ?>
      <div class="av3-email-table-wrap">
        <table class="av3-email-table">
          <thead><tr><th scope="col">Estado</th><th scope="col">Destinatario</th><th scope="col">Asunto</th><th scope="col">Fecha programada</th><th scope="col">Intentos</th></tr></thead>
          <tbody><?php foreach ($rows as $r): ?><tr>
            <td><span class="av3-email-status av3-email-status--<?= e((string)$r['status_tone']) ?>"><?= e((string)$r['status_label']) ?></span></td>
            <td class="av3-email-address"><?= e((string)$r['to_email']) ?></td><td><?= e((string)$r['subject']) ?></td><td><?= e((string)$r['scheduled_at']) ?></td><td><?= (int)$r['attempts'] ?></td>
          </tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <div class="av3-email-mobile-list">
        <?php foreach ($rows as $r): ?><article class="av3-email-mobile-item">
          <div class="av3-email-mobile-item__top"><strong><?= e((string)$r['to_email']) ?></strong><span class="av3-email-status av3-email-status--<?= e((string)$r['status_tone']) ?>"><?= e((string)$r['status_label']) ?></span></div>
          <div class="av3-email-mobile-item__meta"><?= e((string)$r['subject']) ?></div>
          <div class="av3-email-mobile-item__meta">Programado: <?= e((string)$r['scheduled_at']) ?> · Intentos: <?= (int)$r['attempts'] ?></div>
        </article><?php endforeach; ?>
      </div>
      <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_pagination.php'; ?>
    <?php endif; ?>
  </section>
  <section class="av3-email-panel" aria-labelledby="email-log-title">
    <header class="av3-email-panel__header"><div><h2 id="email-log-title">Historial reciente</h2><p>Últimos 50 intentos de envío y su resultado.</p></div></header>
    <?php if (!$logs): ?>
      <div class="av3-email-empty av3-email-empty--compact">Todavía no hay registros de envíos.</div>
    <?php else: ?>
      <div class="av3-email-table-wrap">
        <table class="av3-email-table">
          <thead><tr><th scope="col">Fecha</th><th scope="col">Resultado</th><th scope="col">Destinatario</th><th scope="col">Asunto</th><th scope="col">Motivo</th></tr></thead>
          <tbody><?php foreach ($logs as $l): ?><tr>
            <td><?= e((string)$l['created_at']) ?></td>
            <td><span class="av3-email-status av3-email-status--<?= e((string)$l['result_tone']) ?>"><?= e((string)$l['result_label']) ?></span></td>
            <td><?= e((string)$l['to_email']) ?></td><td><?= e((string)$l['subject']) ?></td><td><?= e((string)$l['error_text']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <div class="av3-email-mobile-list">
        <?php foreach ($logs as $l): ?><article class="av3-email-mobile-item">
          <div class="av3-email-mobile-item__top"><strong><?= e((string)$l['to_email']) ?></strong><span class="av3-email-status av3-email-status--<?= e((string)$l['result_tone']) ?>"><?= e((string)$l['result_label']) ?></span></div>
          <div class="av3-email-mobile-item__meta"><?= e((string)$l['subject']) ?></div>
          <div class="av3-email-mobile-item__meta"><?= e((string)$l['created_at']) ?></div>
          <?php if ($l['error_text'] !== ''): ?><p class="av3-email-log-error"><?= e((string)$l['error_text']) ?></p><?php endif; ?>
        </article><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
 </div>
</section>
