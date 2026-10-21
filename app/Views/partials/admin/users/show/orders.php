<?php
$ord = is_array($show['orders'] ?? null) ? $show['orders'] : ['rows' => []];
$ordersRows = is_array($ord['rows'] ?? null) ? $ord['rows'] : [];
$pg = is_array($show['pagination'] ?? null) ? $show['pagination'] : [];
$page = (int)($pg['page'] ?? 1);
$pages = (int)($pg['pages'] ?? 1);
$totalOrders = (int)($pg['total'] ?? 0);
$prevHref = (string)($pg['prev_href'] ?? '#');
$nextHref = (string)($pg['next_href'] ?? '#');
$prevDisabled = !empty($pg['prev_disabled']);
$nextDisabled = !empty($pg['next_disabled']);
?>
<div class="av3-card">
  <div class="av3-card__head">
    <h2 class="av3-card__title">Pedidos (historial completo)</h2>
    <div class="av3-card__meta"><?= (int)$totalOrders ?> registro(s)</div>
  </div>

  <?php if (!$ordersRows): ?>
    <div class="empty-state"><p>No hay pedidos asociados a este cliente.</p></div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="av3-table">
        <thead>
          <tr><th>ID</th><th>Fecha</th><th>Estado</th><th>Metodo</th><th class="av3-text-right">Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($ordersRows as $o): ?>
            <?php $oid = (int)($o['id'] ?? 0); $href = (string)($o['href'] ?? url('/admin/orders/show?id='.(int)$oid)); ?>
            <tr class="dash-row-link" data-href="<?= e($href) ?>">
              <td>#<?= (int)$oid ?></td>
              <td><?= e((string)($o['date'] ?? '-')) ?></td>
              <td><?= e((string)($o['status_label'] ?? '-')) ?></td>
              <td><?= !empty($o['payment_method']) ? e((string)$o['payment_method']) : '-' ?></td>
              <td class="av3-text-right"><?= e((string)($o['total_fmt'] ?? money(0))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="paginator">
      <div class="paginator__info">Pagina <?= (int)$page ?> de <?= (int)$pages ?> (<?= (int)$totalOrders ?>)</div>
      <div class="paginator__actions">
        <a class="btn btn-soft <?= $prevDisabled ?'is-disabled' : '' ?>" href="<?= $prevDisabled ? 'javascript:void(0)' : e($prevHref) ?>">Anterior</a>
        <a class="btn btn-soft <?= $nextDisabled ?'is-disabled' : '' ?>" href="<?= $nextDisabled ? 'javascript:void(0)' : e($nextHref) ?>">Siguiente</a>
      </div>
    </div>
  <?php endif; ?>
</div>
