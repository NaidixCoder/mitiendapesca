<?php
// Variables: $confirmUrl, $unsubUrl
$confirmUrl = (string)($confirmUrl ?? '');
$unsubUrl = (string)($unsubUrl ?? '');
?>

<h2 style="margin:0 0 8px; font-size:18px;">Confirmá tu suscripción</h2>
<p style="margin:0 0 14px;">Para empezar a recibir ofertas y novedades (sin spam), confirmá tu email haciendo click:</p>

<p style="margin:18px 0;">
  <a href="<?= e($confirmUrl) ?>"
     style="display:inline-block; padding:10px 14px; border-radius:12px; background:#10b981; color:#fff; text-decoration:none; font-weight:600;">
    Confirmar suscripción
  </a>
</p>

<p style="margin:0 0 10px; color:#334155; font-size:13px;">
  Si no te suscribiste, podés ignorar este email.
</p>

<hr style="border:0; border-top:1px solid #e2e8f0; margin:18px 0;">

<p style="margin:0; color:#64748b; font-size:12px;">
  ¿No querés recibir más emails? <a href="<?= e($unsubUrl) ?>">Darte de baja</a>.
</p>
