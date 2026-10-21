<?php
/** @var array<string,mixed> $order */
$order = $order ?? [];
/** @var array<string,string> $bank */
$bank = $bank ?? [];
/** @var array<int, array<string,mixed>> $receipts */
$receipts = $receipts ?? [];

$isTransfer = ((string)($order['payment_method'] ?? '') === 'transferencia');
$status = (string)($order['status'] ?? '');
$isCard = !$isTransfer;
$isCardApproved = $isCard && in_array($status, ['paid', 'processing', 'packed', 'shipped', 'delivered', 'completed'], true);
$isCardPending = $isCard && !$isCardApproved;
$isPending = in_array($status, ['pending_payment', 'pending', 'payment_failed', 'payment_expired'], true);
$isAccredited = in_array($status, ['paid', 'processing', 'packed', 'shipped', 'delivered', 'completed'], true);
$hasReceipt = !empty($receipts);
$reserveHours = (int)($order['transfer_reservation_hours'] ?? 24);
$reserveUntil = trim((string)($order['transfer_reservation_until'] ?? ''));

$alias = trim((string)($bank['alias'] ?? ''));
$cbu = trim((string)($bank['cbu'] ?? ''));
$owner = trim((string)($bank['owner'] ?? ''));
$cuit = trim((string)($bank['cuit'] ?? ''));
$bname = trim((string)($bank['name'] ?? ''));
$reference = 'Pedido #' . (int)($order['id'] ?? 0);
$hasBank = ($alias !== '' || $cbu !== '' || $owner !== '' || $cuit !== '' || $bname !== '');
$paymentMethodLabel = trim((string)($order['payment_method_label'] ?? ''));
if ($paymentMethodLabel === '') {
  $paymentMethodLabel = '—';
}
$accreditedAt = '';
if (!empty($receipts) && is_array($receipts[0])) {
  $accreditedAt = trim((string)($receipts[0]['created_at'] ?? ''));
}
if ($accreditedAt === '') {
  $accreditedAt = trim((string)($order['created_at'] ?? ''));
}
$cardReference = 'Pedido #' . (int)($order['id'] ?? 0);
?>

<section id="pago" class="order-show-card">
  <header class="order-show-card__head order-show-card__head--split">
    <div>
      <h2 class="order-show-card__title">
        <span class="order-show-section-icon" aria-hidden="true"><?= ui_icon('credit-card') ?></span>
        <span>Pago</span>
      </h2>
      <p class="order-show-card__intro">Próximos pasos para completar la operación.</p>
    </div>
    <div class="order-show-tag-group">
      <span class="order-show-status-badge order-show-status-badge--<?= $isTransfer ?'warn' : 'ok' ?>"><?= e($order['payment_method_label'] ?? 'â€”') ?></span>
      <?php if ($isTransfer && $isPending && $hasReceipt): ?>
        <span class="order-show-status-badge order-show-status-badge--ok">Comprobante recibido</span>
      <?php endif; ?>
    </div>
  </header>

  <div class="order-show-card__body">
    <?php if ($isTransfer && $isAccredited): ?>
      <section class="order-show-payment-accredited">
        <div class="order-show-payment-accredited__hero">
          <span class="order-show-payment-accredited__hero-icon" aria-hidden="true"><?= ui_icon('shield-solid') ?></span>
          <div class="order-show-payment-accredited__hero-copy">
            <h3 class="order-show-payment-accredited__hero-title">Pago acreditado</h3>
            <p class="order-show-payment-accredited__hero-text">Recibimos y validamos tu comprobante. Ya estamos preparando tu pedido.</p>
          </div>
        </div>

        <div class="order-show-payment-accredited__grid">
          <div class="order-show-payment-accredited__item">
            <span class="order-show-payment-accredited__item-icon" aria-hidden="true"><?= ui_icon('bank-solid') ?></span>
            <div class="order-show-payment-accredited__row">
              <div class="order-show-payment-accredited__item-label">Metodo</div>
              <div class="order-show-payment-accredited__item-value"><?= e($paymentMethodLabel) ?></div>
            </div>
          </div>
          <div class="order-show-payment-accredited__item">
            <span class="order-show-payment-accredited__item-icon" aria-hidden="true"><?= ui_icon('card') ?></span>
            <div class="order-show-payment-accredited__row">
              <div class="order-show-payment-accredited__item-label">Importe</div>
              <div class="order-show-payment-accredited__item-value"><?= e((string)($order['total_fmt'] ?? '—')) ?></div>
            </div>
          </div>
          <div class="order-show-payment-accredited__item">
            <span class="order-show-payment-accredited__item-icon" aria-hidden="true"><?= ui_icon('clock') ?></span>
            <div class="order-show-payment-accredited__row">
              <div class="order-show-payment-accredited__item-label">Fecha</div>
              <div class="order-show-payment-accredited__item-value"><?= e($accreditedAt !== '' ? $accreditedAt : '—') ?></div>
            </div>
          </div>
          <div class="order-show-payment-accredited__item">
            <span class="order-show-payment-accredited__item-icon" aria-hidden="true"><?= ui_icon('check-badge') ?></span>
            <div class="order-show-payment-accredited__row">
              <div class="order-show-payment-accredited__item-label">Comprobante recibido</div>
              <div class="order-show-payment-accredited__item-value"><?= $hasReceipt ? 'Si' : 'No' ?></div>
            </div>
          </div>
        </div>
      </section>

      <?php if ($hasBank): ?>
        <section class="order-show-block order-show-block--transfer">
          <details class="order-show-transfer-details">
            <summary class="order-show-transfer-details__summary">
              <span class="order-show-transfer-details__summary-icon" aria-hidden="true"><?= ui_icon('bank-solid') ?></span>
              <span class="order-show-transfer-details__summary-text">Datos de la transferencia</span>
              <span class="order-show-transfer-details__summary-arrow" aria-hidden="true"><?= ui_icon('chevron-down') ?></span>
            </summary>
            <div class="order-show-transfer-details__body">
              <div class="order-show-kv order-show-transfer-grid">
                <?php if ($bname !== ''): ?>
                  <div class="order-show-kv__row">
                    <span class="order-show-kv__key">Banco</span>
                    <span class="order-show-kv__value"><?= e($bname) ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($owner !== ''): ?>
                  <div class="order-show-kv__row">
                    <span class="order-show-kv__key">Titular</span>
                    <span class="order-show-kv__value"><?= e($owner) ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($cuit !== ''): ?>
                  <div class="order-show-kv__row">
                    <span class="order-show-kv__key">CUIT</span>
                    <span class="order-show-kv__value"><?= e($cuit) ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($alias !== ''): ?>
                  <div class="order-show-kv__row">
                    <span class="order-show-kv__key">Alias</span>
                    <span class="order-show-kv__value"><?= e($alias) ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($cbu !== ''): ?>
                  <div class="order-show-kv__row">
                    <span class="order-show-kv__key">CBU</span>
                    <span class="order-show-kv__value"><?= e($cbu) ?></span>
                  </div>
                <?php endif; ?>
                <div class="order-show-kv__row">
                  <span class="order-show-kv__key">Referencia</span>
                  <span class="order-show-kv__value"><?= e($reference) ?></span>
                </div>
              </div>
            </div>
          </details>
        </section>
      <?php endif; ?>

      <section class="order-show-block">
        <div class="order-show-block__title-row order-show-block__title-row--icon">
          <span class="order-show-block__icon-wrap">
            <span class="order-show-block__icon" aria-hidden="true"><?= ui_icon('truck-solid') ?></span>
          </span>
          <div class="order-show-block__title-wrap">
            <h3 class="order-show-block__title">Preparando pedido</h3>
          </div>
        </div>
      </section>

    <?php elseif ($isTransfer): ?>
      <?php if ($hasBank): ?>
        <section class="order-show-block order-show-block--transfer">
          <?php if ($hasReceipt): ?>
            <details class="order-show-transfer-details">
              <summary class="order-show-transfer-details__summary">
                <span class="order-show-transfer-details__summary-icon" aria-hidden="true"><?= ui_icon('bank-solid') ?></span>
                <span class="order-show-transfer-details__summary-text">Ver datos de transferencia</span>
                <span class="order-show-transfer-details__summary-arrow" aria-hidden="true"><?= ui_icon('chevron-down') ?></span>
              </summary>
              <div class="order-show-transfer-details__body">
          <?php else: ?>
            <div class="order-show-block__title-row order-show-block__title-row--icon">
              <span class="order-show-block__icon-wrap">
                <span class="order-show-block__icon" aria-hidden="true"><?= ui_icon('bank-solid') ?></span>
              </span>
              <div class="order-show-block__title-wrap">
                <h3 class="order-show-block__title">Datos para la transferencia</h3>
                <p class="order-show-block__subtitle">Copiá estos datos tal como aparecen y usá la referencia del pedido.</p>
              </div>
            </div>
          <?php endif; ?>

          <div class="order-show-kv order-show-transfer-grid">
            <?php if ($bname !== ''): ?>
              <div class="order-show-kv__row">
                <span class="order-show-kv__key">Banco</span>
                <span class="order-show-kv__value"><?= e($bname) ?></span>
              </div>
            <?php endif; ?>

            <?php if ($owner !== ''): ?>
              <div class="order-show-kv__row">
                <span class="order-show-kv__key">Titular</span>
                <span class="order-show-kv__value"><?= e($owner) ?></span>
              </div>
            <?php endif; ?>

            <?php if ($cuit !== ''): ?>
              <div class="order-show-kv__row">
                <span class="order-show-kv__key">CUIT</span>
                <span class="order-show-kv__value"><?= e($cuit) ?></span>
              </div>
            <?php endif; ?>

            <?php if ($alias !== ''): ?>
              <div class="order-show-kv__row order-show-kv__row--copy">
                <span class="order-show-kv__key">Alias</span>
                <span class="order-show-kv__value"><?= e($alias) ?></span>
                <button type="button" class="order-show-copy-btn" data-order-copy="<?= e($alias) ?>" aria-label="Copiar alias">
                  <span class="order-show-copy-btn__icon" aria-hidden="true"><?= ui_icon('copy-solid') ?></span>
                </button>
              </div>
            <?php endif; ?>

            <?php if ($cbu !== ''): ?>
              <div class="order-show-kv__row order-show-kv__row--copy">
                <span class="order-show-kv__key">CBU</span>
                <span class="order-show-kv__value"><?= e($cbu) ?></span>
                <button type="button" class="order-show-copy-btn" data-order-copy="<?= e($cbu) ?>" aria-label="Copiar CBU">
                  <span class="order-show-copy-btn__icon" aria-hidden="true"><?= ui_icon('copy-solid') ?></span>
                </button>
              </div>
            <?php endif; ?>

            <div class="order-show-kv__row">
              <span class="order-show-kv__key">Referencia</span>
              <span class="order-show-kv__value"><?= e($reference) ?></span>
            </div>
          </div>

          <?php if ($status === 'pending_payment'): ?>
            <p class="order-show-block__note">
              La reserva de stock vence<?= $reserveUntil !== '' ? ' el ' . e($reserveUntil) : ' en ' . $reserveHours . ' horas' ?>.
            </p>
          <?php endif; ?>

          <?php if ($hasReceipt): ?>
              </div>
            </details>
          <?php endif; ?>
        </section>
      <?php else: ?>
        <section class="order-show-alert order-show-alert--warn">
          <div class="order-show-alert__title">Datos de transferencia no disponibles</div>
          <div class="order-show-alert__text">Por el momento no podemos mostrar los datos bancarios. Elegí Mercado Pago o contáctanos para recibirlos.</div>
          <details class="order-show-alert__details">
            <summary>Detalle técnico</summary>
            <div class="order-show-alert__code">Configurá `BANK_ALIAS`, `BANK_CBU` y `BANK_OWNER` para habilitar esta sección.</div>
          </details>
        </section>
      <?php endif; ?>

      <?php if ($isPending): ?>
        <section id="subir-comprobante" class="order-show-block order-show-block--separated">
          <div class="order-show-block__title-wrap order-show-upload-head">
            <h3 class="order-show-block__title order-show-upload-title">Subí tu comprobante</h3>
            <p class="order-show-block__subtitle order-show-upload-subtitle">Subí una foto, captura o PDF del pago.</p>
          </div>

          <div class="order-show-upload-formats">
            <span>JPG, PNG o PDF · Hasta 6 MB</span>
          </div>

          <?php
          $latestReceiptForUi = $receipts[0] ?? null;
          $existingUploadName = is_array($latestReceiptForUi) ? trim((string)($latestReceiptForUi['name'] ?? '')) : '';
          $existingUploadSize = is_array($latestReceiptForUi) ? (int)($latestReceiptForUi['size'] ?? 0) : 0;
          $latestUploadFile = is_array($latestReceiptForUi) ? trim((string)($latestReceiptForUi['file'] ?? '')) : '';
          $existingUploadExt = strtoupper((string)pathinfo($existingUploadName, PATHINFO_EXTENSION));
          if ($existingUploadExt === '') {
            $existingUploadExt = 'ARCHIVO';
          }
          $existingUploadSizeLabel = ($existingUploadSize > 0)
            ? (($existingUploadSize >= 1048576)
              ? number_format($existingUploadSize / 1048576, 1, '.', '') . ' MB'
              : (string)max(1, (int)round($existingUploadSize / 1024)) . ' KB')
            : '0 KB';
          $existingUploadDetail = $existingUploadSizeLabel;
          $whatsHref = '';
          if ($latestUploadFile !== '') {
            $latestViewUrl = (string)url('/cuenta/pedidos/comprobante/ver')
              . '?order_id=' . (int)($order['id'] ?? 0)
              . '&file=' . urlencode($latestUploadFile);
            $whatsText = 'Hola, comparto comprobante del pedido #' . (int)($order['id'] ?? 0) . ': ' . $latestViewUrl;
            $waConfigUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
            $waPhone = preg_replace('/\D+/', '', $waConfigUrl);
            if ($waPhone === null) {
              $waPhone = '';
            }
            if ($waPhone !== '') {
              $whatsHref = 'https://wa.me/' . $waPhone . '?text=' . rawurlencode($whatsText);
            } else {
              $whatsHref = 'https://wa.me/?text=' . rawurlencode($whatsText);
            }
          }
          ?>
          <form class="order-show-upload-form" method="post" action="<?= e(url('/cuenta/pedidos/comprobante/subir')) ?>" enctype="multipart/form-data" data-upload-existing="<?= $hasReceipt ? '1' : '0' ?>" data-upload-existing-name="<?= e($existingUploadName) ?>" data-upload-existing-detail="<?= e($existingUploadDetail) ?>" data-upload-whatsapp-href="<?= e($whatsHref) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int)($order['id'] ?? 0) ?>">
            <label class="order-show-upload-dropzone">
              <span class="order-show-upload-dropzone__icon" aria-hidden="true">
                <svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M13 4H8.8C7.11984 4 6.27976 4 5.63803 4.32698C5.07354 4.6146 4.6146 5.07354 4.32698 5.63803C4 6.27976 4 7.11984 4 8.8V15.2C4 16.8802 4 17.7202 4.32698 18.362C4.6146 18.9265 5.07354 19.3854 5.63803 19.673C6.27976 20 7.11984 20 8.8 20H15.2C16.8802 20 17.7202 20 18.362 19.673C18.9265 19.3854 19.3854 18.9265 19.673 18.362C20 17.7202 20 16.8802 20 15.2V11" stroke="currentColor" stroke-width="0.36" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 16L8.29289 11.7071C8.68342 11.3166 9.31658 11.3166 9.70711 11.7071L13 15M13 15L15.7929 12.2071C16.1834 11.8166 16.8166 11.8166 17.2071 12.2071L20 15M13 15L15.25 17.25" stroke="currentColor" stroke-width="0.36" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18.5 3V5.5M18.5 8V5.5M18.5 5.5H16M18.5 5.5H21" stroke="currentColor" stroke-width="0.36" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="order-show-upload-dropzone__content">
                <span class="order-show-upload-dropzone__title">Seleccioná tu comprobante</span>
                <span class="order-show-upload-dropzone__subtitle">Tocá para elegir un archivo</span>
              </span>
              <input class="order-show-upload-form__input" type="file" name="receipt" accept="image/jpeg,image/png,application/pdf">
            </label>

            <section class="order-show-upload-selected" data-upload-selected hidden>
              <div class="order-show-upload-selected__top">
                <div class="order-show-upload-selected__thumb" aria-hidden="true">
                  <span class="order-show-upload-selected__thumb-icon"><?= ui_icon('document') ?></span>
                </div>
                <div class="order-show-upload-selected__meta">
                  <p class="order-show-upload-selected__name" data-upload-file-name>archivo.jpg</p>
                  <p class="order-show-upload-selected__detail" data-upload-file-detail>0 MB</p>
                  <div class="order-show-upload-selected__ready">
                    <span aria-hidden="true"><?= ui_icon('check') ?></span>
                    <span data-upload-ready-text>Listo para subir</span>
                  </div>
                  <div class="order-show-upload-inline-actions">
                    <button type="button" class="order-show-upload-change-btn" data-upload-change>
                      <span class="order-show-upload-change-btn__icon order-show-upload-change-btn__icon--edit" aria-hidden="true"><?= ui_icon('note-edit') ?></span>
                      <span data-upload-change-text class="order-show-upload-change-btn__text">Cambiar</span>
                    </button>
                    <button type="button" class="order-show-upload-remove-btn order-show-upload-remove-btn--inline" data-upload-remove aria-label="Quitar archivo seleccionado">
                      <span aria-hidden="true"><?= ui_icon('trash') ?></span>
                      <span>Eliminar</span>
                    </button>
                  </div>
                </div>
              </div>
              <p class="order-show-upload-selected__hint">Podés cambiarlo antes de confirmar</p>
              <div class="order-show-upload-selected__sent-note" data-upload-sent-note hidden>
                <span class="order-show-upload-selected__sent-icon" aria-hidden="true"><?= ui_icon('shield-solid') ?></span>
                <p>Tu comprobante ya fue enviado.</p>
              </div>
              <a class="v2-btn v2-btn--primary order-show-upload-selected__whatsapp" data-upload-whatsapp href="<?= e($whatsHref) ?>" target="_blank" rel="noopener" hidden>
                <span class="order-show-upload-selected__whatsapp-icon" aria-hidden="true"><?= ui_icon('whatsapp-brand') ?></span>
                <span>Enviar por Whatsapp</span>
                <span aria-hidden="true"><?= ui_icon('chevron-right-solid') ?></span>
              </a>
            </section>

            <button class="v2-btn v2-btn--primary" type="submit">
              <span aria-hidden="true"><?= ui_icon('upload-cloud') ?></span>
              <span>Subir comprobante</span>
            </button>
          </form>
          <p class="order-show-upload-note"><?= ui_icon('shield-solid') ?> Te avisamos cuando validemos el pago.</p>

          <?php if (!empty($receipts)): ?>
            <div class="order-show-receipts">
              <div class="order-show-receipts__title">Comprobantes adjuntos</div>
              <div class="order-show-receipts__list">
                <?php foreach ($receipts as $r): ?>
                  <article class="order-show-receipt">
                    <div class="order-show-receipt__meta">
                      <div class="order-show-receipt__name"><?= e((string)($r['name'] ?? 'comprobante')) ?></div>
                      <div class="order-show-receipt__detail"><?= e((string)($r['created_at'] ?? '')) ?> · <?= (int)round(((int)($r['size'] ?? 0)) / 1024) ?> KB</div>
                    </div>
                    <div class="order-show-receipt__actions">
                      <a class="v2-btn v2-btn--ghost" target="_blank" rel="noopener" href="<?= e(url('/cuenta/pedidos/comprobante/ver')) ?>?order_id=<?= (int)($order['id'] ?? 0) ?>&file=<?= urlencode((string)($r['file'] ?? '')) ?>">Ver</a>
                      <a class="v2-btn v2-btn--ghost" href="<?= e(url('/cuenta/pedidos/comprobante/descargar')) ?>?order_id=<?= (int)($order['id'] ?? 0) ?>&file=<?= urlencode((string)($r['file'] ?? '')) ?>">Descargar</a>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

              <?php
              $latest = $receipts[0] ?? null;
              $latestFile = is_array($latest) ? (string)($latest['file'] ?? '') : '';
              $ext = strtolower(pathinfo($latestFile, PATHINFO_EXTENSION));
              $isPdf = ($ext === 'pdf');
              $isImg = in_array($ext, ['jpg', 'jpeg', 'png'], true);
              $latestViewUrl = '';
              $whatsText = '';
              $whatsHref = '';
              if ($latestFile !== '') {
                $latestViewUrl = (string)url('/cuenta/pedidos/comprobante/ver')
                  . '?order_id=' . (int)($order['id'] ?? 0)
                  . '&file=' . urlencode($latestFile);
                $whatsText = 'Hola, comparto comprobante del pedido #' . (int)($order['id'] ?? 0) . ': ' . $latestViewUrl;
                $waConfigUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
                $waPhone = preg_replace('/\D+/', '', $waConfigUrl);
                if ($waPhone === null) {
                  $waPhone = '';
                }
                if ($waPhone !== '') {
                  $whatsHref = 'https://wa.me/' . $waPhone . '?text=' . rawurlencode($whatsText);
                } else {
                  $whatsHref = 'https://wa.me/?text=' . rawurlencode($whatsText);
                }
              }
              ?>

              <?php if ($latestFile !== '' && ($isPdf || $isImg)): ?>
                <details class="order-show-preview">
                  <summary class="order-show-preview__summary">Previsualizar último comprobante</summary>
                  <div class="order-show-preview__body">
                    <?php if ($isImg): ?>
                      <a target="_blank" rel="noopener" href="<?= e(url('/cuenta/pedidos/comprobante/ver')) ?>?order_id=<?= (int)($order['id'] ?? 0) ?>&file=<?= urlencode($latestFile) ?>">
                        <img class="order-show-preview__image" src="<?= e(url('/cuenta/pedidos/comprobante/ver')) ?>?order_id=<?= (int)($order['id'] ?? 0) ?>&file=<?= urlencode($latestFile) ?>" alt="Comprobante">
                      </a>
                      <div class="order-show-preview__note">Tocá la imagen para verla en una pestaña nueva.</div>
                    <?php else: ?>
                      <div class="order-show-preview__note">PDF adjunto.</div>
                      <a class="v2-btn v2-btn--ghost order-show-preview__cta" target="_blank" rel="noopener" href="<?= e(url('/cuenta/pedidos/comprobante/ver')) ?>?order_id=<?= (int)($order['id'] ?? 0) ?>&file=<?= urlencode($latestFile) ?>">Abrir PDF</a>
                    <?php endif; ?>
                  </div>
                </details>
              <?php endif; ?>

              <?php if ($whatsHref !== ''): ?>
                <div class="order-show-whatsapp-share">
                  <p class="order-show-whatsapp-share__note">
                    Tu comprobante ya fue enviado. Podés reenviarlo por WhatsApp.
                  </p>
                  <a class="v2-btn v2-btn--primary order-show-whatsapp-share__cta" href="<?= e($whatsHref) ?>" target="_blank" rel="noopener">
                    <span class="order-show-whatsapp-share__cta-icon" aria-hidden="true"><?= ui_icon('whatsapp-brand') ?></span>
                    <span>Enviar por WhatsApp</span>
                    <span class="order-show-whatsapp-share__cta-arrow" aria-hidden="true"><?= ui_icon('chevron-right-solid') ?></span>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    <?php else: ?>
      <section class="order-show-payment-card order-show-payment-card--<?= $isCardApproved ?'ok' : 'warn' ?>">
        <div class="order-show-payment-card__hero">
          <span class="order-show-payment-card__hero-icon" aria-hidden="true">
            <?= ui_icon($isCardApproved ? 'check' : 'clock') ?>
          </span>
          <div class="order-show-payment-card__hero-copy">
            <h3 class="order-show-payment-card__hero-title"><?= $isCardApproved ? 'Pago acreditado' : 'Autorización pendiente' ?></h3>
            <p class="order-show-payment-card__hero-text">
              <?= $isCardApproved
                ? 'Ya estamos preparando tu pedido.'
                : 'La operación todavía no fue confirmada por la entidad procesadora. Tu pedido quedó registrado y te avisaremos cuando recibamos una actualización.' ?>
            </p>
          </div>
        </div>

        <div class="order-show-payment-card__grid">
          <div class="order-show-payment-card__item">
            <span class="order-show-payment-card__item-icon" aria-hidden="true"><?= ui_icon('card') ?></span>
            <div class="order-show-payment-card__row">
              <div class="order-show-payment-card__item-label">Método</div>
              <div class="order-show-payment-card__item-value"><?= e($paymentMethodLabel) ?></div>
            </div>
          </div>
          <div class="order-show-payment-card__item">
            <span class="order-show-payment-card__item-icon" aria-hidden="true"><?= ui_icon('bank-solid') ?></span>
            <div class="order-show-payment-card__row">
              <div class="order-show-payment-card__item-label">Importe</div>
              <div class="order-show-payment-card__item-value"><?= e((string)($order['total_fmt'] ?? '—')) ?></div>
            </div>
          </div>
          <div class="order-show-payment-card__item">
            <span class="order-show-payment-card__item-icon" aria-hidden="true"><?= ui_icon('clock') ?></span>
            <div class="order-show-payment-card__row">
              <div class="order-show-payment-card__item-label">Estado</div>
              <div class="order-show-payment-card__item-value"><?= e($isCardApproved ? 'Aprobado' : 'Pendiente de confirmación') ?></div>
            </div>
          </div>
        </div>

        <p class="order-show-payment-card__note">
          <?= $isCardApproved
            ? 'Tu pedido ya pasó a preparación.'
            : 'No reintentes el pago hasta revisar el estado final en Mercado Pago.' ?>
        </p>
      </section>

    <?php endif; ?>
  </div>
</section>

