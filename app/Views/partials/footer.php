<?php
$socialLinks = [
  'instagram' => trim((string)app_setting('social_instagram_url', '')),
  'facebook' => trim((string)app_setting('social_facebook_url', '')),
  'youtube' => trim((string)app_setting('social_youtube_url', '')),
  'whatsapp' => trim((string)app_setting('social_whatsapp_url', '')),
];
?>
<footer class="pf-footer">
  <div class="pf-shell">
    <div class="pf-main">
      <section class="pf-brand">
        <a href="<?= url('/') ?>" class="pf-brand__link">
          <span class="pf-brand__title">Mi Tienda Pesca</span>
        </a>
        <p class="pf-brand__copy">Seleccion de equipos, asesoramiento exlusivo y envios a todo el pais.</p>
        <div class="pf-social" aria-label="Redes sociales">
          <a
            href="<?= e($socialLinks['instagram'] !== '' ? $socialLinks['instagram'] : '#') ?>"
            aria-label="Instagram"
            class="pf-social__link pf-social__link--instagram<?= $socialLinks['instagram'] === '' ? ' is-disabled' : '' ?>"
            <?= $socialLinks['instagram'] !== '' ? 'target="_blank" rel="noopener noreferrer"' : 'aria-disabled="true" tabindex="-1"' ?>
          >
            <?= ui_icon('instagram-brand') ?>
          </a>
          <a
            href="<?= e($socialLinks['facebook'] !== '' ? $socialLinks['facebook'] : '#') ?>"
            aria-label="Facebook"
            class="pf-social__link pf-social__link--facebook<?= $socialLinks['facebook'] === '' ? ' is-disabled' : '' ?>"
            <?= $socialLinks['facebook'] !== '' ? 'target="_blank" rel="noopener noreferrer"' : 'aria-disabled="true" tabindex="-1"' ?>
          >
            <?= ui_icon('facebook-brand') ?>
          </a>
          <a
            href="<?= e($socialLinks['youtube'] !== '' ? $socialLinks['youtube'] : '#') ?>"
            aria-label="YouTube"
            class="pf-social__link pf-social__link--youtube<?= $socialLinks['youtube'] === '' ? ' is-disabled' : '' ?>"
            <?= $socialLinks['youtube'] !== '' ? 'target="_blank" rel="noopener noreferrer"' : 'aria-disabled="true" tabindex="-1"' ?>
          >
            <?= ui_icon('youtube-brand') ?>
          </a>
          <a
            href="<?= e($socialLinks['whatsapp'] !== '' ? $socialLinks['whatsapp'] : '#') ?>"
            aria-label="WhatsApp"
            class="pf-social__link pf-social__link--whatsapp<?= $socialLinks['whatsapp'] === '' ? ' is-disabled' : '' ?>"
            <?= $socialLinks['whatsapp'] !== '' ? 'target="_blank" rel="noopener noreferrer"' : 'aria-disabled="true" tabindex="-1"' ?>
          >
            <?= ui_icon('whatsapp-brand') ?>
          </a>
        </div>
      </section>

      <nav class="pf-links" aria-label="Enlaces del footer">
        <section class="pf-group">
          <h2 class="pf-group__title">Tienda</h2>
          <ul class="pf-group__list">
            <li><a href="<?= url('/productos') ?>" class="pf-link">Productos</a></li>
            <li><a href="<?= url('/marcas') ?>" class="pf-link">Marcas</a></li>
            <li><a href="<?= url('/categorias') ?>" class="pf-link">Categorias</a></li>
          </ul>
        </section>

        <section class="pf-group">
          <h2 class="pf-group__title">Ayuda</h2>
          <ul class="pf-group__list">
            <li><a href="<?= url('/ayuda#envios') ?>" class="pf-link">Envios</a></li>
            <li class="pf-link--desktop-only"><a href="<?= url('/ayuda#devoluciones') ?>" class="pf-link">Devoluciones</a></li>
            <li class="pf-link--desktop-only"><a href="<?= url('/ayuda#pagos') ?>" class="pf-link">Pagos</a></li>
            <li><a href="<?= url('/ayuda#contacto') ?>" class="pf-link">Contacto</a></li>
          </ul>
        </section>

        <section class="pf-group pf-group--full-mobile">
          <h2 class="pf-group__title">Legal</h2>
          <ul class="pf-group__list">
            <li><a href="<?= url('/terminos') ?>" class="pf-link">Terminos</a></li>
            <li><a href="<?= url('/privacidad') ?>" class="pf-link">Privacidad</a></li>
          </ul>
        </section>
      </nav>

      <section class="pf-newsletter" aria-label="Newsletter">
        <h2 class="pf-group__title">Newsletter</h2>
        <p class="pf-newsletter__copy">Solo contenido util. Nada de spam.</p>
        <form class="pf-newsletter__form" method="post" action="<?= url('/newsletter') ?>">
          <?= csrf_field() ?>
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="pf-newsletter__honeypot" aria-hidden="true">
          <input
            type="email"
            name="email"
            required
            placeholder="tu@email"
            class="pf-newsletter__input"
          >
          <button type="submit" class="pf-newsletter__button">Suscribirme</button>
        </form>
        <p class="pf-newsletter__note">Podes darte de baja cuando quieras.</p>
      </section>
    </div>
  </div>

  <div class="pf-base">
    <div class="pf-shell pf-base__inner">
      <span class="pf-base__copy">&copy; <?= date('Y') ?> Mi Tienda Pesca</span>
      <div class="pf-base__meta">
        <span class="pf-base__trust">
          <span class="pf-base__trust-icon" aria-hidden="true"><?= ui_icon('shield-solid') ?></span>
          <span>Compra segura</span>
        </span>
        <span class="pf-base__credit">Sitio desarrollado por Matias Infante</span>
      </div>
    </div>
  </div>
</footer>
