<?php
/**
 * Checkout - Estado vacio de direccion
 * El template padre ya controla `empty($addresses)`.
 */
?>
<div class="checkout-step__panel checkout-address-inline">
  <div class="checkout-address-inline__empty">
    <div class="checkout-address-inline__illustration" aria-hidden="true">
      <svg viewBox="0 0 240 180" class="checkout-address-inline__art">
        <defs>
          <linearGradient id="checkoutAddressHouse" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#8BE1C8" />
            <stop offset="100%" stop-color="#49C0A7" />
          </linearGradient>
          <linearGradient id="checkoutAddressRoof" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#0AA889" />
            <stop offset="100%" stop-color="#097C71" />
          </linearGradient>
        </defs>
        <ellipse cx="120" cy="146" rx="82" ry="18" fill="#E7F5F2" />
        <path d="M56 128c-14-12-23-30-23-50 0-38 31-69 69-69 27 0 50 14 62 36 5-2 11-3 17-3 24 0 43 19 43 43 0 12-5 23-13 31 6 7 10 16 10 27H56c0-6 0-10 0-15z" fill="#F6FBFA" />
        <circle cx="133" cy="44" r="15" fill="#FDB84F" />
        <path d="M133 24c-10 0-19 8-19 18 0 13 19 30 19 30s19-17 19-30c0-10-9-18-19-18zm0 25a7 7 0 1 1 0-14 7 7 0 0 1 0 14z" fill="#FDB84F" />
        <path d="M70 103l50-39 50 39c3 2 7 2 10-1 2-3 2-7-1-10L124 49c-2-2-6-2-8 0L61 92c-3 3-3 7-1 10 3 3 7 3 10 1z" fill="url(#checkoutAddressRoof)" />
        <rect x="82" y="98" width="76" height="48" rx="4" fill="url(#checkoutAddressHouse)" />
        <rect x="112" y="111" width="17" height="35" rx="6" fill="#16586A" />
        <rect x="118" y="112" width="5" height="12" rx="2.5" fill="#0E7A83" />
        <rect x="114" y="91" width="11" height="10" rx="4" fill="#F0FCF8" />
        <path d="M56 144c0-12 10-22 22-22 8 0 15 4 19 11 3-2 7-4 12-4 10 0 18 8 18 18H56v-3z" fill="#BCEBE3" />
        <path d="M146 146c0-11 9-20 20-20 7 0 13 3 17 9 3-2 6-3 10-3 9 0 16 7 16 16h-63v-2z" fill="#A7DED4" />
      </svg>
    </div>

    <div class="checkout-address-inline__content">
      <h2 class="checkout-address-inline__title">Agrega tu primera direccion de entrega</h2>
      <p class="checkout-address-inline__text">Necesitamos una direccion para enviar tu pedido.</p>
    </div>
  </div>

  <div class="checkout-address-inline__actions">
    <button type="button" data-action="checkout-create-address" class="v2-btn v2-btn--primary checkout-address-inline__cta">
      + Agregar direccion
    </button>
  </div>
</div>
