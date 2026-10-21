<?php
/** @var array<string,mixed> $order */
$order = $order ?? [];
/** @var array<int, array<string,mixed>> $items */
$items = $items ?? [];
/** @var array<string,mixed>|null $shipment */
$shipment = $shipment ?? null;
/** @var array<string,string> $bank */
$bank = $bank ?? [];
/** @var array<int, array<string,mixed>> $receipts */
$receipts = $receipts ?? [];

/** @var array<string,mixed>|null $factura */
$factura = $factura ?? null;

/** @var array<int, array<string,mixed>> $comprobantes */
$comprobantes = $comprobantes ?? [];
/** @var string $seller_tax_regime */
$seller_tax_regime = strtolower(trim((string)($seller_tax_regime ?? 'monotributo')));
$sellerAllowsFacturaA = ($seller_tax_regime === 'responsable_inscripto');

/** @var array<string,mixed>|null $fiscal_request */
$fiscal_request = $fiscal_request ?? null;
/** @var array<string,mixed>|null $fiscal_profile */
$fiscal_profile = $fiscal_profile ?? null;

$supportUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
?>

<section class="acct-page order-show-page">
  <div class="acct-page__inner">
    <div class="acct-page__stack">
      <a class="order-show-back-link" href="<?= e(url('/cuenta/pedidos')) ?>">
        <span aria-hidden="true"><?= ui_icon('chevron-left-solid') ?></span>
        <span>Volver a mis pedidos</span>
      </a>

      <div class="order-show-page__layout">
        <div class="order-show-page__main">
          <?php section('cuenta/pedidos/header', ['order' => $order, 'receipts' => $receipts, 'factura' => $factura]); ?>

          <?php if ($sellerAllowsFacturaA): ?>
            <?php
              $frStatus = is_array($fiscal_request) ? (string)($fiscal_request['status'] ?? '') : '';
              $frCuit = '';
              $frTax = 'responsable_inscripto';
              $fpStatus = is_array($fiscal_profile) ? (string)($fiscal_profile['validation_status'] ?? '') : '';
              if (is_array($fiscal_profile)) {
                $frCuit = (string)($fiscal_profile['cuit'] ?? '');
                $frTax = (string)($fiscal_profile['tax_condition'] ?? 'responsable_inscripto');
              }
              if (is_array($fiscal_request)) {
                $frCuit = (string)($fiscal_request['customer_cuit'] ?? $frCuit);
                $frTax = (string)($fiscal_request['customer_tax_condition'] ?? $frTax);
              }
              $showForm = ($frStatus === '' || $frStatus === 'invalid' || (string)($_GET['fa'] ?? '') === '1');
            ?>

            <section class="order-show-card">
              <header class="order-show-card__head">
                <div>
                  <h2 class="order-show-card__title">Factura A (opcional)</h2>
                  <p class="order-show-card__intro">Si necesitas Factura A, podes solicitarla para este pedido.</p>
                </div>
              </header>

              <div class="order-show-card__body">
                <?php if ($frStatus === 'pending'): ?>
                  <div class="order-show-alert order-show-alert--info">
                    <div class="order-show-alert__title">Solicitud recibida</div>
                    <div class="order-show-alert__text">Tu solicitud quedo registrada. <?php if ($fpStatus === 'valid'): ?>CUIT validado.<?php else: ?>Estamos validando tus datos.<?php endif; ?></div>
                  </div>
                <?php elseif ($frStatus === 'approved' || $frStatus === 'done'): ?>
                  <div class="order-show-alert order-show-alert--success">
                    <div class="order-show-alert__title">Solicitud aprobada</div>
                    <div class="order-show-alert__text">Estamos procesando tu Factura A. Vas a poder descargarla desde este pedido.</div>
                  </div>
                <?php else: ?>
                  <div class="order-show-copy">
                    <p>
                      La Factura A se solicita <span class="order-show-emphasis">despues de la compra</span>. Si este pedido ya tiene una Factura B emitida,
                      al aprobar la solicitud emitiremos una <span class="order-show-emphasis">Nota de Credito</span> y luego tu <span class="order-show-emphasis">Factura A</span>.
                    </p>
                  </div>

                  <?php if ($showForm): ?>
                    <form method="post" action="<?= e(url('/cuenta/pedidos/factura-a/solicitar')) ?>" data-factura-a-request class="order-show-form">
                      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="order_id" value="<?= e((string)($order['id'] ?? '')) ?>">

                      <div class="order-show-form__field">
                        <label class="order-show-form__label" for="tax_condition">Condicion frente al IVA</label>
                        <select id="tax_condition" name="tax_condition" class="order-show-form__control">
                          <option value="responsable_inscripto" <?= ($frTax === 'responsable_inscripto' ? 'selected' : '') ?>>Responsable inscripto</option>
                          <option value="consumidor_final" <?= ($frTax === 'consumidor_final' ? 'selected' : '') ?>>Consumidor final</option>
                          <option value="monotributo" <?= ($frTax === 'monotributo' ? 'selected' : '') ?>>Monotributo</option>
                        </select>
                        <div class="order-show-form__help">Para Factura A debe ser "Responsable inscripto".</div>
                      </div>

                      <div class="order-show-form__field">
                        <label class="order-show-form__label" for="cuit">CUIT</label>
                        <input id="cuit" name="cuit" class="order-show-form__control" inputmode="numeric" placeholder="11 digitos" value="<?= e($frCuit) ?>">
                        <div class="order-show-form__help">Sin guiones. Ej: 20361381417</div>
                      </div>

                      <div class="order-show-form__action">
                        <button type="submit" class="v2-btn v2-btn--primary">Solicitar</button>
                      </div>
                    </form>
                  <?php else: ?>
                    <a class="v2-btn v2-btn--secondary order-show-inline-cta" href="<?= e(url('/cuenta/pedidos/ver') . '?id=' . (string)($order['id'] ?? 0) . '&fa=1') ?>">Solicitar Factura A</a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php section('cuenta/pedidos/pago', ['order' => $order, 'bank' => $bank, 'receipts' => $receipts]); ?>

          <section id="productos" class="order-show-card">
            <header class="order-show-card__head">
              <div>
                <h2 class="order-show-card__title">
                  <span class="order-show-section-icon" aria-hidden="true"><?= ui_icon('bag-shopping-solid') ?></span>
                  <span>Productos</span>
                </h2>
                <p class="order-show-card__intro">Detalle de tu compra</p>
              </div>
            </header>

            <div class="order-show-card__body">
              <?php if (empty($items)): ?>
                <div class="order-show-empty">No hay items para mostrar.</div>
              <?php else: ?>
                <div class="order-show-products-list">
                  <?php foreach ($items as $idx => $it): ?>
                    <article class="order-show-product-card<?= $idx > 0 ?' order-show-product-card--with-separator' : '' ?>">
                      <div class="order-show-product-card__top">
                        <div class="order-show-product-card__media">
                          <img src="<?= e(product_cover_url(['image_url' => (string)($it['image_url'] ?? ''), 'sku' => (string)($it['sku'] ?? ''), 'product_slug' => (string)($it['product_slug'] ?? '')])) ?>" alt="<?= e((string)($it['name'] ?? 'Producto')) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= e(asset('img/placeholder.svg')) ?>';">
                        </div>
                        <div class="order-show-product-card__main">
                          <h3 class="order-show-product-card__name"><?= e((string)($it['name'] ?? 'â€”')) ?></h3>
                          <div class="order-show-product-card__meta">
                            <span>SKU: <?= e((string)($it['sku'] ?? 'â€”')) ?></span>
                          </div>
                          <a class="order-show-link" href="<?= e((string)($it['product_href'] ?? '#')) ?>">Ver producto</a>
                        </div>
                      </div>
                      <div class="order-show-product-card__stats">
                        <div class="order-show-product-card__stat">
                          <span class="order-show-product-card__stat-label">Cant.</span>
                          <span class="order-show-product-card__stat-value"><?= (int)($it['qty'] ?? 0) ?></span>
                        </div>
                        <div class="order-show-product-card__stat">
                          <span class="order-show-product-card__stat-label">Precio</span>
                          <span class="order-show-product-card__stat-value"><?= e((string)($it['price_fmt'] ?? 'â€”')) ?></span>
                        </div>
                        <div class="order-show-product-card__stat">
                          <span class="order-show-product-card__stat-label">Subtotal</span>
                          <span class="order-show-product-card__stat-value order-show-product-card__stat-value--strong"><?= e((string)($it['sub_fmt'] ?? 'â€”')) ?></span>
                        </div>
                      </div>
                    </article>
                  <?php endforeach; ?>
                  <div class="order-show-products-total">
                    <span>Total productos:</span>
                    <span><?= e((string)($order['total_fmt'] ?? 'â€”')) ?></span>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <aside class="order-show-page__sidebar">
          <div class="order-show-page__sidebar-stack">
            <?php section('cuenta/pedidos/direccion', ['order' => $order, 'shipment' => $shipment]); ?>
            <?php section('cuenta/pedidos/timeline', ['order' => $order]); ?>

            <section class="order-show-card order-show-card--sidebar order-show-support-card">
              <div class="order-show-card__body order-show-support-card__body">
                <div class="order-show-support-card__main">
                  <span class="order-show-support-card__icon" aria-hidden="true"><?= ui_icon('phone') ?></span>
                  <div class="order-show-support-card__copy">
                    <h2 class="order-show-card__title">¿Necesitás ayuda?</h2>
                    <p class="order-show-card__intro">Estamos para ayudarte con tu pedido.</p>
                  </div>
                </div>
                <a
                  href="<?= e($supportUrl !== '' ? $supportUrl : url('/ayuda')) ?>"
                  class="v2-btn v2-btn--ghost order-show-support-card__cta"
                  <?= $supportUrl !== '' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                >
                  <span>Contactar soporte</span>
                </a>
              </div>
            </section>
          </div>
        </aside>
      </div>

      <?php section('checkout/address-modal', [
        'provinces' => $provinces ?? [],
        'profilePhone' => (string)($order['shipping_phone'] ?? ''),
        'createUrl' => url('/cuenta/pedidos/direccion/guardar'),
        'updateUrl' => url('/cuenta/pedidos/direccion/guardar'),
        'returnUrl' => url('/cuenta/pedidos/ver') . '?id=' . (string)($order['id'] ?? 0),
        'submitLabel' => 'Guardar',
      ]); ?>
    </div>
  </div>
</section>
