<section data-checkout-step="2" class="checkout-step checkout-step--shipping <?= ((int)($currentStep ?? 1) === 2) ?'' : 'hidden' ?>">
  <div
    class="checkout-step__panel checkout-shipping-step"
    data-checkout-shipping-root
    data-shipping-calc-url="<?= e(url('/checkout/envio/calcular')) ?>"
    data-shipping-select-url="<?= e(url('/checkout/envio/seleccionar')) ?>"
  >
    <input type="hidden" name="shipping_method" value="<?= e((string)($shippingMethod ?? '')) ?>">
    <input type="hidden" name="shipping_agency_id" value="<?= e((string)($shippingAgencyId ?? '')) ?>">

    <div id="checkout-shipping-box" class="checkout-shipping-step__results" data-shipping-results data-autoload-shipping="1">
      <div class="checkout-placeholder-card">
        Cargando opciones de envio...
      </div>
    </div>

    <div class="checkout-step__actions checkout-step__actions--sticky-mobile checkout-step__actions--desktop-inline checkout-step__actions--end">
      <?php $hasShippingSnapshot = !empty($hasShipSnapshot ?? false); ?>
      <a
        href="<?= base_url('/checkout?step=3') ?>"
        data-checkout-step2-continue
        class="v2-btn v2-btn--primary <?= $hasShippingSnapshot ?'' : 'is-disabled' ?>"
        <?= $hasShippingSnapshot ? '' : 'aria-disabled="true"' ?>
      >
        Continuar
      </a>
    </div>
  </div>
</section>
