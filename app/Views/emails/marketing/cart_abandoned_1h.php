<?php
$items = is_array($items ?? null) ? $items : [];
$cart_url = (string)($cart_url ?? url('/carrito'));
$unsubscribe_url = (string)($unsubscribe_url ?? '');
?>
<h2 style="margin:0 0 12px;font-size:18px;">Te quedó un carrito pendiente 🛒</h2>
<p style="margin:0 0 12px;">Guardamos tu carrito para que sigas cuando quieras.</p>
<?php if ($items): ?>
  <div style="border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin:0 0 12px;">
    <div style="font-weight:600;margin:0 0 8px;">Productos</div>
    <ul style="margin:0;padding-left:18px;">
      <?php foreach ($items as $it): ?>
        <li style="margin:0 0 6px;">
          <a href="<?= e((string)($it['product_url'] ?? '')) ?>" style="text-decoration:none;">
            <?= e((string)($it['product_name'] ?? '')) ?>
          </a>
          <?php if (!empty($it['variant_title'])): ?> — <span style="color:#6b7280;"><?= e((string)$it['variant_title']) ?></span><?php endif; ?>
          · <strong><?= (int)($it['qty'] ?? 0) ?>u</strong>
          <?php if (!empty($it['unit_price'])): ?> · <?= e(money((int)$it['unit_price'])) ?><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<p style="margin:0 0 12px;">
  <a href="<?= e($cart_url) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">Volver al carrito</a>
</p>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />
<p style="margin:0;color:#6b7280;font-size:12px;">Baja: <a href="<?= e($unsubscribe_url) ?>">darse de baja</a>.</p>
