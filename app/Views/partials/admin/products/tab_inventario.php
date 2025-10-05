<?php /** @var array $inv */ ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <label class="block">Stock
    <input class="input w-full" type="number" name="stock" value="<?= (int)($inv['stock'] ?? 0) ?>">
  </label>
  <label class="block">Umbral bajo stock
    <input class="input w-full" type="number" name="low_stock_threshold" value="<?= (int)($inv['low_stock_threshold'] ?? 3) ?>">
  </label>
  <div class="block">
    <div class="text-sm text-[color:var(--muted)] mt-7">Se usa para alertas en Dashboard.</div>
  </div>
</div>
