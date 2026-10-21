<?php /** @var array<string,mixed> $template_show */ ?>
<section class="av3-email-panel av3-email-template-preview" aria-labelledby="email-template-preview-title">
  <header class="av3-email-panel__header">
    <div><h2 id="email-template-preview-title">Vista previa</h2><p><?= e((string)($template_show['preview_hint'] ?? '')) ?></p></div>
  </header>
  <div class="av3-email-template-preview__frame">
    <iframe title="Vista previa de plantilla" sandbox="" referrerpolicy="no-referrer" loading="lazy" srcdoc="<?= e((string)($template_show['html'] ?? '')) ?>"></iframe>
  </div>
</section>
