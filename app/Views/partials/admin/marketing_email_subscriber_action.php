<?php /** @var array<string,mixed> $r */ ?>
<form class="av3-email-action" method="post" action="<?= e((string)($links['set_status'] ?? url('/admin/marketing/email/suscriptores/estado'))) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
  <input type="hidden" name="status" value="<?= !empty($r['can_unsubscribe']) ? 'unsubscribed' : 'subscribed' ?>">
  <button class="av3-ui-btn" type="submit"><?= !empty($r['can_unsubscribe']) ? 'Dar de baja' : 'Reactivar' ?></button>
</form>
