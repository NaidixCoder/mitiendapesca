<?php
$pageId = 'admin-email-campaign-show';
$pageTitle = 'Detalle de envío';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$campaign = is_array($campaign_view ?? null) ? $campaign_view : [];
$stats = is_array($stats_view ?? null) ? $stats_view : [];
$orders = is_array($orders_view ?? null) ? $orders_view : [];
$links = is_array($links ?? null) ? $links : [];
$notice = is_array($notice ?? null) ? $notice : [];
$id = (int)($campaign['id'] ?? 0);
$audParams = is_array($campaign['audience_params'] ?? null) ? $campaign['audience_params'] : [];
$adminHeaderTitle = (string)($campaign['name'] ?? 'Detalle de envío');
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
 ['label'=>'Envíos','href'=>url('/admin/marketing/email/campanas')],
 ['label'=>$adminHeaderTitle],
];
$adminHeaderActions =
 '<a class="av3-ui-btn" href="'.e((string)($links['back'] ?? url('/admin/marketing/email/campanas'))).'">Volver</a>';
if (!empty($campaign['can_edit'])) {
    $adminHeaderActions .= '<a class="av3-ui-btn av3-ui-btn--primary" href="'.e((string)($links['edit'] ?? '')).'">Editar</a>';
}
?>
<section class="admin-surface">
<div class="av3-wrap av3-marketing-email av3-email-campaign-detail" data-email-campaign-detail>
  <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <?php if (!empty($notice['show'])): ?>
    <div class="av3-email-notice av3-email-notice--<?= e((string)($notice['type'] ?? 'info')) ?>" role="status">
      <strong><?= e((string)($notice['title'] ?? 'Información')) ?></strong>
      <?= e((string)($notice['message'] ?? '')) ?>
    </div>
  <?php endif; ?>
  <section class="av3-email-panel av3-email-campaign-detail__overview" aria-labelledby="email-campaign-overview-title">
    <header class="av3-email-panel__header av3-email-campaign-detail__head">
      <div>
        <h2 id="email-campaign-overview-title"><?= e((string)($campaign['name'] ?? '')) ?></h2>
        <p>Asunto: <?= e((string)($campaign['subject'] ?? '')) ?></p>
        <?php if (!empty($campaign['scheduled_at'])): ?>
          <p>Programada para: <?= e((string)$campaign['scheduled_at']) ?></p>
        <?php endif; ?>
      </div>
      <div class="av3-email-campaign-detail__actions">
        <span class="av3-email-status av3-email-status--<?= e((string)($campaign['status_tone'] ?? 'neutral')) ?>"><?= e((string)($campaign['status_label'] ?? '')) ?></span>
        <?php if (!empty($campaign['can_schedule_now'])): ?>
          <form method="post" action="<?= e((string)($links['schedule_now'] ?? url('/admin/marketing/email/campanas/schedule_now'))) ?>" data-confirm-schedule-email>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <button class="av3-ui-btn av3-ui-btn--primary" type="submit">Programar ahora</button>
          </form>
        <?php endif; ?>
        <?php if (!empty($campaign['can_cancel'])): ?>
          <form method="post" action="<?= e((string)($links['cancel'] ?? url('/admin/marketing/email/campanas/cancel'))) ?>" data-confirm-cancel-email>
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <button class="av3-ui-btn" type="submit">Cancelar envío</button>
          </form>
        <?php endif; ?>
      </div>
    </header>
    <div class="av3-email-campaign-detail__metrics">
      <?php foreach ([
       ['Enviados','sent_count'],
       ['Aperturas aprox.','open_count'],
       ['Clics','click_count'],
       ['Compras','orders_count'],
      ] as [$label,$key]): ?>
        <div class="av3-email-metric"><span><?= e($label) ?></span><strong><?= (int)($stats[$key] ?? 0) ?></strong></div>
      <?php endforeach; ?>
      <div class="av3-email-metric"><span>Ingresos</span><strong><?= e((string)($stats['revenue_total_label'] ?? '$ 0')) ?></strong></div>
    </div>
    <p class="av3-email-campaign-detail__hint">Las aperturas son aproximadas; algunos clientes de correo bloquean imágenes.</p>
  </section>
  <div class="av3-email-campaign-detail__content">
    <section class="av3-email-panel" aria-labelledby="email-campaign-settings-title">
      <header class="av3-email-panel__header"><div><h2 id="email-campaign-settings-title">Configuración</h2><p>Audiencia y destino del botón principal.</p></div></header>
      <dl class="av3-email-campaign-detail__definition">
        <div>
          <dt>Destinatarios</dt><dd><?= e((string)($campaign['audience_label'] ?? '')) ?></dd>
          <?php if (($campaign['audience_key'] ?? '') === 'inactive_buyers' && isset($audParams['days'])): ?>
            <small>Sin compras durante <?= (int)$audParams['days'] ?> días.</small>
          <?php endif; ?>
          <?php if (($campaign['audience_key'] ?? '') === 'vip' && !empty($campaign['vip_min_total_label'])): ?>
            <small>Gasto mínimo: <?= e((string)$campaign['vip_min_total_label']) ?>.</small>
          <?php endif; ?>
        </div>
        <div><dt>Texto del botón</dt><dd><?= e((string)($campaign['cta_label'] ?? '')) ?: 'Sin configurar' ?></dd></div>
        <div><dt>Destino</dt><dd class="av3-email-campaign-detail__url"><?= e((string)($campaign['cta_url'] ?? '')) ?: 'Sin configurar' ?></dd></div>
      </dl>
    </section>
    <section class="av3-email-panel av3-email-campaign-detail__preview" aria-labelledby="email-campaign-preview-title">
      <header class="av3-email-panel__header"><div><h2 id="email-campaign-preview-title">Vista previa del contenido</h2><p>HTML aislado del administrador; no se envía ningún correo.</p></div></header>
      <div class="av3-email-campaign-detail__frame">
        <iframe title="Contenido del email" sandbox="" referrerpolicy="no-referrer" loading="lazy" srcdoc="<?= e((string)($campaign['body_html'] ?? '')) ?>"></iframe>
      </div>
    </section>
  </div>
  <section class="av3-email-panel av3-email-campaign-detail__orders" aria-labelledby="email-campaign-orders-title">
    <header class="av3-email-panel__header"><div><h2 id="email-campaign-orders-title">Compras atribuidas</h2><p>Pedidos confirmados asociados al envío.</p></div></header>
    <?php if (!$orders): ?>
      <div class="av3-email-empty av3-email-empty--compact">Todavía no hay compras asociadas a esta campaña.</div>
    <?php else: ?>
      <div class="av3-email-table-wrap">
        <table class="av3-email-table">
          <thead><tr><th scope="col">Pedido</th><th scope="col">Usuario</th><th scope="col">Estado</th><th scope="col">Total</th><th scope="col">Fecha</th></tr></thead>
          <tbody><?php foreach ($orders as $o): ?><tr>
            <td><a href="<?= e((string)($o['order_url'] ?? '')) ?>">#<?= (int)$o['id'] ?></a></td>
            <td>#<?= (int)$o['user_id'] ?></td>
            <td><?= e((string)$o['status_label']) ?></td>
            <td><?= e((string)$o['total_label']) ?></td>
            <td><?= e((string)$o['created_at']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <div class="av3-email-mobile-list">
        <?php foreach ($orders as $o): ?><article class="av3-email-mobile-item">
          <div class="av3-email-mobile-item__top"><a class="av3-email-record-name" href="<?= e((string)($o['order_url'] ?? '')) ?>">Pedido #<?= (int)$o['id'] ?></a><strong><?= e((string)$o['total_label']) ?></strong></div>
          <div class="av3-email-mobile-item__meta">Usuario #<?= (int)$o['user_id'] ?> · <?= e((string)$o['status_label']) ?></div>
          <div class="av3-email-mobile-item__meta"><?= e((string)$o['created_at']) ?></div>
        </article><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
</section>
