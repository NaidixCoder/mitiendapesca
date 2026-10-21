<?php
$email = (string)($email ?? '');
$preheader = (string)($preheader ?? '');
$body_html = (string)($body_html ?? '');
$cta_label = (string)($cta_label ?? 'Ver');
$cta_url = (string)($cta_url ?? url('/'));
$unsubscribe_url = (string)($unsubscribe_url ?? '');
?>

<?php if ($preheader !== ''): ?>
  <p style="margin:0 0 10px;color:#6b7280;font-size:12px;"><?= e($preheader) ?></p>
<?php endif; ?>

<div style="margin:0 0 12px;">
  <?= $body_html ?>
</div>

<?php if ($cta_url !== ''): ?>
  <p style="margin:0 0 12px;">
    <a href="<?= e($cta_url) ?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;"><?= e($cta_label ?: 'Ver') ?></a>
  </p>
<?php endif; ?>

<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />
<p style="margin:0;color:#6b7280;font-size:12px;">Si no querés recibir más emails, podés darte de baja en un clic: <a href="<?= e($unsubscribe_url) ?>">darse de baja</a>.</p>