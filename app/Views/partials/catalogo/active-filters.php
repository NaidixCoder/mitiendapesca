<?php
/** @var array<int,array{label:string,href:string}> $chips */
/** @var string|null $clearAll */

$chips = $chips ?? [];
$clearAll = $clearAll ?? null;

if (empty($chips)) {
    return;
}
?>

<div class="catalog-active-filters" aria-label="Filtros activos">
  <?php foreach ($chips as $chip): ?>
    <a href="<?= e($chip['href']) ?>" class="catalog-chip" title="Quitar filtro">
      <span><?= e($chip['label']) ?></span>
      <span class="catalog-chip__remove" aria-hidden="true">×</span>
    </a>
  <?php endforeach; ?>

  <?php if (!empty($clearAll)): ?>
    <a href="<?= e($clearAll) ?>" class="catalog-chip-clear">Limpiar filtros</a>
  <?php endif; ?>
</div>
