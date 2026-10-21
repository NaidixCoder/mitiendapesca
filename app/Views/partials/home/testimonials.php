<?php
$testimonials = [
    ['name' => 'Santiago R.', 'text' => 'Me asesoraron para armar mi primer equipo. Llego rapido y excelente calidad.'],
    ['name' => 'Valentin D.', 'text' => 'Compre un Reel Montague, muy buena calidad!.'],
    ['name' => 'Julian V.', 'text' => 'Compre online y retire en tienda, todo en 24hs. Super recomendable.'],
    ['name' => 'Ricardo P.', 'text' => 'Me armo una linea de pejerrey de primera. Exitos Matias.'],
];
?>
<section class="home-sec home-testimonials" aria-label="Experiencias reales">
  <div class="home-sec__container">
    <header class="home-sec__head home-sec__head--split">
      <div>
        <h2 class="home-sec__title">Experiencias reales</h2>
        <p class="home-sec__subtitle">Que dicen nuestros clientes</p>
      </div>
    </header>

    <div class="home-testimonials__carousel" data-home-carousel data-home-carousel-mobile-only="1" data-home-carousel-interval="3000">
      <div class="home-testimonials__track" data-home-carousel-track>
        <?php foreach ($testimonials as $idx => $t): ?>
          <blockquote class="home-testimonials__card <?= $idx === 0 ?'is-active' : '' ?>" data-home-carousel-slide>
            <div class="home-testimonials__card-head">
              <div class="home-testimonials__meta">
                <span class="home-testimonials__name"><?= e($t['name']) ?></span>
                <span class="home-testimonials__verified">Compra verificada</span>
              </div>
              <div class="home-testimonials__rating" aria-label="Calificacion 5 de 5">
                <?php for ($i = 0; $i < 5; $i++): ?>
                  <?= ui_icon('star-solid', 'home-testimonials__star') ?>
                <?php endfor; ?>
              </div>
            </div>
            <p class="home-testimonials__text"><?= e($t['text']) ?></p>
          </blockquote>
        <?php endforeach; ?>
      </div>

      <div class="home-testimonials__controls">
        <button class="home-testimonials__control home-testimonials__control--prev" type="button" data-home-carousel-prev aria-label="Opinion anterior">
          <span aria-hidden="true"></span>
        </button>
        <button class="home-testimonials__control home-testimonials__control--next" type="button" data-home-carousel-next aria-label="Opinion siguiente">
          <span aria-hidden="true"></span>
        </button>
      </div>

      <div class="home-testimonials__dots" role="tablist" aria-label="Selector de opiniones">
        <?php foreach ($testimonials as $idx => $_): ?>
          <button
            class="home-testimonials__dot <?= $idx === 0 ?'is-active' : '' ?>"
            type="button"
            data-home-carousel-dot="<?= (int)$idx ?>"
            aria-label="Ver opinion <?= (int)$idx + 1 ?>"
            aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"
          ></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
