<?php
$subtotal = (int)($subtotal ?? 0);
$freeMin = (int)($free_min_amount ?? 0);
$options = is_array($options ?? null) ? $options : [];
$pickup = is_array($pickup ?? null) ? $pickup : [];
$addressSummary = is_array($address_summary ?? null) ? $address_summary : [];

$pickupMessage = trim((string)($pickup['message'] ?? 'Elegi una sucursal para retiro.'));
$pickupLocalities = is_array($pickup['localities'] ?? null) ? $pickup['localities'] : [];
$pickupAgencies = is_array($pickup['agencies'] ?? null) ? $pickup['agencies'] : [];

$addressLabel = trim((string)($addressSummary['label'] ?? ''));
$addressLine1 = trim((string)($addressSummary['line1'] ?? ''));
$addressLine2 = trim((string)($addressSummary['line2'] ?? ''));
$selectedOptionId = trim((string)($selected_option_id ?? ''));

function money_ar(int $value): string
{
    return '$ ' . number_format((float)$value, 0, ',', '.');
}

function shipping_ui_text(string $value): string
{
    return strtr($value, [
        'ÃƒÆ’Ã‚Â¡' => 'a',
        'ÃƒÆ’Ã‚Â©' => 'e',
        'ÃƒÆ’Ã‚Â­' => 'i',
        'ÃƒÆ’Ã‚Â³' => 'o',
        'ÃƒÆ’Ã‚Âº' => 'u',
        'ÃƒÂ¡' => 'a',
        'ÃƒÂ©' => 'e',
        'ÃƒÂ­' => 'i',
        'ÃƒÂ³' => 'o',
        'ÃƒÂº' => 'u',
        'ÃƒÂ±' => 'n',
        'Ãƒâ€˜' => 'N',
        'Ã‚Â¡' => '',
        'Ã‚Â¿' => '',
        'Ã¢â‚¬â€œ' => '-',
        'Ã¢â‚¬â€' => '-',
        'Ã‚Â·' => ' - ',
    ]);
}

function shipping_service_tier(string $serviceName, string $productName, string $serviceCode, array $meta = []): string
{
    $haystack = mb_strtolower(trim(implode(' ', array_filter([
        shipping_ui_text($serviceName),
        shipping_ui_text($productName),
        shipping_ui_text((string)($meta['product_type'] ?? '')),
        $serviceCode,
    ]))), 'UTF-8');

    if ($haystack === '') {
        return '';
    }

    if (str_contains($haystack, 'express') || str_contains($haystack, 'expreso')) {
        return 'Express';
    }

    if (str_contains($haystack, 'clasico') || str_contains($haystack, 'clasic') || str_contains($haystack, 'standard') || str_contains($haystack, 'std')) {
        return 'Clasico';
    }

    return '';
}

function shipping_eta_label(array $meta): string
{
    $min = isset($meta['delivery_time_min']) && $meta['delivery_time_min'] !== null ? (int)$meta['delivery_time_min'] : null;
    $max = isset($meta['delivery_time_max']) && $meta['delivery_time_max'] !== null ? (int)$meta['delivery_time_max'] : null;

    if ($min !== null && $max !== null) {
        if ($min === $max) {
            return 'Llega en ' . $min . ' ' . ($min === 1 ? 'dia' : 'dias');
        }

        return 'Llega de ' . $min . ' a ' . $max . ' dias hábiles';
    }

    if ($min !== null) {
        return 'Llega desde ' . $min . ' ' . ($min === 1 ? 'dia' : 'dias');
    }

    if ($max !== null) {
        return 'Llega hasta ' . $max . ' dias';
    }

    return '';
}

?>

<div class="checkout-shipping-results" data-checkout-shipping-box>
  <div class="checkout-shipping-results__hero">
    <div class="checkout-shipping-results__title">Elegi como recibir tu pedido</div>

    <?php if ($freeMin > 0 && $subtotal < $freeMin): ?>
      <div class="checkout-shipping-results__promo">Envio gratis desde <?= money_ar($freeMin) ?></div>
    <?php endif; ?>
  </div>

  <?php if (empty($options)): ?>
    <div class="checkout-shipping-results__empty">No hay opciones disponibles para este destino.</div>
  <?php else: ?>
    <div class="checkout-shipping-results__list">
      <?php foreach ($options as $opt): ?>
        <?php
        if (!is_array($opt)) {
            continue;
        }

        $mode = strtoupper((string)($opt['mode'] ?? 'D'));
        $serviceName = shipping_ui_text((string)($opt['service_name'] ?? 'Opcion'));
        $serviceCode = (string)($opt['service_code'] ?? '');
        $meta = is_array($opt['meta'] ?? null) ? $opt['meta'] : [];
        $cost = (int)($opt['cost'] ?? 0);
        $optionId = (string)($opt['option_key'] ?? ($mode . '|' . $serviceCode));
        $checked = ($selectedOptionId !== '' && $optionId === $selectedOptionId);
        $productName = shipping_ui_text(trim((string)($meta['product_name'] ?? '')));
        $serviceTier = shipping_service_tier($serviceName, $productName, $serviceCode, $meta);
        $etaLabel = shipping_eta_label($meta);
        $showPickupFields = ($mode === 'S' && !empty($pickupAgencies));
        $isLocalPickup = ($mode === 'L');
        $titleBase = $isLocalPickup ? 'Retirar en el local ' : (($mode === 'S') ? 'Retirar en sucursal' : 'A domicilio');
        $detailLine = $isLocalPickup
            ? shipping_ui_text(trim((string)($meta['local_notes'] ?? 'Retira tu compra por nuestro local.')))
            : (($mode === 'S') ? ($productName !== '' ? $productName : $serviceName) : trim($addressLine1 . ($addressLine2 !== '' ? ' - ' . $addressLine2 : '')));
        $metaLine = $isLocalPickup
            ? shipping_ui_text(trim((string)($meta['local_address'] ?? $serviceName)))
            : (($mode === 'S') ? shipping_ui_text($pickupMessage) : ($addressLabel !== '' ? $addressLabel : 'Domicilio guardado'));
        ?>

        <label
          class="checkout-shipping-card"
          data-shipping-card
          tabindex="0"
          role="radio"
          aria-checked="<?= $checked ? 'true' : 'false' ?>"
        >
          <input
            type="radio"
            name="shipping_option"
            value="<?= e($optionId) ?>"
            class="checkout-shipping-card__radio"
            data-mode="<?= e($mode) ?>"
            data-requires-agency="<?= $mode === 'S' ? '1' : '0' ?>"
            data-service-code="<?= e($serviceCode) ?>"
            data-service-name="<?= e($serviceName) ?>"
            data-cost="<?= $cost ?>"
            <?= $checked ? 'checked' : '' ?>
          >

          <div class="checkout-shipping-card__panel">
            <div class="checkout-shipping-card__summary">
              <div class="checkout-shipping-card__summary-copy">
                <div class="checkout-shipping-card__title">
                  <?= e($titleBase) ?>
                  <?php if ($serviceTier !== ''): ?>
                    <span class="checkout-shipping-card__title-tier<?= $serviceTier ==='Express' ? ' checkout-shipping-card__title-tier--express' : '' ?>"><?= e($serviceTier) ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($mode === 'S' || $isLocalPickup): ?>
                  <div class="checkout-shipping-card__accent" <?= $isLocalPickup ? 'data-shipping-agency-address-text' : '' ?>><?= e($metaLine) ?></div>
                <?php endif; ?>
                <?php if ($etaLabel !== ''): ?>
                  <div class="checkout-shipping-card__eta"><?= e($etaLabel) ?></div>
                <?php endif; ?>
                <div class="checkout-shipping-card__service" <?= $mode === 'S' ? 'data-shipping-agency-address data-shipping-agency-address-text' : '' ?>>
                  <?= e($detailLine) ?>
                </div>
              </div>

              <div class="checkout-shipping-card__summary-side">
                <div class="checkout-shipping-card__price">
                  <?= $cost > 0 ? money_ar($cost) : '<span class="checkout-shipping-card__free">Gratis</span>' ?>
                </div>
              </div>
            </div>

            <?php if ($mode === 'S' || $mode === 'D' || $isLocalPickup): ?>
              <div class="checkout-shipping-card__footer">
                <?php if ($mode === 'S'): ?>
                  <button
                    type="button"
                    class="checkout-shipping-card__footer-toggle"
                    data-shipping-pickup-toggle
                    aria-expanded="false"
                  >
                    <span class="checkout-shipping-card__footer-copy">Ver punto en el mapa o elegir otro</span>
                    <span class="checkout-shipping-card__chevron"><?= ui_icon('chevron-right-solid') ?></span>
                  </button>
                <?php elseif ($isLocalPickup): ?>
                  <button
                    type="button"
                    class="checkout-shipping-card__footer-toggle"
                    data-shipping-pickup-toggle
                    aria-expanded="false"
                  >
                    <span class="checkout-shipping-card__footer-copy">Ver local en el mapa</span>
                    <span class="checkout-shipping-card__chevron"><?= ui_icon('chevron-right-solid') ?></span>
                  </button>
                <?php else: ?>
                  <button type="button" class="checkout-shipping-card__footer-button" onclick="window.location.href='<?= e(base_url('/checkout?step=1')) ?>'">
                    Modificar domicilio o elegir otro
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($showPickupFields): ?>
                <div class="checkout-shipping-card__pickup-fields ui-is-hidden" data-shipping-pickup-panel>
                <?php if (!empty($pickupLocalities)): ?>
                  <label class="checkout-field checkout-field--compact">
                    <span class="checkout-field__label">Localidad</span>
                    <select class="checkout-field__control" data-shipping-locality-select>
                      <option value="">-- Elegi una localidad --</option>
                      <?php foreach ($pickupLocalities as $locality): ?>
                        <?php if (!is_array($locality)) continue; ?>
                        <option value="<?= e((string)($locality['value'] ?? '')) ?>" <?= !empty($locality['selected']) ? 'selected' : '' ?>>
                          <?= e((string)($locality['label'] ?? '')) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                <?php endif; ?>

                <label class="checkout-field checkout-field--compact">
                  <span class="checkout-field__label">Sucursal</span>
                  <select name="shipping_agency" class="checkout-field__control" data-shipping-agency-select>
                    <option value="">-- Elegi una sucursal --</option>
                    <?php foreach ($pickupAgencies as $agency): ?>
                      <?php if (!is_array($agency)) continue; ?>
                      <option
                        value="<?= e((string)($agency['id'] ?? '')) ?>"
                        data-name="<?= e(shipping_ui_text((string)($agency['name'] ?? 'Sucursal'))) ?>"
                        data-address="<?= e(shipping_ui_text((string)($agency['address'] ?? ''))) ?>"
                        data-locality="<?= e(shipping_ui_text((string)($agency['locality'] ?? ''))) ?>"
                        <?= !empty($agency['selected']) ? 'selected' : '' ?>
                      >
                        <?= e(shipping_ui_text((string)($agency['name'] ?? 'Sucursal'))) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </label>

                <div class="checkout-field__error ui-is-hidden" data-shipping-agency-required>Elegi una sucursal para continuar.</div>

                <div class="checkout-map ui-is-hidden" data-shipping-agency-map>
                  <div class="checkout-map__frame">
                    <iframe
                      title="Mapa de la sucursal"
                      class="checkout-map__iframe"
                      src=""
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                  </div>
                </div>
                </div>
            <?php endif; ?>

            <?php if ($isLocalPickup): ?>
              <div class="checkout-shipping-card__pickup-fields ui-is-hidden" data-shipping-pickup-panel>
                <div
                  class="checkout-map ui-is-hidden"
                  data-shipping-agency-map
                  data-map-url="<?= e((string)($meta['local_maps_url'] ?? '')) ?>"
                  data-map-place-id="<?= e((string)($meta['local_place_id'] ?? '')) ?>"
                  data-map-lat="<?= e((string)($meta['local_map_lat'] ?? '')) ?>"
                  data-map-lng="<?= e((string)($meta['local_map_lng'] ?? '')) ?>"
                  data-map-zoom="<?= e((string)($meta['local_map_zoom'] ?? '17')) ?>"
                >
                  <div class="checkout-map__frame">
                    <iframe
                      title="Mapa del local"
                      class="checkout-map__iframe"
                      src=""
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
