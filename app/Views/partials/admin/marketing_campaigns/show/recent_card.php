<div class="av3-marketing-detail__recent">
  <section class="av3-marketing-board" aria-labelledby="mkt-hits-title">
    <div class="av3-marketing-section-head"><div><h2 id="mkt-hits-title">Visitas recientes</h2><p>Últimos <?= (int)$days ?> días.</p></div></div>
    <?php if (!$recentHits): ?><p class="av3-marketing-detail__empty">No hay visitas registradas.</p>
    <?php else: ?><div class="av3-marketing-report-table-wrap">
      <table class="av3-marketing-report-table">
        <thead><tr><th>Fecha</th><th>Origen</th></tr></thead>
        <tbody><?php foreach ($recentHits as $h): $ref = is_array($h['referrer'] ?? null) ? $h['referrer'] : []; ?>
          <tr><td><?= e((string)($h['created_at'] ?? '')) ?></td><td><span class="av3-marketing-referrer av3-marketing-referrer--<?= e((string)($ref['tone'] ?? 'neutral')) ?>"><?= e((string)($ref['badge'] ?? 'Directo')) ?></span><div class="av3-marketing-referrer__text">
            <?php if (!empty($ref['href'])): ?><a href="<?= e((string)$ref['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)($ref['text'] ?? '')) ?></a>
            <?php else: ?><?= e((string)($ref['text'] ?? '')) ?><?php endif; ?>
          </div></td></tr>
        <?php endforeach; ?></tbody>
      </table>
    </div><?php endif; ?>
  </section>
  <section class="av3-marketing-board" aria-labelledby="mkt-orders-title">
    <div class="av3-marketing-section-head"><div><h2 id="mkt-orders-title">Compras atribuidas</h2><p>Pedidos confirmados del período.</p></div></div>
    <?php if (!$recentOrders): ?><p class="av3-marketing-detail__empty">No hay compras atribuidas.</p>
    <?php else: ?><div class="av3-marketing-report-table-wrap">
      <table class="av3-marketing-report-table">
        <thead><tr><th>Pedido</th><th>Cliente</th><th>Total</th></tr></thead>
        <tbody><?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><a href="<?= e((string)($o['show_url'] ?? '')) ?>">#<?= (int)($o['id'] ?? 0) ?></a><small><?= e((string)($o['created_at'] ?? '')) ?></small></td>
            <td><?= e((string)($o['customer_name'] ?? '—')) ?></td>
            <td>$ <?= e((string)($o['total_fmt'] ?? '0')) ?></td>
          </tr>
        <?php endforeach; ?></tbody>
      </table>
    </div><?php endif; ?>
  </section>
</div>
