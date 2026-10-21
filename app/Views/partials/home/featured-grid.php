<?php
$items = $items ?? [];
$title = $title ?? 'Productos';
$subtitle = $subtitle ?? 'Seleccionados para vos';
$badge = $badge ?? 'En stock';
?>
<section class="home-sec" aria-label="<?= e($title) ?>">
  <div class="home-sec__container">
    <header class="home-sec__head home-sec__head--split">
      <div>
        <h2 class="home-sec__title"><?= e($title) ?></h2>
        <p class="home-sec__subtitle"><?= e($subtitle) ?></p>
      </div>
      <a href="<?= base_url('productos') ?>" class="home-sec__link home-sec__cta">Ver todos</a>
    </header>

    <div class="home-grid">
      <?php foreach ($items as $p): ?>
        <?php
          $card = is_array($p['card'] ?? null) ? $p['card'] : [];
          if ($card === []) {
              continue;
          }
        ?>
        <?php section('partials/catalogo/product-card', ['product' => $card]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
