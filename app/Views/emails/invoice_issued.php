<?php
/**
 * Email transaccional: factura emitida (con PDF adjunto)
 *
 * @var array  $user
 * @var int    $orderId
 * @var string $invoiceLabel
 * @var string $invoiceNumber
 * @var string $orderUrl
 */
$nombre = (string)($user['name'] ?? 'Hola');
$label  = (string)($invoiceLabel ?? 'Factura');
$number = (string)($invoiceNumber ?? '');
?>

<div style="font-family:Arial,Helvetica,sans-serif;">
  <h1 style="margin:0 0 10px 0;font-size:20px;line-height:1.25;color:#111827;">Tu factura está lista 🧾</h1>

  <p style="margin:0 0 14px 0;color:#374151;font-size:14px;line-height:1.5;">
    Hola <?= e($nombre) ?>, adjuntamos tu <strong><?= e($label) ?></strong><?= $number !== '' ? ' <strong>N° '.e($number).'</strong>' : '' ?> del pedido <strong>#<?= (int)$orderId ?></strong>.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #eef0f3;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:12px 14px;background:#f9fafb;">
        <div style="color:#111827;font-size:13px;font-weight:700;">Comprobante</div>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 14px;">
        <div style="font-size:13px;color:#111827;line-height:1.5;">
          • Tipo: <strong><?= e($label) ?></strong><br>
          <?php if ($number !== ''): ?>• Número: <strong><?= e($number) ?></strong><br><?php endif; ?>
          • PDF: <strong>adjunto en este email</strong>
        </div>
      </td>
    </tr>
  </table>

  <div style="margin-top:16px;">
    <a href="<?= e($orderUrl) ?>" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:700;">
      Ver mi pedido
    </a>
  </div>

  <p style="margin:14px 0 0 0;color:#6b7280;font-size:12px;line-height:1.5;">
    Si necesitás ayuda, respondé este email y te asistimos.
  </p>
</div>
