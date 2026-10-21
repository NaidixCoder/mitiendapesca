<?php
/** @var int $userId */
/** @var string $userEmail */
/** @var string $method */
/** @var int $step */
/** @var int $addressId */
/** @var string $reason */
/** @var string $url */
/** @var string $userAgent */
/** @var string $occurredAt */
?>
<div style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.55;font-size:14px;">
  <h1 style="margin:0 0 14px 0;font-size:20px;line-height:1.25;">Fallo del brick de Mercado Pago</h1>
  <p style="margin:0 0 16px 0;">El checkout mostró el fallback de Mercado Pago porque no pudo cargar el formulario embebido.</p>

  <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;">
    <tr><td style="padding:6px 0;font-weight:700;">Fecha</td><td style="padding:6px 0;"><?= e($occurredAt) ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">Usuario ID</td><td style="padding:6px 0;"><?= (int)$userId ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">Email</td><td style="padding:6px 0;"><?= e($userEmail !== '' ? $userEmail : '-') ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">Método</td><td style="padding:6px 0;"><?= e($method !== '' ? $method : '-') ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">Paso</td><td style="padding:6px 0;"><?= (int)$step ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">Dirección ID</td><td style="padding:6px 0;"><?= (int)$addressId ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">Motivo</td><td style="padding:6px 0;"><?= e($reason) ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">URL</td><td style="padding:6px 0;word-break:break-word;"><?= e($url !== '' ? $url : '-') ?></td></tr>
    <tr><td style="padding:6px 0;font-weight:700;">User-Agent</td><td style="padding:6px 0;word-break:break-word;"><?= e($userAgent !== '' ? $userAgent : '-') ?></td></tr>
  </table>
</div>
