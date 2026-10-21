<?php
/** @var array $filters */
/** @var string|null $basePath */
/** @var array<int,array{value:string,label:string}> $sortOptions */
/** @var string|null $currentSortLabel */

$basePath = $basePath ?? '/productos';
$action = url($basePath);
$preserveKeys = ['slug', 'categoria', 'sub', 'q', 'marca', 'min', 'max'];
$tagFilters = is_array($filters['tags'] ?? null) ? $filters['tags'] : [];
$ordNow = (string)($filters['orden'] ?? 'relevancia');
$sortOptions = $sortOptions ?? [
  ['value' => 'relevancia', 'label' => 'Relevancia'],
  ['value' => 'precio_asc', 'label' => 'Menor precio'],
  ['value' => 'precio_desc', 'label' => 'Mayor precio'],
  ['value' => 'novedades', 'label' => 'Novedades'],
];
$currentSortLabel = (string)($currentSortLabel ?? 'Relevancia');
?>

<div class="catalog-toolbar">
  <form method="get" action="<?= e($action) ?>" class="catalog-toolbar__controls catalog-toolbar__form catalog-sort" data-catalog-sort>
    <?php foreach ($preserveKeys as $k): ?>
      <?php if (!empty($filters[$k]) && is_array($filters[$k])): ?>
        <?php foreach ($filters[$k] as $value): ?>
          <?php if ($value !== null && $value !== ''): ?>
            <input type="hidden" name="<?= e($k) ?>[]" value="<?= e((string)$value) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
      <?php elseif (!empty($filters[$k])): ?>
        <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$filters[$k]) ?>">
      <?php endif; ?>
    <?php endforeach; ?>
    <?php foreach ($tagFilters as $groupSlug => $tagSlugs): ?>
      <?php foreach ((array)$tagSlugs as $tagSlug): ?>
        <?php $tagSlug = trim((string)$tagSlug); ?>
        <?php if ($tagSlug !== ''): ?>
          <input type="hidden" name="tag[<?= e((string)$groupSlug) ?>][]" value="<?= e($tagSlug) ?>">
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <input type="hidden" name="orden" value="<?= e($ordNow) ?>" data-catalog-sort-input>

    <button
      type="button"
      class="catalog-sort__trigger"
      aria-haspopup="dialog"
      aria-expanded="false"
      aria-controls="catalog-sort-sheet"
      data-catalog-sort-trigger
    >
      <span class="catalog-sort__label" data-catalog-sort-label><?= e($currentSortLabel) ?></span>
      <span class="catalog-sort__chevron" aria-hidden="true">
        <?= ui_icon('chevron-down-solid') ?>
      </span>
    </button>

    <div class="catalog-sort__popover" aria-hidden="true" data-catalog-sort-popover hidden>
      <div class="catalog-sort__list" role="listbox" aria-label="Ordenar productos">
        <?php foreach ($sortOptions as $index => $option): ?>
          <?php $isActive = $ordNow === (string)$option['value']; ?>
          <button
            type="button"
            class="catalog-sort__option<?= $isActive ?' is-active' : '' ?>"
            role="option"
            aria-selected="<?= $isActive ? 'true' : 'false' ?>"
            data-sort-value="<?= e((string)$option['value']) ?>"
            data-sort-label="<?= e((string)$option['label']) ?>"
          >
            <span class="catalog-sort__option-text"><?= e((string)$option['label']) ?></span>
            <?php if ($isActive): ?>
              <span class="catalog-sort__option-mark" aria-hidden="true"></span>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="catalog-sort__backdrop" hidden data-catalog-sort-backdrop></div>
    <div
      id="catalog-sort-sheet"
      class="catalog-sort__sheet"
      aria-hidden="true"
      data-catalog-sort-sheet
      hidden
    >
      <div class="catalog-sort__sheet-header">
        <p class="catalog-sort__sheet-title">Ordenar productos</p>
        <button type="button" class="catalog-sort__sheet-close" aria-label="Cerrar" data-catalog-sort-close>
          <?= ui_icon('close-solid') ?>
        </button>
      </div>
      <div class="catalog-sort__sheet-body" role="listbox" aria-label="Ordenar productos">
        <?php foreach ($sortOptions as $option): ?>
          <?php $isActive = $ordNow === (string)$option['value']; ?>
          <button
            type="button"
            class="catalog-sort__sheet-option<?= $isActive ?' is-active' : '' ?>"
            role="option"
            aria-selected="<?= $isActive ? 'true' : 'false' ?>"
            data-sort-value="<?= e((string)$option['value']) ?>"
            data-sort-label="<?= e((string)$option['label']) ?>"
          >
            <span class="catalog-sort__sheet-option-text"><?= e((string)$option['label']) ?></span>
            <?php if ($isActive): ?>
              <span class="catalog-sort__sheet-option-mark" aria-hidden="true"></span>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </form>
</div>
