<?php
/** @var array<string,mixed> $user */
/** @var int $orderId */
/** @var string $carrier */
/** @var string $service */
/** @var string $trackingNumber */
/** @var string $trackingUrl */
?>
<h2 style="margin:0 0 12px 0;">🚚 ¡Tu pedido fue despachado!</h2>

<p style="margin:0 0 10px 0;">
  Hola <?= e((string)($user['name'] ?? '')) ?>, te avisamos que el <b>pedido #<?= (int)$orderId ?></b> ya fue despachado.
</p>

<div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin:14px 0;">
  <p style="margin:0 0 6px 0;"><b>Transportista:</b> <?= e($carrier) ?><?= $service ? ' – '.e($service) : '' ?></p>

  <?php if ($trackingNumber): ?>
    <p style="margin:0 0 6px 0;"><b>Número de seguimiento:</b> <?= e($trackingNumber) ?></p>
  <?php endif; ?>

  <?php if ($trackingUrl): ?>
    <p style="margin:0;">
      <a href="<?= e($trackingUrl) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">
        Ver seguimiento
      </a>
    </p>
  <?php else: ?>
    <p style="margin:0;color:#6b7280;">
      Te compartiremos el link de seguimiento apenas esté disponible.
    </p>
  <?php endif; ?>
</div>

<p style="margin:0;color:#6b7280;font-size:13px;">
  Tip: guardá este email para consultar el seguimiento cuando quieras.
</p>
