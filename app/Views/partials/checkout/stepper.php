<?php
$currentStep = (int)($currentStep ?? 1);
$canStep2 = (bool)($canStep2 ?? false);
$canStep3 = (bool)($canStep3 ?? false);
$canStep4 = (bool)($canStep4 ?? false);

$steps = [
  1 => ['title' => 'Direccion', 'desc' => 'Elegi donde entregar', 'href' => base_url('/checkout'), 'enabled' => true],
  2 => ['title' => 'Envio', 'desc' => 'Elegi el metodo de envio', 'href' => base_url('/checkout?step=2'), 'enabled' => $canStep2],
  3 => ['title' => 'Pago', 'desc' => 'Selecciona un metodo', 'href' => base_url('/checkout?step=3'), 'enabled' => $canStep3],
  4 => ['title' => 'Confirmacion', 'desc' => 'Finaliza tu compra', 'href' => base_url('/checkout?step=4'), 'enabled' => $canStep4],
];

$backHref = $currentStep > 1 ? (string)($steps[$currentStep - 1]['href'] ?? base_url('/checkout')) : (string)base_url('/carrito');
?>
<div class="checkout-stepper">
  <div class="checkout-stepper__mobile">
    <div class="checkout-stepper__topbar">
      <a href="<?= e($backHref) ?>" class="checkout-stepper__back">
        <?= ui_icon('back-arrow', 'checkout-stepper__back-arrow ui-icon--primary', ['aria-hidden' => 'true']) ?>
        <span class="checkout-stepper__back-label">Volver</span>
      </a>
      <div class="checkout-stepper__counter"><?= (int)$currentStep ?>/4</div>
    </div>
  </div>

  <div class="checkout-stepper__desktop">
    <h1 class="checkout-stepper__desktop-title">Checkout</h1>
    <p class="checkout-stepper__desktop-copy">Completa tu compra en 4 pasos. Es rapido y seguro.</p>

    <div class="checkout-stepper__desktop-grid">
      <?php foreach ($steps as $n => $s):
        $isActive = ($currentStep === $n);
        $isDone = ($currentStep > $n);
        $isEnabled = $isActive || ($isDone && (bool)$s['enabled']);

        $cardCls = 'checkout-stepper__card';
        if ($isActive) $cardCls .= ' is-active';
        if ($isDone) $cardCls .= ' is-done';
        if (!$isEnabled) $cardCls .= ' is-disabled';
      ?>
        <?php if ($isEnabled): ?>
          <a
            href="<?= e((string)$s['href']) ?>"
            class="<?= e($cardCls) ?>"
            data-stepper-card="<?= (int)$n ?>"
            data-stepper-href="<?= e((string)$s['href']) ?>"
          >
            <div class="checkout-stepper__badge <?= $isDone ?'is-done' : ($isActive ? 'is-active' : 'is-inactive') ?>">
              <?= (int)$n ?>
            </div>
            <div class="checkout-stepper__card-copy">
              <div class="checkout-stepper__card-title"><?= e((string)$s['title']) ?></div>
              <div class="checkout-stepper__card-desc"><?= e((string)$s['desc']) ?></div>
            </div>
          </a>
        <?php else: ?>
          <div
            class="<?= e($cardCls) ?>"
            aria-disabled="true"
            data-stepper-card="<?= (int)$n ?>"
            data-stepper-href="<?= e((string)$s['href']) ?>"
          >
            <div class="checkout-stepper__badge <?= $isDone ?'is-done' : ($isActive ? 'is-active' : 'is-inactive') ?>">
              <?= (int)$n ?>
            </div>
            <div class="checkout-stepper__card-copy">
              <div class="checkout-stepper__card-title"><?= e((string)$s['title']) ?></div>
              <div class="checkout-stepper__card-desc"><?= e((string)$s['desc']) ?></div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
