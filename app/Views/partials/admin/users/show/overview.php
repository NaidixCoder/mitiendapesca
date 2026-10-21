<?php
$s = is_array($show['stats'] ?? null) ? $show['stats'] : [];
$tier = (string)($s['tier'] ?? 'Bronce');
$badgeTone = (string)($s['tier_badge_tone'] ?? 'neutral');
?>
<div class="av3-card av3-md-col-span-2">
  <div class="av3-card__head">
    <h2 class="av3-card__title">Historial (de por vida)</h2>
    <span class="badge badge-<?= e($badgeTone) ?>"><?= e($tier) ?></span>
  </div>

  <div class="av3-grid av3-gap-3 av3-sm-grid-cols-3">
    <div class="kpi"><div class="kpi__label">Total gastado</div><div class="kpi__value"><?= e((string)($s['paid_total_fmt'] ?? money(0))) ?></div></div>
    <div class="kpi"><div class="kpi__label">Pedidos</div><div class="kpi__value"><?= (int)($s['paid_orders_count'] ?? 0) ?></div></div>
    <div class="kpi"><div class="kpi__label">Ticket promedio</div><div class="kpi__value"><?= e((string)($s['avg_paid_total_fmt'] ?? money(0))) ?></div></div>
  </div>

  <div class="av3-space-top-4 av3-grid av3-gap-3 av3-sm-grid-cols-3">
    <div class="stat"><div class="stat__label">Primera compra</div><div class="stat__value"><?= e((string)($s['first_paid_at'] ?? '')) !== '' ? e((string)$s['first_paid_at']) : '-' ?></div></div>
    <div class="stat"><div class="stat__label">Ultima compra</div><div class="stat__value"><?= e((string)($s['last_paid_at'] ?? '')) !== '' ? e((string)$s['last_paid_at']) : '-' ?></div></div>
    <div class="stat"><div class="stat__label">Puntos</div><div class="stat__value"><?= (int)($s['loyalty_points'] ?? 0) ?></div></div>
  </div>

  <p class="av3-note av3-space-top-4">Fidelidad: 1 punto cada $1.000 gastados. Tiers: Bronce &lt; 100k, Plata >= 100k, Oro >= 250k, Platino >= 500k.</p>
</div>
