<section class="home-sec home-newsletter" aria-label="Newsletter">
  <div class="home-sec__container">
    <div class="home-newsletter__panel">
      <div class="home-newsletter__content">
        <h3 class="home-newsletter__title">Ofertas, lanzamientos y tips</h3>
        <p class="home-newsletter__subtitle">¡Recibi novedades antes que nadie!</p>
      </div>

      <form class="home-newsletter__form" method="post" action="<?= url('/newsletter') ?>">
        <?= csrf_field() ?>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="home-newsletter__honeypot" aria-hidden="true">
        <label class="home-newsletter__label" for="home-newsletter-email">Email</label>
        <input
          id="home-newsletter-email"
          type="email"
          name="email"
          required
          class="home-newsletter__input"
          placeholder="tu@email.com"
        >
        <button class="home-newsletter__button" type="submit">Suscribirme</button>
      </form>
    </div>
  </div>
</section>
