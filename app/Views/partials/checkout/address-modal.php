<?php
$provinces = is_array($provinces ?? null) ? $provinces : [];
$profilePhone = trim((string)($profilePhone ?? ''));
$createUrl = trim((string)($createUrl ?? '')) ?: url('/cuenta/direcciones/crear');
$updateUrl = trim((string)($updateUrl ?? '')) ?: url('/cuenta/direcciones/guardar');
$returnUrl = trim((string)($returnUrl ?? '')) ?: url('/checkout?step=2');
$submitLabel = trim((string)($submitLabel ?? '')) ?: 'Guardar y continuar';
?>
<!-- Modal Direccion (Checkout) -->
<div id="checkout-address-modal" class="checkout-address-modal ui-is-hidden">
  <div data-modal-backdrop class="checkout-address-modal__backdrop"></div>

  <div class="checkout-address-modal__viewport">
    <div data-modal-panel class="checkout-address-modal__panel">
      <div class="checkout-address-modal__hero">
        <div class="checkout-address-modal__hero-head">
          <span class="checkout-address-modal__hero-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 22s7-6.2 7-13a7 7 0 1 0-14 0c0 6.8 7 13 7 13Zm0-9.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z"/></svg>
          </span>
          <h3 class="checkout-address-modal__title">Direccion de entrega</h3>
          <button type="button" data-modal-close class="checkout-address-modal__close" aria-label="Cerrar">×</button>
        </div>

        <p data-modal-message class="checkout-address-modal__subtitle">Ingresa la direccion donde queres recibir tu pedido.</p>

        <div class="checkout-address-modal__benefits">
          <div class="checkout-address-modal__benefit">
            <span class="checkout-address-modal__benefit-icon" aria-hidden="true">
              <?= ui_icon('check', 'checkout-address-modal__benefit-icon-svg') ?>
            </span>
            <span>Envios a todo el pais</span>
          </div>

        </div>
      </div>

      <form
        data-address-form
        data-mode="edit"
        data-create-url="<?= e($createUrl) ?>"
        data-update-url="<?= e($updateUrl) ?>"
        data-return-url="<?= e($returnUrl) ?>"
        data-profile-phone="<?= e($profilePhone) ?>"
        class="checkout-form checkout-address-modal__form"
      >
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="">

        <div class="checkout-address-modal__card">
          <div class="checkout-address-modal__section-title">Contacto</div>

          <div class="checkout-address-modal__stack">
            <label class="checkout-field">
              <span class="checkout-field__label">Nombre y apellido</span>
              <input id="full_name" name="full_name" required class="checkout-field__control" />
            </label>

            <label class="checkout-field">
              <span class="checkout-field__label">Telefono</span>
              <input id="phone" name="phone" required class="checkout-field__control" />
            </label>
          </div>

          <div class="checkout-address-modal__section-title checkout-address-modal__section-title--sub">Direccion</div>

          <div class="checkout-form__grid checkout-form__grid--two">
            <label class="checkout-field">
              <span class="checkout-field__label">Calle</span>
              <input id="street" name="street" required class="checkout-field__control" />
            </label>

            <label class="checkout-field">
              <span class="checkout-field__label">Nro</span>
              <input id="number" name="number" required class="checkout-field__control" />
            </label>

            <label class="checkout-field">
              <span class="checkout-field__label">Piso (opcional)</span>
              <input id="floor" name="floor" class="checkout-field__control" />
            </label>

            <label class="checkout-field">
              <span class="checkout-field__label">Dpto (opcional)</span>
              <input id="apartment" name="apartment" class="checkout-field__control" />
            </label>

            <label class="checkout-field">
              <span class="checkout-field__label">Codigo Postal</span>
              <input id="zip" name="zip" required class="checkout-field__control" />
            </label>

            <label class="checkout-field">
              <span class="checkout-field__label">Localidad</span>
              <input id="city" name="city" required class="checkout-field__control" />
            </label>

            <label class="checkout-field checkout-field--full">
              <span class="checkout-field__label">Provincia</span>
              <select id="state" name="state" required class="checkout-field__control">
                <option value="" selected disabled>Selecciona una provincia</option>
                <?php foreach ($provinces as $p): ?>
                  <option value="<?= e((string)$p) ?>"><?= e((string)$p) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="checkout-field checkout-field--full">
              <span class="checkout-field__label">Notas para el repartidor</span>
              <textarea id="notes" name="notes" rows="5" placeholder="Ejemplo: Porton gris, timbre roto" class="checkout-field__control checkout-field__control--textarea checkout-field__control--notes"></textarea>
            </label>
          </div>
        </div>

        <div class="checkout-address-modal__actions">
          <button type="button" data-modal-close class="v2-btn v2-btn--ghost">Cancelar</button>
          <button type="submit" data-save-btn class="v2-btn v2-btn--primary"><?= e($submitLabel) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
