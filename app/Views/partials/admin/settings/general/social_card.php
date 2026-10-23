<section id="ajustes-contacto" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-settings-detail__heading">Contacto y redes sociales</h2>

  <label class="av3-block">
    <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Email de contacto</div>
    <input
      type="email"
      name="contact_support_email"
      value="<?= e((string)($cfg['contact_support_email'] ?? '')) ?>"
      class="input av3-width-full"
      placeholder="hola@mitienda.com"
    >
  </label>

  <div class="av3-grid av3-gap-3 av3-md-grid-cols-2">
    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Instagram</div>
      <input
        type="url"
        name="social_instagram_url"
        value="<?= e((string)($cfg['social_instagram_url'] ?? '')) ?>"
        class="input av3-width-full"
        placeholder="https://instagram.com/tu-cuenta"
      >
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Facebook</div>
      <input
        type="url"
        name="social_facebook_url"
        value="<?= e((string)($cfg['social_facebook_url'] ?? '')) ?>"
        class="input av3-width-full"
        placeholder="https://facebook.com/tu-pagina"
      >
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">YouTube</div>
      <input
        type="url"
        name="social_youtube_url"
        value="<?= e((string)($cfg['social_youtube_url'] ?? '')) ?>"
        class="input av3-width-full"
        placeholder="https://youtube.com/@tu-canal"
      >
    </label>

    <label class="av3-block">
      <div class="av3-type-body av3-weight-medium av3-space-bottom-1">WhatsApp</div>
      <input
        type="url"
        name="social_whatsapp_url"
        value="<?= e((string)($cfg['social_whatsapp_url'] ?? '')) ?>"
        class="input av3-width-full"
        placeholder="https://wa.me/549..."
      >
    </label>
  </div>

  <p class="av3-type-body av3-text-var-muted">
    Estas URLs se usan en los iconos del footer. Si dejas un campo vacio, ese icono quedara visible pero no sera clickeable.
  </p>
</section>
