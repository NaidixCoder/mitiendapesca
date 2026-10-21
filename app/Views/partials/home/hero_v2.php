<?php
$heroSlides = is_array($heroSlides ?? null) ? $heroSlides : [];
if (empty($heroSlides)) return;

$heroTitle = trim((string)($heroTitle ?? ''));
if ($heroTitle === '') {
  $heroTitle = 'Equipa tu proxima [[accent]]pesca[[/accent]] con productos de calidad';
}

$heroDescription = trim((string)($heroDescription ?? ''));
if ($heroDescription === '') {
  $heroDescription = home_hero_description_default();
}

$homeCategories = is_array($homeCategories ?? null) ? $homeCategories : [];
$heroTitleHtml = home_hero_title_html($heroTitle);
$heroSlideBg = asset('img/hero/background-hero2.jpg');
?>

<section class="home-hero-v2" aria-label="Hero principal">
  <div class="home-hero-v2__container home-sec__container">
    <div class="home-hero-v2__intro">
      <span class="home-hero-v2__eyebrow">
        <span class="home-hero-v2__eyebrow-dot" aria-hidden="true"></span>
        Destacados 2026
      </span>
      <h1 class="home-hero-v2__title"><?= $heroTitleHtml ?></h1>
      <p class="home-hero-v2__desc"><?= e($heroDescription) ?></p>
      <a href="<?= e(base_url('productos')) ?>" class="home-hero-v2__catalog-btn">Ver catalogo completo</a>
      <p class="home-hero-v2__trust">
        <span><?= ui_icon('check-line') ?> Envios a todo el pais</span>
        <span><?= ui_icon('check-line') ?> Garantia oficial</span>
      </p>
    </div>

    <div class="home-hero-v2__showcase">
      <div class="home-hero-v2__media" style="--home-hero-v2-bg: url('<?= e($heroSlideBg) ?>');">
        <?php
          $chipSource = $heroSlides[0] ?? [];
          $chipTag = trim((string)($chipSource['tag'] ?? 'SENUELO DESTACADO'));
        ?>
        <span class="home-hero-v2__chip home-hero-v2__chip--top"><?= ui_icon('star-solid') ?> <?= e($chipTag) ?></span>

        <div class="home-hero-v2__carousel" data-hero-carousel aria-label="Productos destacados">
          <div class="home-hero-v2__track" data-hero-track>
            <?php $loopIndex = 0; ?>
            <?php foreach ($heroSlides as $idx => $slide): ?>
              <?php
                $desktop = (string)($slide['image_desktop'] ?? '');
                $mobile = (string)($slide['image_mobile'] ?? '');
                if ($desktop === '') $desktop = asset('img/hero/bendy.png');
                if ($mobile === '') $mobile = $desktop;
                $target = trim((string)($slide['target_url'] ?? base_url('productos')));
                $alt = trim((string)($slide['alt_text'] ?? 'Producto destacado'));
                $slideLabel = trim((string)($slide['short_name'] ?? $slide['title'] ?? 'Producto destacado'));
                $slideDescription = trim((string)($slide['subtitle'] ?? 'Accion natural · Maxima efectividad en agua dulce y salada.'));
                $slidePrice = trim((string)($slide['price_label'] ?? '$ 12.490'));
                $isActive = ($loopIndex === 0);
              ?>
              <figure class="home-hero-v2__slide <?= $isActive ?'is-active' : '' ?>" data-hero-slide>
                <a href="<?= e($target) ?>" class="home-hero-v2__slide-link" aria-label="<?= e($alt) ?>">
                  <picture>
                    <?php if ($mobile !== ''): ?>
                      <source media="(max-width: 767px)" srcset="<?= e($mobile) ?>">
                    <?php endif; ?>
                    <img
                      src="<?= e($desktop) ?>"
                      alt="<?= e($alt) ?>"
                      class="home-hero-v2__image"
                      loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>"
                      decoding="async"
                    >
                  </picture>
                </a>

                <div class="home-hero-v2__overlay">
                  <span class="home-hero-v2__line" aria-hidden="true"></span>
                  <h2 class="home-hero-v2__product-title"><?= e($slideLabel) ?></h2>
                  <p class="home-hero-v2__product-desc"><?= e($slideDescription) ?></p>
                  <div class="home-hero-v2__cta-row">
                    <span class="home-hero-v2__price"><?= e($slidePrice) ?></span>
                    <a href="<?= e($target) ?>" class="home-hero-v2__product-btn">
                      <?= ui_icon('cart-outline') ?>
                      <span>Ver producto</span>
                    </a>
                  </div>
                </div>
              </figure>
              <?php $loopIndex++; ?>
            <?php endforeach; ?>
          </div>

          <div class="home-hero-v2__dots" role="tablist" aria-label="Selector de imagen">
            <?php $dotIndex = 0; ?>
            <?php foreach ($heroSlides as $slide): ?>
              <?php $isDotActive = ($dotIndex === 0); ?>
              <button
                class="home-hero-v2__dot <?= $isDotActive ?'is-active' : '' ?>"
                type="button"
                data-hero-dot="<?= (int)$dotIndex ?>"
                aria-current="<?= $isDotActive ? 'true' : 'false' ?>"
                aria-label="Imagen <?= (int)($dotIndex + 1) ?>"
              ></button>
              <?php $dotIndex++; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if (!empty($homeCategories)): ?>
          <div class="home-hero-v2__cats">
            <?php foreach (array_slice($homeCategories, 0, 4) as $c): ?>
              <?php
                $img = (string)($c['img'] ?? '');
                $title = (string)($c['title'] ?? 'Categoria');
                $href = (string)($c['href'] ?? '#');
              ?>
              <a class="home-hero-v2__cat" href="<?= e($href) ?>">
                <span class="home-hero-v2__cat-media">
                  <img src="<?= e($img) ?>" alt="<?= e($title) ?>" class="home-hero-v2__cat-img">
                </span>
                <span class="home-hero-v2__cat-body">
                  <span class="home-hero-v2__cat-copy">
                    <span class="home-hero-v2__cat-title"><?= e($title) ?></span>
                    <span class="home-hero-v2__cat-link">Ver mas</span>
                  </span>
                  <span class="home-hero-v2__cat-chevron" aria-hidden="true">
                    <?= ui_icon('chevron-right-solid') ?>
                  </span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
