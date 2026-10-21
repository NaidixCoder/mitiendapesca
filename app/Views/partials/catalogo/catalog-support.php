<?php
$whatsUrl = trim((string)($whatsUrl ?? ''));
?>

<section class="catalog-support" aria-label="Informacion del catalogo">
  <div class="catalog-support__panel">
    <p class="catalog-support__eyebrow">Catalogo</p>
    <h2 class="catalog-support__title">¿No encontrás lo que buscas?</h2>
    <p class="catalog-support__copy">
      Explorá por categorias y marcas. Cada producto del catalogo fue elegido por experiencia, pruebas y resultados en el agua. Si lo vendo, es porque lo usarí­a.
      Si no encontras lo que buscas, te ayudamos por WhatsApp.
    </p>

    <div class="catalog-support__actions">
      <?php if ($whatsUrl !== ''): ?>
        <a
          class="catalog-support__cta-btn"
          href="<?= e($whatsUrl) ?>"
          target="_blank"
          rel="noopener noreferrer"
        >
          <span class="catalog-support__cta-icon" aria-hidden="true">
            <?= ui_icon('whatsapp-brand') ?>
          </span>
          Consultar por WhatsApp
        </a>
      <?php endif; ?>

    </div>
  </div>
</section>
