<?php
/** @var array $user */
/** @var int $orderId */
/** @var array $items */
/** @var int $total */
/** @var string $paymentMethod */
/** @var string $orderUrl */
$nombre = (string)($user['name'] ?? 'Hola');
$pm = $paymentMethod ?: 'No especificado';
?>
<div style="font-family:Arial,Helvetica,sans-serif;">
  <h1 style="margin:0 0 10px 0;font-size:20px;line-height:1.25;color:#111827;">¡Pago confirmado, <?= e($nombre) ?>! ✅</h1>

  <p style="margin:0 0 14px 0;color:#374151;font-size:14px;line-height:1.5;">
    Se acreditó el pago del pedido <strong>#<?= (int)$orderId ?></strong>.
    Método: <strong><?= e($pm) ?></strong>.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #eef0f3;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:12px 14px;background:#f9fafb;">
        <div style="color:#111827;font-size:13px;font-weight:700;">Detalle</div>
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
            </tr>
          <?php endforeach; ?>
          <tr><td style="border-top:1px solid #eef0f3;padding-top:10px;"></td></tr>
          <tr>
            <td style="padding:8px 0;color:#111827;font-weight:700;">Total pagado: $<?= number_format($total/100, 2, ',', '.') ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <div style="margin-top:16px;">
    <a href="<?= e($orderUrl) ?>" style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:700;">
      Ver mi pedido
    </a>
  </div>

  <p style="margin:14px 0 0 0;color:#6b7280;font-size:12px;line-height:1.5;">
    Próximo paso: estamos preparando tu pedido. Te vamos avisando por esta vía.
  </p>
</div>
