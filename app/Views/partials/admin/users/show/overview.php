<?php
$s=is_array($show['stats']??null)?$show['stats']:[];
?>
<section class="av3-ui-card">
 <div class="av3-ui-card-header"><h2 class="av3-ui-card-title">Actividad comercial</h2><span class="av3-ui-badge av3-ui-badge--neutral"><?= e((string)($s['tier']??'Bronce')) ?></span></div>
 <div class="av3-ui-card-body av3-users__stack">
  <div class="av3-users__metrics">
   <div><span class="av3-users__meta">Total abonado</span><strong><?= e((string)($s['paid_total_fmt']??money(0))) ?></strong></div>
   <div><span class="av3-users__meta">Pedidos pagos</span><strong><?= (int)($s['paid_orders_count']??0) ?></strong></div>
   <div><span class="av3-users__meta">Ticket promedio</span><strong><?= e((string)($s['avg_paid_total_fmt']??money(0))) ?></strong></div>
  </div>
  <div class="av3-users__metrics">
   <div><span class="av3-users__meta">Primera compra</span><span><?= e((string)(($s['first_paid_at']??'')?:'Sin compras')) ?></span></div>
   <div><span class="av3-users__meta">Última compra</span><span><?= e((string)(($s['last_paid_at']??'')?:'Sin compras')) ?></span></div>
   <div><span class="av3-users__meta">Puntos de fidelidad</span><span><?= (int)($s['loyalty_points']??0) ?></span></div>
  </div>
 </div>
</section>
