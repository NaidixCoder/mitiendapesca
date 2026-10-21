<?php
$items = is_array($items ?? null) ? $items : [];
$orders_url = (string)($orders_url ?? url('/cuenta/pedidos'));
$unsubscribe_url = (string)($unsubscribe_url ?? '');
?>
<h2 style="margin:0 0 12px;font-size:18px;">¡Gracias por tu compra! ✅</h2>
<p style="margin:0 0 12px;">Ya registramos tu pedido. Podés ver el estado cuando quieras en tu cuenta.</p>
<?php if ($items): ?>
  <div style="border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin:0 0 12px;">
    <div style="font-weight:600;margin:0 0 8px;">Tu compra</div>
    <ul style="margin:0;padding-left:18px;">
      <?php foreach ($items as $it): ?>
        <li style="margin:0 0 6px;">
          <?= e((string)($it['product_name'] ?? '')) ?>
          <?php if (!empty($it['variant_title'])): ?> — <span style="color:#6b7280;"><?= e((string)$it['variant_title']) ?></span><?php endif; ?>
          · <strong><?= (int)($it['qty'] ?? 0) ?>u</strong>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<p style="margin:0 0 12px;">
  <a href="<?= e($orders_url) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">Ver mis pedidos</a>
</p>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />
<p style="margin:0;color:#6b7280;font-size:12px;">Baja: <a href="<?= e($unsubscribe_url) ?>">darse de baja</a>.</p>
