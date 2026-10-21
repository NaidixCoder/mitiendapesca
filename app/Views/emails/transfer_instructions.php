<?php
/** @var array $user */
/** @var int $orderId */
/** @var array $items */
/** @var int $total */
/** @var array $bank */
/** @var string $orderUrl */
/** @var string $ordersUrl */
$nombre = (string)($user['name'] ?? 'Hola');
?>
<div style="font-family:Arial,Helvetica,sans-serif;">
  <h1 style="margin:0 0 10px 0;font-size:20px;line-height:1.25;color:#111827;">Instrucciones para transferencia 💳</h1>

  <p style="margin:0 0 14px 0;color:#374151;font-size:14px;line-height:1.5;">
    <?= e($nombre) ?>, para completar tu pedido <strong>#<?= (int)$orderId ?></strong> realizá una transferencia por el total de:
    <strong>$<?= number_format($total/100, 2, ',', '.') ?></strong>.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #eef0f3;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:12px 14px;background:#f9fafb;">
        <div style="color:#111827;font-size:13px;font-weight:700;">Datos bancarios</div>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 14px;color:#111827;font-size:13px;line-height:1.6;">
        <?php if (!empty($bank['titular'])): ?><div><strong>Titular:</strong> <?= e($bank['titular']) ?></div><?php endif; ?>
        <?php if (!empty($bank['banco'])): ?><div><strong>Banco:</strong> <?= e($bank['banco']) ?></div><?php endif; ?>
        <?php if (!empty($bank['cbu'])): ?><div><strong>CBU:</strong> <?= e($bank['cbu']) ?></div><?php endif; ?>
        <?php if (!empty($bank['alias'])): ?><div><strong>Alias:</strong> <?= e($bank['alias']) ?></div><?php endif; ?>
        <?php if (!empty($bank['cuit'])): ?><div><strong>CUIT:</strong> <?= e($bank['cuit']) ?></div><?php endif; ?>

        <?php if (empty($bank['cbu']) && empty($bank['alias'])): ?>
          <div style="color:#b45309;margin-top:8px;">
            ⚠️ Falta configurar los datos bancarios en <code>.env</code> (BANK_*). Por ahora, respondé este email con el comprobante o consultanos por WhatsApp.
          </div>
        <?php endif; ?>

        <div style="margin-top:10px;color:#6b7280;font-size:12px;">
          Importante: colocá como referencia el número de pedido <strong>#<?= (int)$orderId ?></strong>.
        </div>
      </td>
    </tr>
  </table>

  <div style="margin-top:16px;">
    <a href="<?= e($orderUrl) ?>" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:700;">
      Ver mi pedido
    </a>
    <a href="<?= e($ordersUrl) ?>" style="display:inline-block;margin-left:10px;background:#111827;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:700;">
      Mis pedidos
    </a>
  </div>

  <p style="margin:14px 0 0 0;color:#6b7280;font-size:12px;line-height:1.5;">
    Apenas se acredite el pago, vas a ver el pedido actualizado en “Mis pedidos”.
  </p>
</div>
