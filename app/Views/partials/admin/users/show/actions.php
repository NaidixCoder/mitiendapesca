<?php
$u = is_array($show['user'] ?? null) ? $show['user'] : [];
$links = is_array($show['links'] ?? null) ? $show['links'] : [];
$uid = (int)($u['id'] ?? 0);
?>
<div class="av3-card">
  <div class="av3-card__head"><h2 class="av3-card__title">Acciones</h2></div>
  <div class="av3-flow-2">
    <a class="btn av3-width-full" href="<?= e((string)($links['orders_by_user'] ?? url('/admin/orders?user_id='.(int)$uid))) ?>">Ver pedidos del cliente</a>
    <a class="btn btn-soft av3-width-full" href="<?= e((string)($links['audit_by_user'] ?? url('/admin/audit?tipo=user&q=%23'.(int)$uid))) ?>">Ver auditoria</a>
  </div>
</div>
