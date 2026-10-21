<?php
$pageTitle = 'Envíos masivos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$campaigns = is_array($campaigns_prepared ?? null) ? $campaigns_prepared : [];
$links = is_array($links ?? null) ? $links : [];
$adminHeaderTitle = 'Envíos masivos';
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
 ['label'=>'Envíos'],
];
$adminHeaderActions = '<a class="av3-ui-btn av3-ui-btn--primary" href="'.e((string)($links['new_campaign'] ?? url('/admin/marketing/email/campanas/new'))).'">Nuevo envío</a>';
?>
<section class="admin-surface">
 <div class="av3-wrap av3-marketing-email" data-email-campaigns>
  <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <div class="av3-email-page-intro">
    <p>Campañas para enviar ofertas, novedades y contenido a tus suscriptores.</p>
    <details class="av3-email-help-inline"><summary>Antes de enviar</summary><p>Crear una campaña la guarda como borrador. Para que se envíe, hay que programarla desde su detalle.</p></details>
  </div>
  <section class="av3-email-panel" aria-labelledby="email-campaigns-title">
    <header class="av3-email-panel__header">
      <div><h2 id="email-campaigns-title">Campañas de email</h2><p><?= e((string)($range_label ?? '')) ?></p></div>
      <form class="av3-email-page-size" method="get" action="<?= url('/admin/marketing/email/campanas') ?>">
        <label for="email-campaigns-per">Por página</label><select id="email-campaigns-per" name="per" >
          <?php foreach ([25,50] as $n): ?><option value="<?= $n ?>"<?= (int)($per ?? 25)===$n ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
        </select>
        <button class="av3-ui-btn" type="submit">Aplicar</button>
      </form>
    </header>
    <?php if (!$campaigns): ?>
      <div class="av3-email-empty"><h3>Todavía no hay campañas de email</h3><p>Creá un borrador para preparar tu primer envío.</p><a class="av3-ui-btn av3-ui-btn--primary" href="<?= e((string)($links['new_campaign'] ?? url('/admin/marketing/email/campanas/new'))) ?>">Crear campaña</a></div>
    <?php else: ?>
      <div class="av3-email-table-wrap">
        <table class="av3-email-table av3-email-table--campaigns">
          <thead><tr><th scope="col">Campaña</th><th scope="col">Estado</th><th scope="col">Enviados</th><th scope="col">Aperturas</th><th scope="col">Clics</th><th scope="col">Compras</th><th scope="col">Ingresos</th><th scope="col" class="av3-email-right">Acciones</th></tr></thead>
          <tbody><?php foreach ($campaigns as $c): ?><tr>
            <td><a class="av3-email-record-name" href="<?= e((string)$c['show_url']) ?>"><?= e((string)$c['name']) ?></a><span class="av3-email-record-subtitle"><?= e((string)$c['subject']) ?></span><?php if ($c['scheduled_at'] !== ''): ?><span class="av3-email-record-caption">Programada: <?= e((string)$c['scheduled_at']) ?></span><?php endif; ?></td>
            <td><span class="av3-email-status av3-email-status--<?= e((string)$c['status_tone']) ?>"><?= e((string)$c['status_label']) ?></span></td>
            <td class="av3-email-num"><?= (int)$c['sent_count'] ?></td>
            <td class="av3-email-num"><?= (int)$c['open_count'] ?><small><?= e((string)$c['open_rate']) ?>%</small></td>
            <td class="av3-email-num"><?= (int)$c['click_count'] ?><small><?= e((string)$c['ctr']) ?>%</small></td>
            <td class="av3-email-num"><?= (int)$c['orders_count'] ?></td>
            <td class="av3-email-num"><?= e((string)$c['revenue_total_label']) ?></td>
            <td class="av3-email-right"><a class="av3-email-textlink" href="<?= e((string)$c['show_url']) ?>">Detalle</a> <a class="av3-email-textlink" href="<?= e((string)$c['edit_url']) ?>">Editar</a></td>
          </tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <div class="av3-email-mobile-list">
        <?php foreach ($campaigns as $c): ?><article class="av3-email-mobile-item">
          <div class="av3-email-mobile-item__top"><div><a class="av3-email-record-name" href="<?= e((string)$c['show_url']) ?>"><?= e((string)$c['name']) ?></a><span class="av3-email-record-subtitle"><?= e((string)$c['subject']) ?></span></div><span class="av3-email-status av3-email-status--<?= e((string)$c['status_tone']) ?>"><?= e((string)$c['status_label']) ?></span></div>
          <div class="av3-email-mobile-stats"><span><strong><?= (int)$c['sent_count'] ?></strong> enviados</span><span><strong><?= (int)$c['click_count'] ?></strong> clics</span><span><strong><?= (int)$c['orders_count'] ?></strong> compras</span></div>
          <?php if ($c['scheduled_at'] !== ''): ?><span class="av3-email-record-caption">Programada: <?= e((string)$c['scheduled_at']) ?></span><?php endif; ?>
          <div class="av3-email-mobile-actions"><a href="<?= e((string)$c['show_url']) ?>">Detalle</a><a href="<?= e((string)$c['edit_url']) ?>">Editar</a></div>
        </article><?php endforeach; ?>
      </div>
      <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_pagination.php'; ?>
    <?php endif; ?>
  </section>
 </div>
</section>
