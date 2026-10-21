<?php
  $alias = trim((string)($bank['alias'] ?? ''));
  $cbu   = trim((string)($bank['cbu'] ?? ''));
  $owner = trim((string)($bank['owner'] ?? ''));
  $bname = trim((string)($bank['name'] ?? ''));
  $hasBank = ($alias !== '' || $cbu !== '' || $owner !== '' || $bname !== '');
?>

<?php if ($hasBank): ?>
  <div class="checkout-bank-sheet">
    <div class="checkout-bank-sheet__row">
      <div class="checkout-bank-sheet__label">Banco</div>
      <div class="checkout-bank-sheet__value"><?= e($bank['name'] ?? '') ?></div>
    </div>
    <div class="checkout-bank-sheet__row">
      <div class="checkout-bank-sheet__label">Alias</div>
      <div class="checkout-bank-sheet__value"><?= e($bank['alias'] ?? '') ?></div>
    </div>
    <div class="checkout-bank-sheet__row">
      <div class="checkout-bank-sheet__label">CBU</div>
      <div class="checkout-bank-sheet__value"><?= e($bank['cbu'] ?? '') ?></div>
    </div>
    <div class="checkout-bank-sheet__row">
      <div class="checkout-bank-sheet__label">Titular</div>
      <div class="checkout-bank-sheet__value"><?= e($bank['owner'] ?? '') ?></div>
    </div>
    <div class="checkout-bank-sheet__row checkout-bank-sheet__row--strong">
      <div class="checkout-bank-sheet__label">Monto exacto</div>
      <div class="checkout-bank-sheet__value"><?= money_ar((int)($cartTotal ?? 0)) ?></div>
    </div>
  </div>
<?php endif; ?>
