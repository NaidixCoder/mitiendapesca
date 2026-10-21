<?php
  $hasBank = (
    trim((string)($bank['alias'] ?? '')) !== '' ||
    trim((string)($bank['cbu'] ?? '')) !== '' ||
    trim((string)($bank['owner'] ?? '')) !== '' ||
    trim((string)($bank['name'] ?? '')) !== ''
  );
  $emailVerifiedState = (int)($emailVerifiedState ?? ($_SESSION['email_verified'] ?? 0));
  $requiresEmailVerification = $emailVerifiedState !== 1;
  $verificationEmail = trim((string)($_SESSION['email'] ?? ''));
?>

<div class="checkout-payment-section">
  <div class="checkout-card checkout-card--transfer">
    <div class="checkout-card__header checkout-card__header--section">
      <div class="checkout-card__title-wrap">
        <?= ui_icon('circle-active-solid', 'checkout-card__section-icon checkout-card__section-icon--active', ['aria-hidden' => 'true']) ?>
        <div class="checkout-card__section-title">Transferencia bancaria</div>
      </div>
      <?= ui_icon('chevron-down-solid', 'checkout-card__section-chevron', ['aria-hidden' => 'true']) ?>
    </div>

    <div class="checkout-card__divider"></div>

    <div class="checkout-card__section-block">
      <div class="checkout-card__title-wrap">
        <?= ui_icon('bank-solid', 'checkout-card__section-icon', ['aria-hidden' => 'true']) ?>
        <div class="checkout-card__section-heading">Antes de confirmar</div>
      </div>

      <?php if (!$hasBank): ?>
        <div class="checkout-step__notice checkout-step__notice--warning">
          <div class="checkout-step__notice-title">Falta configurar los datos bancarios</div>
          <div class="checkout-step__notice-text">Completa BANK_ALIAS / BANK_CBU / BANK_OWNER en tu <code>.env</code> antes de habilitar transferencia.</div>
        </div>
      <?php else: ?>
        <div class="checkout-step__notice">
          <div class="checkout-step__notice-title">Instrucciones para continuar</div>
          <div class="checkout-step__notice-text">1. Confirmá el pedido para reservar el stock.</div>
          <div class="checkout-step__notice-text">2. La reserva se mantiene por tiempo limitado mientras esperás los datos para transferir.</div>
          <div class="checkout-step__notice-text">3. Después de transferir, subí el comprobante para agilizar la validación.</div>
          <div class="checkout-step__notice-text">4. Cuando confirmemos el pago, el pedido pasará a preparación.</div>
        </div>

        <?php if ($requiresEmailVerification): ?>
          <div
            class="checkout-step__notice checkout-step__notice--warning"
            data-transfer-email-warning
            role="alert"
            tabindex="-1"
          >
            <div class="checkout-step__notice-title">Verific&aacute; tu email para confirmar por transferencia</div>
            <div class="checkout-step__notice-text">
              Antes de reservar stock por transferencia necesitamos verificar tu email<?= $verificationEmail !== '' ? ' en <b>'.e($verificationEmail).'</b>' : '' ?>.
            </div>
            <div class="checkout-step__notice-text">
              Te dejamos dos acciones r&aacute;pidas para destrabarlo sin salir del checkout.
            </div>
            <div class="checkout-step__notice-actions">
              <button
                type="button"
                class="v2-btn"
                data-transfer-resend-email
                data-resend-url="<?= e((string)base_url('/verificar-email/reenviar')) ?>"
              >
                Reenviar email
              </button>
              <button
                type="button"
                class="v2-btn v2-btn--ghost"
                data-transfer-refresh-email
                data-refresh-url="<?= e((string)base_url('/checkout?step=4')) ?>"
              >
                Ya verifiqu&eacute;
              </button>
            </div>
          </div>
        <?php endif; ?>

        <div class="checkout-card__actions">
          <button
            id="btn-transfer-confirm"
            type="submit"
            formaction="<?= base_url('/checkout/transferencia') ?>"
            class="v2-btn v2-btn--brand"
            data-transfer-requires-email="<?= $requiresEmailVerification ? '1' : '0' ?>"
            <?= $requiresEmailVerification ? 'disabled aria-disabled="true"' : '' ?>
          >
            Confirmar pedido
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
