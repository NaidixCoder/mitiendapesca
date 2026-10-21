<?php
$orders_url = (string)($orders_url ?? url('/cuenta/pedidos'));
$unsubscribe_url = (string)($unsubscribe_url ?? '');
?>
<h2 style="margin:0 0 12px;font-size:18px;">¿Cómo te fue con tu compra? ⭐</h2>
<p style="margin:0 0 12px;">Tu opinión nos ayuda un montón. Si podés, contanos en una respuesta a este email:</p>
<ul style="margin:0 0 12px;padding-left:18px;">
  <li style="margin:0 0 6px;">¿El producto cumplió lo que esperabas?</li>
  <li style="margin:0 0 6px;">¿Qué mejorarías?</li>
</ul>
<p style="margin:0 0 12px;">
  <a href="<?= e($orders_url) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">Ver mis pedidos</a>
</p>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />
<p style="margin:0;color:#6b7280;font-size:12px;">Baja: <a href="<?= e($unsubscribe_url) ?>">darse de baja</a>.</p>
