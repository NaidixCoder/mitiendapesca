<?php
/** @var array $user */
/** @var int $orderId */
/** @var array $items */
/** @var int $subtotal */
/** @var int $shipping */
/** @var int $discount */
/** @var int $total */
/** @var string $paymentMethod */
/** @var array $shippingAddress */
/** @var string $orderUrl */
$nombre = (string)($user['name'] ?? 'Hola');
$pm = $paymentMethod ?: 'No especificado';
?>
<div style="font-family:Arial,Helvetica,sans-serif;">
  <h1 style="margin:0 0 10px 0;font-size:20px;line-height:1.25;color:#111827;">¡Gracias <?= e($nombre) ?>! Recibimos tu pedido.</h1>

  <p style="margin:0 0 14px 0;color:#374151;font-size:14px;line-height:1.5;">
    Tu pedido <strong>#<?= (int)$orderId ?></strong> se creó correctamente y quedó en estado <strong>Pendiente de pago</strong>.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #eef0f3;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:12px 14px;background:#f9fafb;">
        <div style="color:#111827;font-size:13px;font-weight:700;">Resumen</div>
        <div style="color:#6b7280;font-size:12px;margin-top:2px;">Método: <?= e($pm) ?></div>
      </td>
    </tr>
    <tr>
      <td style="padding:10px 14px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:13px;color:#111827;">
          <?php foreach ($items as $it): ?>
            <tr>
              <td style="padding:6px 0;color:#111827;">
                <?= e((string)($it['product_name'] ?? $it['name'] ?? 'Producto')) ?>
                <span style="color:#6b7280;">×<?= (int)($it['qty'] ?? 0) ?></span>
              </td>
              <td align="right" style="padding:6px 0;color:#111827;">
                $<?= number_format(((int)($it['price'] ?? 0) * (int)($it['qty'] ?? 0))/100, 2, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr><td colspan="2" style="border-top:1px solid #eef0f3;padding-top:10px;"></td></tr>
          <tr>
            <td style="padding:6px 0;color:#6b7280;">Subtotal</td>
            <td align="right" style="padding:6px 0;color:#111827;">$<?= number_format($subtotal/100, 2, ',', '.') ?></td>
          </tr>
          <tr>
            <td style="padding:6px 0;color:#6b7280;">Envío</td>
            <td align="right" style="padding:6px 0;color:#111827;">$<?= number_format($shipping/100, 2, ',', '.') ?></td>
          </tr>
          <?php if ($discount > 0): ?>
          <tr>
            <td style="padding:6px 0;color:#6b7280;">Descuento</td>
            <td align="right" style="padding:6px 0;color:#111827;">- $<?= number_format($discount/100, 2, ',', '.') ?></td>
          </tr>
          <?php endif; ?>
          <tr>
            <td style="padding:8px 0;color:#111827;font-weight:700;">Total</td>
            <td align="right" style="padding:8px 0;color:#111827;font-weight:700;">$<?= number_format($total/100, 2, ',', '.') ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <?php if (!empty($shippingAddress)): ?>
    <div style="margin-top:14px;border:1px solid #eef0f3;border-radius:12px;padding:12px 14px;">
      <div style="font-size:13px;font-weight:700;color:#111827;">Envío</div>
      <div style="margin-top:6px;color:#374151;font-size:13px;line-height:1.45;">
        <?= e((string)($shippingAddress['full_name'] ?? '')) ?><?= !empty($shippingAddress['phone']) ? ' · ' . e((string)$shippingAddress['phone']) : '' ?><br>
        <?= e((string)($shippingAddress['street'] ?? '')) ?> <?= e((string)($shippingAddress['number'] ?? '')) ?>
        <?php if (!empty($shippingAddress['apartment'])): ?> (<?= e((string)$shippingAddress['apartment']) ?>)<?php endif; ?><br>
        <?= e((string)($shippingAddress['city'] ?? '')) ?>, <?= e((string)($shippingAddress['state'] ?? '')) ?> <?= e((string)($shippingAddress['zip'] ?? '')) ?>
      </div>
    </div>
  <?php endif; ?>

  <div style="margin-top:16px;">
    <a href="<?= e($orderUrl) ?>" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:700;">
      Ver mi pedido
    </a>
  </div>

  <p style="margin:14px 0 0 0;color:#6b7280;font-size:12px;line-height:1.5;">
    Tip: si elegiste transferencia, revisá el email de instrucciones o entrá a “Mis pedidos”.
  </p>
</div>
