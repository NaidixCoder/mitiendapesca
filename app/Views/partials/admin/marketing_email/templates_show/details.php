<?php /** @var array<string,mixed> $template_show */ ?>
<section class="av3-email-panel av3-email-template-details" aria-labelledby="email-template-info-title">
  <header class="av3-email-panel__header"><div><h2 id="email-template-info-title">Detalles de plantilla</h2><p><?= e((string)($template_show['mode_label'] ?? 'Visual')) ?> · <?= e((string)($template_show['created_at'] ?? '')) ?></p></div></header>
  <dl>
    <div><dt>Asunto sugerido</dt><dd><?= !empty($template_show['subject_empty']) ? 'Sin definir' : e((string)$template_show['subject']) ?></dd></div>
    <div><dt>Texto previo</dt><dd><?= !empty($template_show['preheader_empty']) ? 'Sin definir' : e((string)$template_show['preheader']) ?></dd></div>
  </dl>
  <div class="av3-email-template-details__actions">
    <form method="post" action="<?= e((string)($template_show['delete_url'] ?? '')) ?>" data-confirm-delete-template>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($template_show['id'] ?? 0) ?>">
      <button class="av3-ui-btn av3-email-delete" type="submit">Eliminar plantilla</button>
    </form>
    <p><?= e((string)($template_show['tip_text'] ?? '')) ?></p>
  </div>
</section>
