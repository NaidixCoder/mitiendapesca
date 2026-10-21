<div id="tp-toast-host" class="tp-toast-host" aria-live="polite" aria-atomic="true"></div>

<template id="tp-toast-template">
  <div class="tp-toast" role="status">
    <div class="tp-toast__icon" data-toast-icon aria-hidden="true">
      <span class="tp-toast__icon-glyph" data-toast-icon-success><?= ui_icon('check-line') ?></span>
      <span class="tp-toast__icon-glyph ui-is-hidden" data-toast-icon-error><?= ui_icon('triangle-exclamation-solid') ?></span>
    </div>
    <div class="tp-toast__body">
      <div class="tp-toast__message" data-toast-message></div>
      <div class="tp-toast__detail ui-is-hidden" data-toast-detail></div>
    </div>
    <a class="tp-toast__action ui-is-hidden" data-toast-action></a>
    <button type="button" class="tp-toast__close" data-toast-close aria-label="Cerrar">
      <?= ui_icon('close') ?>
    </button>
  </div>
</template>
