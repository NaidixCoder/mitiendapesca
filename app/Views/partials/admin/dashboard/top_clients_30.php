<?php
/**
 * Top clientes (30 dias)
 * Espera: $rows = array de [display_name,display_email,orders_count,total_spent,last_order_label,profile_href]
 */
$rows = $rows ?? [];
?>

<section class="av3-panel av3-ranking">
  <div class="av3-ranking__body">
    <div>
      <h2 >Top clientes (30 dias)</h2>
      <p class="av3-empty">Los que mas compraron en el ultimo mes (solo ventas confirmadas).</p>
    </div>
    <div>
      <a class="av3-action-link" href="<?= e(url('/admin/users')) ?>">Ver clientes</a>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="av3-ranking__scroll">
      <div >Sin datos</div>
      <div >No hay compras registradas en los ultimos 30 dias.</div>
    </div>
  <?php else: ?>
    <div >
      <div >
      <table class="av3-ranking__table">
        <thead>
          <tr>
            <th>Cliente</th>
            <th >Pedidos</th>
            <th >Total</th>
            <th >Ultima compra</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr data-admin-row-link="1" data-href="<?= e((string)($r['profile_href'] ?? '')) ?>">
              <td>
                <div ><?= e((string)($r['display_name'] ?? 'Cliente')) ?></div>
                <?php if (!empty($r['display_email'])): ?>
                  <div ><?= e((string)$r['display_email']) ?></div>
                <?php endif; ?>
              </td>
              <td ><?= e((string)($r['orders_count'] ?? 0)) ?></td>
              <td ><?= e(money((int)($r['total_spent'] ?? 0))) ?></td>
              <td ><?= e((string)($r['last_order_label'] ?? '-')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  <?php endif; ?>
</section>
