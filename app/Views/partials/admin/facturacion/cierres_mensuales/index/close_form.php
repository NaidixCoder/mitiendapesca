<?php
/** @var array<int,array<string,mixed>> $period_options */
/** @var string $close_url */
/** @var string $csrf */
?>
<div class="card av3-pad-4 av3-space-bottom-4">
  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
    <div>
      <label class="label">Periodo a cerrar</label>
      <select id="closure-period" class="input">
        <?php foreach ($period_options as $option): ?>
          <option value="<?= e((string)($option['period'] ?? '')) ?>"
                  data-has-movements="<?= !empty($option['has_movements']) ? '1' : '0' ?>"
                  data-is-closed="<?= !empty($option['is_closed']) ? '1' : '0' ?>"
                  <?= !empty($option['selected']) ? 'selected' : '' ?>>
            <?= e((string)($option['label'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p id="period-hint" class="av3-type-caption av3-text-gray-500 av3-space-top-1"></p>
    </div>
    <div class="av3-flex av3-items-end av3-gap-2">
      <button id="btn-close-period" class="btn btn-primary av3-width-full" type="button" data-close-url="<?= e($close_url) ?>">Cerrar periodo</button>
    </div>
    <div class="av3-flex av3-items-end">
      <p class="av3-type-caption av3-text-gray-500">Una vez cerrado, los reportes leeran el snapshot y no se recalculara ese mes.</p>
    </div>
  </div>
  <input type="hidden" id="csrf" value="<?= e($csrf) ?>">
</div>
