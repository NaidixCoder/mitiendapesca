<?php /** @var array $addresses */ ?>
<section data-checkout-step="1" class="checkout-step checkout-step--address <?= ((int)($currentStep ?? 1) === 1) ?'' : 'hidden' ?>">
  <div class="checkout-step__panel checkout-address-step">
    <div class="checkout-step__header">
      <div class="checkout-step__heading">
        <div class="checkout-step__index">1</div>
        <div class="checkout-step__heading-copy">
          <h2 class="checkout-step__title">Elige tu direccion</h2>
          <p class="checkout-step__subtitle">Selecciona donde quieres recibir tu compra o administra tus direcciones guardadas.</p>
        </div>
      </div>
    </div>

    <div class="checkout-address-step__list<?= count($addresses) === 1 ?' checkout-address-step__list--single' : '' ?>">
      <?php foreach ($addresses as $a):
        $aid = (int)($a['id'] ?? 0);
        $selectedAddressId = (int)($selectedAddressId ?? 0);
        $isSelected = ($selectedAddressId > 0)
          ? ($aid === $selectedAddressId)
          : ($defaultId && $aid === (int)$defaultId);
        $title = !empty($a['is_default']) ? 'Direccion principal' : (($a['label'] ?? '') ?: 'Direccion');
      ?>
        <label class="checkout-address-card<?= $isSelected ?' is-selected' : '' ?>">
          <input
            type="radio"
            name="address_id"
            value="<?= $aid ?>"
            class="checkout-address-card__input"
            required
            <?= $isSelected ? 'checked' : '' ?>
            data-phone="<?= e((string)($a['phone'] ?? '')) ?>"
            data-city="<?= e((string)($a['city'] ?? '')) ?>"
            data-state="<?= e((string)($a['state'] ?? '')) ?>"
            data-zip="<?= e((string)($a['zip'] ?? '')) ?>"
            data-full-name="<?= e((string)($a['full_name'] ?? '')) ?>"
            data-street="<?= e((string)($a['street'] ?? '')) ?>"
            data-number="<?= e((string)($a['number'] ?? '')) ?>"
            data-floor="<?= e((string)($a['floor'] ?? '')) ?>"
            data-apartment="<?= e((string)($a['apartment'] ?? '')) ?>"
            data-notes="<?= e((string)($a['notes'] ?? '')) ?>"
            data-edit-url="<?= e(url('/cuenta/direcciones/editar?id=' . $aid)) ?>"
          >
          <div class="checkout-address-card__header">
            <div class="checkout-address-card__header-main">
              <span class="checkout-address-card__status" aria-hidden="true">
                <svg class="checkout-address-card__status-icon checkout-address-card__status-icon--inactive" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M528 320C528 205.1 434.9 112 320 112C205.1 112 112 205.1 112 320C112 434.9 205.1 528 320 528C434.9 528 528 434.9 528 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"/></svg>
                <svg class="checkout-address-card__status-icon checkout-address-card__status-icon--active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 576C178.6 576 64 461.4 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576zM438 209.7C427.3 201.9 412.3 204.3 404.5 215L285.1 379.2L233 327.1C223.6 317.7 208.4 317.7 199.1 327.1C189.8 336.5 189.7 351.7 199.1 361L271.1 433C276.1 438 282.9 440.5 289.9 440C296.9 439.5 303.3 435.9 307.4 430.2L443.3 243.2C451.1 232.5 448.7 217.5 438 209.7z"/></svg>
              </span>
              <span class="checkout-address-card__title"><?= e($title) ?></span>
            </div>
            <button type="button" data-action="checkout-edit-address" class="checkout-address-card__edit">Editar</button>
          </div>

          <div class="checkout-address-card__body">
            <div class="checkout-address-card__name"><?= e($a['full_name'] ?? '') ?></div>
            <div class="checkout-address-card__line">
              <?= e(($a['street'] ?? '') . ' ' . ($a['number'] ?? '')) ?>
            </div>
            <div class="checkout-address-card__line">
              <?= e(($a['city'] ?? '') . ', ' . ($a['state'] ?? '') . ' (' . ($a['zip'] ?? '') . ')') ?>
            </div>

            <div class="checkout-address-card__detail-row">
              <?= ui_icon('phone', 'checkout-address-card__detail-icon ui-icon--primary', ['aria-hidden' => 'true']) ?>
              <span class="checkout-address-card__detail-text"><?= e($a['phone'] ?? '') ?></span>
            </div>

            <?php if (!empty($a['notes'])): ?>
              <div class="checkout-address-card__detail-row">
                <?= ui_icon('note-edit', 'checkout-address-card__detail-icon ui-icon--primary', ['aria-hidden' => 'true']) ?>
                <span class="checkout-address-card__detail-text">Nota: <?= e($a['notes']) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </label>
      <?php endforeach; ?>

      <?php if (count($addresses) === 1): ?>
        <button type="button" data-action="checkout-create-address" class="checkout-address-card checkout-address-card--cta">
          <span class="checkout-address-card__cta-icon" aria-hidden="true"><?= ui_icon('plus-solid', 'checkout-address-card__cta-icon-svg') ?></span>
          <span class="checkout-address-card__cta-title">Agregar nueva direccion</span>
          <span class="checkout-address-card__cta-text">Carga otra direccion para elegir rápido entre varias opciones de entrega.</span>
        </button>
      <?php endif; ?>
    </div>

    <p id="checkout-address-error" class="checkout-step__error ui-is-hidden">Selecciona una direccion para continuar.</p>
    <a id="checkout-address-fixlink" href="#" class="checkout-step__secondary-link ui-is-hidden">
      Editar direccion
    </a>

    <div class="checkout-step__actions checkout-step__actions--sticky-mobile checkout-step__actions--desktop-inline checkout-step__actions--end">
      <button type="button" class="v2-btn v2-btn--primary" data-checkout-next="2">
        Continuar
      </button>
    </div>
  </div>
</section>
