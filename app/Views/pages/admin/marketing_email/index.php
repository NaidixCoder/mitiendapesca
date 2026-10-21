<?php
$pageTitle = 'Email marketing';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$stats = is_array($stats ?? null) ? $stats : [];
$queueCounts = is_array($queueCounts ?? null) ? $queueCounts : [];
$adminHeaderTitle = 'Email marketing';
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing'],
];
$adminHeaderActions = '<a class="av3-ui-btn av3-ui-btn--primary" href="'.e(url('/admin/marketing/email/campanas/new')).'">Nuevo envío</a>';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-marketing-email" data-email-summary>
    <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
    <div class="av3-email-summary-grid">
      <section class="av3-email-panel" aria-labelledby="email-subscribers-title">
        <header class="av3-email-panel__header">
          <div><h2 id="email-subscribers-title">Suscriptores</h2><p>Estado de tu lista de contactos.</p></div>
          <a href="<?= url('/admin/marketing/email/suscriptores') ?>">Ver lista</a>
        </header>
        <div class="av3-email-metrics">
          <?php foreach ([
            ['Total','total'],['Suscriptos','subscribed'],['Por confirmar','pending'],['Bajas','unsubscribed']
          ] as [$label,$field]): ?>
            <div class="av3-email-metric"><span><?= e($label) ?></span><strong><?= (int)($stats[$field] ?? 0) ?></strong></div>
          <?php endforeach; ?>
        </div>
      </section>
      <section class="av3-email-panel" aria-labelledby="email-queue-title">
        <header class="av3-email-panel__header">
          <div><h2 id="email-queue-title">Cola de envío</h2><p>Procesamiento y resultados acumulados.</p></div>
          <a href="<?= url('/admin/marketing/email/cola') ?>">Ver cola</a>
        </header>
        <div class="av3-email-metrics">
          <?php foreach ([
            ['Por enviar','queued'],['Enviados','sent'],['Fallidos','failed'],['Total','total']
          ] as [$label,$field]): ?>
            <div class="av3-email-metric"><span><?= e($label) ?></span><strong><?= (int)($queueCounts[$field] ?? 0) ?></strong></div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
    <section class="av3-email-panel av3-email-quick" aria-labelledby="email-actions-title">
      <header class="av3-email-panel__header"><div><h2 id="email-actions-title">Herramientas</h2><p>Accedé a las principales tareas de email marketing.</p></div></header>
      <div class="av3-email-quick__links">
        <a href="<?= url('/admin/marketing/email/automatizaciones') ?>"><strong>Automatizaciones</strong><span>Bienvenida, carrito y postcompra</span></a>
        <a href="<?= url('/admin/marketing/email/campanas') ?>"><strong>Envíos masivos</strong><span>Campañas y resultados</span></a>
        <a href="<?= url('/admin/marketing/email/templates') ?>"><strong>Plantillas</strong><span>Diseños para reutilizar</span></a>
      </div>
    </section>
    <details class="av3-email-help">
      <summary>Procesamiento automático y opciones avanzadas</summary>
      <p>En producción, los envíos se pueden procesar mediante el programador del servidor. Si necesitás procesar correos manualmente, hacelo desde la Cola de envío. Esa acción puede enviar mensajes reales.</p>
      <code>php tools/email-marketing-runner.php</code>
    </details>
  </div>
</section>
