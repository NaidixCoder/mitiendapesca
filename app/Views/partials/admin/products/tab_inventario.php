<?php /** @var array $inv */ ?>
<div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-4">
  <label class="av3-block">Stock
    <input class="input av3-width-full" type="number" name="stock" value="<?= (int)($inv['stock'] ?? 0) ?>">
  </label>
  <label class="av3-block">Umbral bajo stock
    <input class="input av3-width-full" type="number" name="low_stock_threshold" value="<?= (int)($inv['low_stock_threshold'] ?? 3) ?>">
  </label>
  <div class="av3-block">
    <div class="av3-type-body av3-text-var-fg av3-mt-7">Se usa para alertas en Dashboard.</div>
  </div>
</div>
