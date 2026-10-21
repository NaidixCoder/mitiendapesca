<section class="av3-marketing-board av3-marketing-detail__performance" aria-labelledby="mkt-performance-title">
  <div class="av3-marketing-section-head">
    <div><h2 id="mkt-performance-title">Rendimiento</h2><p>Resultados de la campaña durante el período seleccionado.</p></div>
    <form class="av3-marketing-detail__period" method="get" action="<?= e((string)($links['show'] ?? '')) ?>">
      <input type="hidden" name="id" value="<?= (int)($campaign['id'] ?? 0) ?>">
      <label for="mkt-report-days">Período</label>
      <select id="mkt-report-days" name="days">
        <?php foreach ([7,30,90] as $n): ?><option value="<?= $n ?>"<?= $days === $n ? ' selected' : '' ?>><?= $n ?> días</option><?php endforeach; ?>
      </select>
      <button class="av3-ui-btn" type="submit">Aplicar</button>
    </form>
  </div>
  <div class="av3-marketing-detail__metrics">
    <?php foreach ([
      ['label'=>'Visitas','value'=>$report['hits_fmt'] ?? '0'],
      ['label'=>'Nuevos usuarios','value'=>$report['new_users_fmt'] ?? '0'],
      ['label'=>'Agregados al carrito','value'=>$report['add_to_cart_fmt'] ?? '0'],
      ['label'=>'Compras','value'=>$report['purchases_fmt'] ?? '0'],
      ['label'=>'Ingresos','value'=>'$ '.($report['revenue_fmt'] ?? '0')],
    ] as $metric): ?>
      <div class="av3-marketing-detail__metric"><span><?= e((string)$metric['label']) ?></span><strong><?= e((string)$metric['value']) ?></strong></div>
    <?php endforeach; ?>
  </div>
  <div class="av3-marketing-detail__secondary">
    <div class="av3-marketing-detail__ratios">
      <strong>Conversión sobre visitas</strong>
      <div>
        <span>Nuevos <b><?= e((string)($report['ratio_new_users_fmt'] ?? '0,0%')) ?></b></span>
        <span>Carrito <b><?= e((string)($report['ratio_add_to_cart_fmt'] ?? '0,0%')) ?></b></span>
        <span>Compras <b><?= e((string)($report['ratio_purchases_fmt'] ?? '0,0%')) ?></b></span>
      </div>
    </div>
    <?php if (!empty($campaign['notes'])): ?>
      <div class="av3-marketing-detail__notes"><strong>Notas internas</strong><p><?= e((string)$campaign['notes']) ?></p></div>
    <?php endif; ?>
  </div>
</section>
<section class="av3-marketing-board av3-marketing-detail__series" aria-labelledby="mkt-series-title">
  <div class="av3-marketing-section-head"><div><h2 id="mkt-series-title">Evolución diaria</h2><p>Visitas, usuarios, carrito, compras e ingresos.</p></div></div>
  <?php if (!$series): ?>
    <div class="av3-marketing-detail__empty">Todavía no hay actividad registrada para este período.</div>
  <?php else: ?>
    <div class="av3-marketing-report-table-wrap">
      <table class="av3-marketing-report-table">
        <thead><tr><th>Fecha</th><th>Visitas</th><th>Usuarios</th><th>Carrito</th><th>Compras</th><th>Ingresos</th></tr></thead>
        <tbody><?php foreach ($series as $row): ?>
          <tr><td><?= e((string)$row['date']) ?></td><td><?= e((string)$row['hits_fmt']) ?></td><td><?= e((string)$row['new_users_fmt']) ?></td><td><?= e((string)$row['add_to_cart_fmt']) ?></td><td><?= e((string)$row['purchases_fmt']) ?></td><td>$ <?= e((string)$row['revenue_fmt']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
