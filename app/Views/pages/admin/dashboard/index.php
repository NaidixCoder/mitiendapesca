<?php
$pageTitle = 'Resumen general';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$dashboardView = is_array($dashboard_view ?? null) ? $dashboard_view : [];
extract($dashboardView, EXTR_SKIP);
$metrics = is_array($kpis ?? null) ? $kpis : [];
$metricKeys = ['facturacion hoy', 'pedidos pendientes', 'productos activos', 'bajo stock'];
$metricSet = [];
foreach ($metricKeys as $key) {
  foreach ($metrics as $metric) {
    $label = mb_strtolower((string)($metric['label'] ?? ''), 'UTF-8');
    $ascii = strtr($label, ['ó' => 'o', 'á'=>'a', 'é'=>'e', 'í'=>'i', 'ú'=>'u']);
    if (str_contains($ascii, $key)) { $metricSet[] = $metric; break; }
  }
}
$panel = is_array($pendingPanel ?? null) ? $pendingPanel : [];
$orders = is_array($panel['rows'] ?? null) ? array_slice($panel['rows'], 0, 5) : [];
$ordersUrl = url('/admin/orders');
$alerts = is_array($alerts ?? null) ? $alerts : [];
$stocks = is_array($lowStockRows ?? null) ? $lowStockRows : [];
$events = is_array($activityEvents ?? null) ? array_slice($activityEvents, 0, 6) : [];
$featureRows = is_array($featuredRows ?? null) ? $featuredRows : [];
$clientRows = is_array($topClientsRows ?? null) ? $topClientsRows : [];
$attrs = is_array($panel['attrs'] ?? null) ? $panel['attrs'] : [];
?>
<div class="admin-surface">
<main class="av3-dashboard">
  <?php
  $adminHeaderTitle='Resumen general';
  $adminHeaderDescription='Actividad y rendimiento de tu tienda.';
  $adminHeaderBreadcrumbs=[['label'=>'Inicio']];
  $adminHeaderActions='';
  require BASE_PATH.'/app/Views/partials/admin/page_header.php';
  ?>
  <section class="av3-summary" aria-label="Indicadores generales">
    <?php foreach ($metricSet as $metric): ?>
      <?php $target=(string)($metric['href'] ?? ''); $tag=$target!==''?'a':'div'; ?>
      <<?= $tag ?> class="av3-summary__metric"<?= $target!==''?' href="'.e($target).'"':'' ?>>
        <span class="av3-summary__label"><?= e((string)($metric['label'] ?? '')) ?></span>
        <strong class="av3-summary__value"><?= e((string)($metric['value'] ?? '0')) ?></strong>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </section>

  <div class="av3-dashboard__columns">
    <div class="av3-dashboard__column av3-dashboard__column--primary">
    <section id="pending-orders" class="av3-panel av3-card av3-orders"
      <?php foreach (['index_base','show_base','status_endpoint'] as $attr): ?>
        <?php if (!empty($attrs[$attr])): ?> data-<?= str_replace('_','-',$attr) ?>="<?= e((string)$attrs[$attr]) ?>"<?php endif; ?>
      <?php endforeach; ?>>
      <header class="av3-panel__heading av3-card__head"><h2>Pedidos por gestionar</h2><a href="<?= e($ordersUrl) ?>">Ver todos</a></header>
      <div class="av3-table-wrap">
        <table class="av3-table">
          <thead><tr><th>Pedido</th><th>Cliente</th><th>Estado</th><th class="av3-number">Total</th></tr></thead>
          <tbody>
          <?php if (!$orders): ?><tr><td colspan="4" class="av3-empty">No hay pedidos pendientes.</td></tr><?php endif; ?>
          <?php foreach ($orders as $o): ?>
            <tr data-admin-row-link="1" data-href="<?= e((string)($o['href'] ?? '')) ?>">
              <td><a class="av3-order-link" href="<?= e((string)($o['href'] ?? '#')) ?>"><?= e((string)($o['id_label'] ?? '')) ?></a><small><?= e((string)($o['created_label'] ?? '')) ?></small></td>
              <td><?= e((string)($o['customer_label'] ?? '-')) ?></td>
              <td><span class="av3-state"><?= e((string)($o['status_label'] ?? '')) ?></span><?php if (!empty($o['is_urgent'])): ?><span class="av3-state av3-state--urgent">Urgente</span><?php endif; ?></td>
              <td class="av3-number"><?= e((string)($o['total_label'] ?? '$ 0')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="av3-order-mobile" aria-label="Pedidos por gestionar">
        <?php if (!$orders): ?><p class="av3-empty">No hay pedidos pendientes.</p><?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <a class="av3-order-mobile__item" href="<?= e((string)($o['href'] ?? '#')) ?>">
            <div class="av3-order-mobile__top">
              <strong>Pedido <?= e((string)($o['id_label'] ?? '')) ?></strong>
              <strong class="av3-number"><?= e((string)($o['total_label'] ?? '$ 0')) ?></strong>
            </div>
            <div class="av3-order-mobile__middle">
              <span class="av3-order-mobile__customer"><?= e((string)($o['customer_label'] ?? '-')) ?></span>
              <time class="av3-order-mobile__date"><?= e((string)($o['created_label'] ?? '')) ?></time>
            </div>
            <div class="av3-order-mobile__bottom">
              <span class="av3-state"><?= e((string)($o['status_label'] ?? '')) ?></span>
              <?php if (!empty($o['is_urgent'])): ?><span class="av3-state av3-state--urgent">Urgente</span><?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="av3-panel av3-card av3-stock">
      <header class="av3-panel__heading av3-card__head"><h2>Stock bajo</h2><a href="<?= url('/admin/products') ?>">Ver productos</a></header>
      <div class="av3-table-wrap">
      <table class="av3-table"><thead><tr><th>Producto</th><th class="av3-number">Stock</th><th class="av3-number">Mínimo</th></tr></thead>
      <tbody>
      <?php if (!$stocks): ?><tr><td colspan="3" class="av3-empty">No hay productos con stock bajo.</td></tr><?php endif; ?>
      <?php foreach ($stocks as $s): ?>
        <tr data-admin-row-link="1" data-href="<?= e((string)($s['edit_href'] ?? '')) ?>">
          <td><strong><?= e((string)($s['name'] ?? '')) ?></strong><small><?= e((string)($s['sku'] ?? '')) ?><?= !empty($s['variant_title']) ? ' · '.e((string)$s['variant_title']) : '' ?></small></td>
          <td class="av3-number"><span class="av3-stock-count<?= (int)($s['stock'] ?? 0) === 0 ? ' av3-stock-count--zero' : ' av3-stock-count--low' ?>"><?= (int)($s['stock'] ?? 0) ?></span></td><td class="av3-number"><?= (int)($s['thr'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
      <div class="av3-stock-mobile">
        <?php if (!$stocks): ?><p class="av3-empty">No hay productos con stock bajo.</p><?php endif; ?>
        <?php foreach ($stocks as $stock): ?>
          <a class="av3-stock-mobile__item" href="<?= e((string)($stock['edit_href'] ?? '#')) ?>">
            <div><strong><?= e((string)($stock['name'] ?? '')) ?></strong><small><?= e((string)($stock['sku'] ?? '')) ?><?= !empty($stock['variant_title']) ? ' · '.e((string)$stock['variant_title']) : '' ?></small></div>
            <div class="av3-stock-mobile__counts"><span class="av3-stock-count<?= (int)($stock['stock'] ?? 0) === 0 ? ' av3-stock-count--zero' : ' av3-stock-count--low' ?>">Stock: <?= (int)($stock['stock'] ?? 0) ?></span><span>Mín. <strong><?= (int)($stock['thr'] ?? 0) ?></strong></span></div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    </div>
    <div class="av3-dashboard__column av3-dashboard__column--secondary">
    <section class="av3-panel av3-card av3-alerts">
      <header class="av3-panel__heading av3-card__head"><h2>Requiere atención</h2></header>
      <?php if (!$alerts): ?><p class="av3-empty">No hay alertas pendientes.</p><?php endif; ?>
      <ul class="av3-alert-list">
      <?php foreach ($alerts as $alert):
        $message=(string)($alert['text'] ?? ''); if ($message==='') continue;
        $tone=(string)($alert['tone'] ?? '');
        $legacy=(string)($alert['badge_class'] ?? '');
        $urgent=($tone==='is-danger' || str_contains($legacy,'red-'));
        $href=(string)($alert['href'] ?? '');
      ?>
        <li>
          <?php if ($href !== ''): ?><a href="<?= e($href) ?>"><?php else: ?><span><?php endif; ?>
            <i class="av3-alert-dot<?= $urgent?' av3-alert-dot--urgent':'' ?>" aria-hidden="true"></i>
            <?= e($message) ?>
          <?php if ($href !== ''): ?></a><?php else: ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
      </ul>
    </section>
    <section class="av3-panel av3-card av3-events">
      <header class="av3-panel__heading av3-card__head"><h2>Actividad reciente</h2><a href="<?= url('/admin/audit') ?>">Ver todo</a></header>
      <?php if (!$events): ?><p class="av3-empty">Sin actividad reciente.</p><?php endif; ?>
      <ul class="av3-event-list">
      <?php foreach ($events as $event):
        $raw=(string)($event['text'] ?? 'Evento del sistema');
        $label=str_ireplace(['Checkout Mp Payment Error (checkout)','Checkout Mp Payment Error'],['Error en pago de Mercado Pago','Error en pago de Mercado Pago'],$raw);
        $eventIsError=(bool)preg_match('/error|fallid|rechazad/i', $label);
      ?>
        <li><span class="av3-event-dot<?= $eventIsError ? ' av3-event-dot--error' : '' ?>" aria-hidden="true"></span><div>
        <?php if (!empty($event['url'])): ?><a href="<?= e((string)$event['url']) ?>"><?= e($label) ?></a><?php else: ?><span><?= e($label) ?></span><?php endif; ?>
        <small><?= e((string)($event['actor'] ?? 'Sistema')) ?><?= !empty($event['when']) ? ' · '.e((string)$event['when']) : '' ?></small>
        </div></li>
      <?php endforeach; ?>
      </ul>
    </section>
    </div>
  </div>

  <section class="av3-performance" aria-label="Rendimiento comercial">
    <header class="av3-section-head"><h2>Rendimiento comercial</h2><a href="<?= url('/admin/analytics') ?>">Ver analíticas</a></header>
    <div class="av3-panel av3-card av3-performance__body">
      <?php /* Mantener IDs del gráfico y controles para el JS existente. */ ?>
      <?php render_partial('admin/dashboard/trend'); ?>
    </div>
  </section>
  <?php if ($featureRows || $clientRows): ?>
  <div class="av3-rankings">
    <?php if ($featureRows): ?><?php render_partial('admin/dashboard/featured_products',['rows'=>$featureRows]); ?><?php endif; ?>
    <?php if ($clientRows): ?><?php render_partial('admin/dashboard/top_clients_30',['rows'=>$clientRows]); ?><?php endif; ?>
  </div>
  <?php endif; ?>


</main>
</div>
