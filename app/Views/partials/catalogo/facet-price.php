<?php
/** @var array $filters */
/** @var string|null $basePath */
/** @var array{min?:int,max?:int}|null $priceBounds */

$basePath = $basePath ?? '/productos';
$action = url($basePath);
$hiddenKeys = ['slug', 'categoria', 'sub', 'q', 'orden', 'page'];
$priceBounds = $priceBounds ?? ['min' => 0, 'max' => 50000];
$rawSliderMin = max(0, (int)($priceBounds['min'] ?? 0));
$rawSliderMax = max($rawSliderMin, (int)($priceBounds['max'] ?? 0), 1000);
$rawSliderSpan = max(1, $rawSliderMax - $rawSliderMin);
$sliderStep = match (true) {
  $rawSliderSpan <= 5000 => 100,
  $rawSliderSpan <= 50000 => 1000,
  default => 5000,
};
$roundDownToStep = static fn (int $value, int $step): int => max(0, (int)(floor($value / max(1, $step)) * max(1, $step)));
$roundUpToStep = static fn (int $value, int $step): int => max(0, (int)(ceil($value / max(1, $step)) * max(1, $step)));
$formatPriceInput = static fn (int $value): string => number_format(max(0, $value), 0, ',', '.');
$sliderMin = $roundDownToStep($rawSliderMin, $sliderStep);
$fallbackMax = max($sliderMin, $roundUpToStep($rawSliderMax, $sliderStep), $sliderStep);
$rawMinFilter = trim((string)($filters['min'] ?? ''));
$rawMaxFilter = trim((string)($filters['max'] ?? ''));
$rawMin = ($rawMinFilter !== '' && is_numeric($rawMinFilter)) ? (int)$rawMinFilter : $sliderMin;
$rawMax = ($rawMaxFilter !== '' && is_numeric($rawMaxFilter)) ? (int)$rawMaxFilter : $fallbackMax;
$currentMin = max($sliderMin, $roundDownToStep($rawMin, $sliderStep));
$currentMax = max($currentMin, $roundUpToStep($rawMax, $sliderStep));
$sliderMax = max($fallbackMax, $currentMax);
?>

<section class="catalog-facet catalog-facet--price">
  <div class="catalog-facet__head">
    <div>
      <h3 class="catalog-facet__title">Precio</h3>
    </div>
  </div>

  <div class="catalog-facet__body">
    <form
      method="get"
      action="<?= e($action) ?>"
      class="catalog-price-form"
      data-price-slider
      data-slider-min="<?= e((string)$sliderMin) ?>"
      data-slider-max="<?= e((string)$sliderMax) ?>"
      data-slider-step="<?= e((string)$sliderStep) ?>"
    >
      <?php foreach ($hiddenKeys as $k): ?>
        <?php if (!empty($filters[$k])): ?>
          <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$filters[$k]) ?>">
        <?php endif; ?>
      <?php endforeach; ?>

      <div class="catalog-price-panel">
        <div class="catalog-price-slider">
          <div class="catalog-price-slider__track-wrap">
            <div class="catalog-price-slider__track"></div>
            <div class="catalog-price-slider__range" data-price-range-fill></div>
            <button
              type="button"
              class="catalog-price-slider__handle catalog-price-slider__handle--min"
              data-price-min-handle
              aria-label="Mover precio minimo"
            ></button>
            <button
              type="button"
              class="catalog-price-slider__handle catalog-price-slider__handle--max"
              data-price-max-handle
              aria-label="Mover precio maximo"
            ></button>
          </div>
        </div>

        <div class="catalog-price-grid">
          <div class="catalog-price-field">
            <label class="catalog-price-field__label" for="catalog-price-min">Minimo</label>
            <div class="catalog-price-field__control">
              <span class="catalog-price-field__currency" aria-hidden="true">$</span>
              <input
                id="catalog-price-min"
                class="catalog-price-field__input"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                placeholder="<?= e($formatPriceInput($sliderMin)) ?>"
                value="<?= e($formatPriceInput($currentMin)) ?>"
                data-price-min-input
              >
              <input type="hidden" name="min" value="<?= e((string)$currentMin) ?>" data-price-min-hidden>
            </div>
          </div>

          <div class="catalog-price-field">
            <label class="catalog-price-field__label" for="catalog-price-max">Maximo</label>
            <div class="catalog-price-field__control">
              <span class="catalog-price-field__currency" aria-hidden="true">$</span>
              <input
                id="catalog-price-max"
                class="catalog-price-field__input"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                placeholder="<?= e($formatPriceInput($sliderMax)) ?>"
                value="<?= e($formatPriceInput($currentMax)) ?>"
                data-price-max-input
              >
              <input type="hidden" name="max" value="<?= e((string)$currentMax) ?>" data-price-max-hidden>
            </div>
          </div>
        </div>

        <div class="catalog-price-actions">
          <button class="v2-btn v2-btn--primary" type="submit">Aplicar</button>
        </div>
      </div>
    </form>
  </div>
</section>
