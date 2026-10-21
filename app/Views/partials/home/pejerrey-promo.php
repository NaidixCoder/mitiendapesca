<?php
$waUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
?>
<section class="home-sec home-pejerrey" aria-label="Temporada de pejerrey">
  <div class="home-sec__container">
    <article class="home-pejerrey__hero">
      <div class="home-pejerrey__content">
        <span class="home-pejerrey__badge"><?= ui_icon('check-line') ?> Temporada de pejerrey</span>
        <h2 class="home-pejerrey__title">Prepará tu próxima salida de <span>pejerrey</span></h2>
        <p class="home-pejerrey__desc">
          Impulsá tu pesca con líneas artesanales, boyas, anzuelos y equipos seleccionados por rendimiento.
          Te ayudamos a elegir según tu forma de pescar.
        </p>
        <p class="home-pejerrey__micro">
          Más salida al agua, menos dudas: elegí rápido y llevate lo que realmente funciona.
        </p>
        <div class="home-pejerrey__actions">
          <a href="<?= url('/productos?q=pejerrey') ?>" class="home-pejerrey__btn home-pejerrey__btn--primary">Ver selección pejerrey</a>
          <?php if ($waUrl !== ''): ?>
            <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="home-pejerrey__btn home-pejerrey__btn--ghost">Consultar por WhatsApp</a>
          <?php endif; ?>
        </div>
        <div class="home-pejerrey__benefits" aria-label="Beneficios de compra">
          <span class="home-pejerrey__benefit"><?= ui_icon('box') ?> Líneas listas para usar</span>
          <span class="home-pejerrey__benefit"><?= ui_icon('user') ?> Asesoramiento personalizado</span>
          <span class="home-pejerrey__benefit"><?= ui_icon('truck-solid') ?> Envíos a todo el país</span>
        </div>
      </div>
      <div class="home-pejerrey__media" aria-hidden="true">
        <div class="home-pejerrey__chips">
          <span class="home-pejerrey__chip home-pejerrey__chip--solid"><?= ui_icon('star-solid') ?> Selección recomendada</span>
        </div>
        <img src="<?= e(asset('img/home/linea-07.jpg')) ?>" alt="" loading="lazy" decoding="async">
      </div>
    </article>

    <div class="home-pejerrey__grid">
      <a class="home-pejerrey__card" href="<?= url('/productos?q=lineas artesanales pejerrey') ?>">
        <span class="home-pejerrey__card-icon" aria-hidden="true"><?= ui_icon('document') ?></span>
        <h3>Líneas artesanales</h3>
        <p>Armadas para pejerrey en laguna y río.</p>
        <span class="home-pejerrey__card-cta">Ver productos</span>
      </a>
      <a class="home-pejerrey__card" href="<?= url('/productos?q=boyas pejerrey') ?>">
        <span class="home-pejerrey__card-icon" aria-hidden="true"><?= ui_icon('tag-solid') ?></span>
        <h3>Boyas</h3>
        <p>Modelos visibles para distintas condiciones.</p>
        <span class="home-pejerrey__card-cta">Ver productos</span>
      </a>
      <a class="home-pejerrey__card" href="<?= url('/productos?q=anzuelos pejerrey') ?>">
        <span class="home-pejerrey__card-icon" aria-hidden="true"><?= ui_icon('check-line') ?></span>
        <h3>Anzuelos</h3>
        <p>Opciones confiables para más clavadas.</p>
        <span class="home-pejerrey__card-cta">Ver productos</span>
      </a>
      <a class="home-pejerrey__card" href="<?= url('/productos?q=equipo pejerrey') ?>">
        <span class="home-pejerrey__card-icon" aria-hidden="true"><?= ui_icon('box') ?></span>
        <h3>Equipos completos</h3>
        <p>Combos para salir a pescar sin vueltas.</p>
        <span class="home-pejerrey__card-cta">Ver productos</span>
      </a>
    </div>
  </div>
</section>
