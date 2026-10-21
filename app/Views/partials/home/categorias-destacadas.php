<section class="home-sec" aria-label="Explorar por categoria">
  <div class="home-sec__container">
    <header class="home-sec__head">
      <h2 class="home-sec__title">Explorá por categoría</h2>
      <div class="home-sec__nav">
      <p class="home-sec__subtitle">Encontrá rápido lo que buscas</p>
        <a href="<?= url('/categorias') ?>" class="home-sec__link">Ver todas</a>
      </div>
    </header>

    <div class="home-grid">
      <?php foreach (($homeCategories ?? []) as $c): ?>
      <?php
        $img = (string)($c['img'] ?? '');
        $title = (string)($c['title'] ?? 'Categoria');
        $href = (string)($c['href'] ?? '#');
      ?>
      <a class="home-card" href="<?= e($href) ?>">
        <div class="home-card__media">
          <img src="<?= e($img) ?>" alt="<?= e($title) ?>" class="home-card__img">
        </div>
        <div class="home-card__body">
          <h3 class="home-card__title"><?= e($title) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
