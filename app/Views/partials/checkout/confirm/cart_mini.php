<div class="checkout-summary-card">
  <div class="checkout-summary-card__header">
    <h3 class="checkout-summary-card__title">Tu carrito</h3>
    <a href="<?= url('/carrito') ?>" class="checkout-summary-card__link">Editar</a>
  </div>
  <div class="checkout-cart-mini">
    <?php foreach ($effectiveItems as $it): ?>
      <div class="checkout-cart-mini__item">
        <img src="<?= e($it['thumb'] ?? '') ?>" class="checkout-cart-mini__thumb" alt="" loading="lazy">
        <div class="checkout-cart-mini__body">
          <div class="checkout-cart-mini__name"><?= e($it['name'] ?? '') ?></div>
          <?php if (!empty($it['variant_label'])): ?>
            <div class="checkout-cart-mini__variant"><?= e((string)$it['variant_label']) ?></div>
          <?php endif; ?>
          <div class="checkout-cart-mini__meta"><?= (int)($it['qty'] ?? 0) ?> - <?= money_ar((int)($it['unit_price_effective'] ?? $it['price'] ?? 0)) ?></div>
        </div>
        <div class="checkout-cart-mini__price"><?= money_ar((int)($it['line_total_effective'] ?? $it['line_total'] ?? 0)) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
