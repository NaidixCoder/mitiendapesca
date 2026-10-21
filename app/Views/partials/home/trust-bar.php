<section aria-label="Marcas" class="home-brand-strip">
  <div class="home-brand-strip__container">
    <header class="home-sec__head">
      <h2 class="home-sec__title">Marcas</h2>
      <div class="home-sec__nav">
        <p class="home-sec__subtitle">Buscá por tu marca favorita</p>
        <a href="<?= url('/marcas') ?>" class="home-sec__link">Ver todas</a>
      </div>
    </header>

    <div class="home-brand-strip__grid" role="list">
      <?php foreach (($homeBrands ?? []) as $brand): ?>
        <?php
          $logo = (string)($brand['logo'] ?? '');
          $name = (string)($brand['name'] ?? 'Marca');
          $href = (string)($brand['href'] ?? '#');
          if ($logo === '') continue;
        ?>
        <a href="<?= e($href) ?>" aria-label="<?= e($name) ?>" class="home-brand-strip__item" role="listitem">
          <span class="home-brand-strip__logo-wrap">
            <img src="<?= e($logo) ?>" alt="<?= e($name) ?>" class="home-brand-strip__logo">
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
