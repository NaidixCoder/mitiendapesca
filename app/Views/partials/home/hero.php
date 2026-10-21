<?php
$heroSlides = is_array($heroSlides ?? null) ? $heroSlides : [];
if (empty($heroSlides)) return;

$heroTitle = trim((string)($heroTitle ?? ''));
if ($heroTitle === '') {
  $heroTitle = 'Equipá tu próxima [[accent]]pesca[[/accent]] con productos de calidad';
}

$heroDescription = trim((string)($heroDescription ?? ''));
if ($heroDescription === '') {
  $heroDescription = home_hero_description_default();
}
$heroTitleHtml = home_hero_title_html($heroTitle);
$heroSlideBg = asset('img/hero/background-hero2.jpg');
?>

<section class="home-hero">
  <div class="home-hero__container home-sec__container">
    <div class="home-hero__main">
      <span class="home-hero__badge">
        <span class="home-hero__badge-dot" aria-hidden="true"></span>
        Destacados 2026
      </span>
      <h1 class="home-hero__title"><?= $heroTitleHtml ?></h1>
      <p class="home-hero__description"><?= e($heroDescription) ?></p>
      
      <div class="home-hero__actions">
        <a href="<?= base_url('productos') ?>" class="home-hero__btn home-hero__btn--primary">Ver catalogo completo</a>
      </div>
      
      <p class="home-hero__trustline">
        <span class="home-hero__trustline-item">
          <span class="home-hero__trustline-icon" aria-hidden="true"><?= ui_icon('check-line') ?></span>
          <span>Envios a todo el pais</span>
        </span>
        <span class="home-hero__trustline-item">
          <span class="home-hero__trustline-icon" aria-hidden="true"><?= ui_icon('check-line') ?></span>
          <span>Garantia oficial</span>
        </span>
      </p>
    </div>

    <div class="home-hero__media">
      <div class="home-hero__carousel" data-hero-carousel aria-label="Productos destacados" style="--home-hero-slide-bg: url('<?= e($heroSlideBg) ?>');">
        <div class="home-hero__carousel-track" data-hero-track>
          <?php $loopIndex = 0; ?>
          <?php foreach ($heroSlides as $idx => $slide): ?>
            <?php
              $desktop = (string)($slide['image_desktop'] ?? '');
              $mobile = (string)($slide['image_mobile'] ?? '');
              if ($desktop === '') {
                $desktop = asset('img/hero/bendy.png');
              }
              if ($mobile === '') {
                $mobile = $desktop;
              }
              $target = trim((string)($slide['target_url'] ?? base_url('productos')));
              $alt = trim((string)($slide['alt_text'] ?? 'Producto destacado'));
              $slideLabel = trim((string)($slide['short_name'] ?? $slide['title'] ?? ''));
              $slideTag = trim((string)($slide['tag'] ?? ''));
              $isActive = ($loopIndex === 0);
            ?>
            <figure class="home-hero__slide <?= $isActive ?'is-active' : '' ?>" data-hero-slide>
              <a href="<?= e($target) ?>" class="home-hero__slide-link" aria-label="<?= e($alt) ?>">
                <picture>
                  <?php if ($mobile !== ''): ?>
                    <source media="(max-width: 767px)" srcset="<?= e($mobile) ?>">
                  <?php endif; ?>
                  <img
                    src="<?= e($desktop) ?>"
                    alt="<?= e($alt) ?>"
                    class="home-hero__image"
                    loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>"
                    decoding="async"
                  >
                </picture>
                <?php if ($slideLabel !== '' || $slideTag !== ''): ?>
                  <span class="home-hero__slide-overlay">
                    <?php if ($slideTag !== ''): ?>
                      <span class="home-hero__slide-tag"><?= e($slideTag) ?></span>
                    <?php endif; ?>
                    <?php if ($slideLabel !== ''): ?>
                      <span class="home-hero__slide-title"><?= e($slideLabel) ?></span>
                    <?php endif; ?>
                  </span>
                <?php endif; ?>
              </a>
            </figure>
            <?php $loopIndex++; ?>
          <?php endforeach; ?>
        </div>

        <button class="home-hero__control home-hero__control--prev" type="button" data-hero-prev aria-label="Imagen anterior">
          <span aria-hidden="true">&lsaquo;</span>
        </button>
        <button class="home-hero__control home-hero__control--next" type="button" data-hero-next aria-label="Imagen siguiente">
          <span aria-hidden="true">&rsaquo;</span>
        </button>

        <div class="home-hero__dots" role="tablist" aria-label="Selector de imagen">
          <?php $dotIndex = 0; ?>
          <?php foreach ($heroSlides as $idx => $slide): ?>
            <?php $isDotActive = ($dotIndex === 0); ?>
            <button
              class="home-hero__dot <?= $isDotActive ?'is-active' : '' ?>"
              type="button"
              data-hero-dot="<?= (int)$dotIndex ?>"
              aria-current="<?= $isDotActive ? 'true' : 'false' ?>"
              aria-label="Imagen <?= (int)($dotIndex + 1) ?>"
            ></button>
            <?php $dotIndex++; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
