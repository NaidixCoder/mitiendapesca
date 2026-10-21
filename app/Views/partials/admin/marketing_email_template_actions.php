<?php /** @var array<string,mixed> $t */ ?>
<div class="av3-email-template-actions">
  <a class="av3-email-textlink" href="<?= e((string)$t['show_url']) ?>">Ver</a>
  <a class="av3-email-textlink" href="<?= e((string)$t['use_url']) ?>">Usar</a>
  <form class="av3-email-inline-form" method="post" action="<?= url('/admin/marketing/email/templates/delete') ?>" data-confirm-delete-template>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
    <button type="submit" class="av3-email-delete">Eliminar</button>
  </form>
</div>
