<section data-checkout-step="3" class="checkout-step checkout-step--payment <?= ((int)($currentStep ?? 1) === 3) ?'' : 'hidden' ?>">
  <?php
    $paymentCards = is_array($paymentOptionCards ?? null) ? $paymentOptionCards : [];
    $fmtMoney = static fn (int $value): string => '$ '.number_format((float)$value, 0, ',', '.');
  ?>
  <div class="checkout-step__panel checkout-payment-step">
    <div class="checkout-step__header">
      <div class="checkout-step__heading">
        <div class="checkout-step__index">3</div>
        <div class="checkout-step__heading-copy">
          <h2 class="checkout-step__title">Elegi el metodo de pago</h2>
          <p class="checkout-step__subtitle">Elegi como queres pagar. Podes cambiar antes de iniciar el pago.</p>
        </div>
      </div>
    </div>

    <div class="checkout-payment-step__methods">
      <?php foreach (['transferencia', 'tarjeta_credito', 'tarjeta_debito'] as $methodKey): ?>
        <?php if (empty($paymentCards[$methodKey]) || !is_array($paymentCards[$methodKey])) continue; ?>
        <?php $card = $paymentCards[$methodKey]; ?>
        <?php $badgeVariant = trim((string)($card['badgeVariant'] ?? '')); ?>
        <button
          type="button"
          class="checkout-payment-card checkout-payment-card--<?= e($methodKey) ?>"
          data-payment-method="<?= e((string)($card['method'] ?? '')) ?>"
          <?= $methodKey === 'transferencia' ? 'data-payment-default="1"' : '' ?>
        >
          <div class="checkout-payment-card__main">
            <?php if (trim((string)($card['icon'] ?? '')) !== ''): ?>
              <span class="checkout-payment-card__icon" aria-hidden="true">
                <?= ui_icon((string)$card['icon'], 'checkout-payment-card__icon-svg') ?>
              </span>
            <?php endif; ?>

            <div class="checkout-payment-card__copy-block">
              <div class="checkout-payment-card__title"><?= e((string)($card['title'] ?? '')) ?></div>
              <div class="checkout-payment-card__copy"><?= e((string)($card['subtitle'] ?? '')) ?></div>
              <div class="checkout-payment-card__meta">
                <div class="checkout-payment-card__amount"><?= e($fmtMoney((int)($card['total'] ?? 0))) ?></div>
                <?php if (trim((string)($card['badge'] ?? '')) !== ''): ?>
                  <div class="checkout-payment-card__badge<?= $badgeVariant !=='' ? ' checkout-payment-card__badge--'.e($badgeVariant) : '' ?>"><?= e((string)$card['badge']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </button>
      <?php endforeach; ?>
    </div>

    <p id="checkout-method-error" class="checkout-step__error ui-is-hidden">Selecciona un metodo de pago para continuar.</p>

    <div class="checkout-step__actions checkout-step__actions--sticky-mobile checkout-step__actions--split">
      <button type="button" class="v2-btn v2-btn--ghost" data-checkout-prev="2">Volver</button>
      <button type="button" class="v2-btn v2-btn--primary" data-checkout-next="4">Continuar</button>
    </div>
  </div>
</section>
