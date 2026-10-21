<?php
/** @var array<int,array{label:string,href:string}> $chips */
/** @var string $clearAll */
if (empty($chips ?? [])) return;
?>
<div class="site-active-filters">
  <div class="site-active-filters__row">
    <span class="site-active-filters__label">Filtros:</span>

    <?php foreach ($chips as $chip): ?>
    <a href="<?= e($chip['href']) ?>" class="site-active-filters__chip" title="Quitar filtro">
      <span class="site-active-filters__truncate"><?= e($chip['label']) ?></span>
      <span class="site-active-filters__remove">×</span>
    </a>
    <?php endforeach; ?>

    <a href="<?= e($clearAll) ?>" class="site-active-filters__clear">
      Quitar todos
    </a>
  </div>
</div>