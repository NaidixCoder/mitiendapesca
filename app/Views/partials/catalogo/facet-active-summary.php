<?php
/** @var array $summary */

$summary = is_array($summary ?? null) ? $summary : [];
$chips = is_array($summary['chips'] ?? null) ? $summary['chips'] : [];
$clearAll = (string)($summary['clear_all'] ?? '');
$count = (int)($summary['count'] ?? count($chips));

if (empty($summary['has_active']) || $chips === []) {
    return;
}
?>

<section class="catalog-active-summary" aria-label="Resumen de filtros activos">
  <div class="catalog-active-summary__head">
    <p class="catalog-active-summary__count">
      <span class="catalog-active-summary__icon" aria-hidden="true">
        <?= ui_icon('warning-triangle') ?>
      </span>
      <span><?= $count ?> filtro<?= $count === 1 ? '' : 's' ?> activo<?= $count === 1 ? '' : 's' ?></span>
    </p>
    <?php if ($clearAll !== ''): ?>
      <a href="<?= e($clearAll) ?>" class="catalog-active-summary__clear">Limpiar</a>
    <?php endif; ?>
  </div>

  <div class="catalog-active-summary__chips">
    <?php foreach ($chips as $chip): ?>
      <a href="<?= e((string)($chip['href'] ?? '#')) ?>" class="catalog-active-summary__chip" title="Quitar filtro">
        <span><?= e((string)($chip['label'] ?? 'Filtro')) ?></span>
        <span class="catalog-active-summary__chip-remove" aria-hidden="true">×</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
