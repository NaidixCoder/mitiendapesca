<?php
  $mpResultId = 'mp-result';
?>

<section id="<?= e($mpResultId) ?>" class="checkout-card__payment-result ui-is-hidden" data-state="" aria-live="polite">
  <div class="checkout-card__payment-result-head">
    <div class="checkout-card__payment-result-icon" data-role="mp-result-icon" aria-hidden="true"></div>
    <div class="checkout-card__payment-result-copy">
      <div id="mp-result-badge" class="checkout-card__payment-result-badge"></div>
      <div id="mp-result-title" class="checkout-card__payment-result-title"></div>
      <div id="mp-result-text" class="checkout-card__payment-result-text"></div>
    </div>
  </div>

  <div class="checkout-card__payment-result-body" id="mp-result-body"></div>

  <div class="checkout-card__payment-result-actions">
    <a id="mp-result-primary-link" class="v2-btn v2-btn--brand ui-is-hidden" href="#" rel="nofollow"></a>
    <button id="mp-result-primary-button" type="button" class="v2-btn v2-btn--brand ui-is-hidden"></button>
    <a id="mp-result-secondary-link" class="v2-btn v2-btn--ghost ui-is-hidden" href="#" rel="nofollow"></a>
    <button id="mp-result-secondary-button" type="button" class="v2-btn v2-btn--ghost ui-is-hidden"></button>
  </div>
</section>
