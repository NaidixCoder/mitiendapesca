<?php
$pageTitle = 'Ayuda';
$pageId = 'help';
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
?>

<section class="help-page">
  <div class="help-page__shell">
    <header class="help-page__hero">
      <div class="help-page__eyebrow">Centro de ayuda</div>
      <h1 class="help-page__title">Te ayudamos a resolver tu compra</h1>
      <p class="help-page__lead">
        Encontrá información clara sobre envíos, devoluciones, medios de pago y contacto para comprar con más tranquilidad.
      </p>

      <nav class="help-page__jump-nav" aria-label="Secciones de ayuda">
        <a href="#envios" class="help-page__jump-link">Envíos</a>
        <a href="#devoluciones" class="help-page__jump-link">Devoluciones</a>
        <a href="#pagos" class="help-page__jump-link">Medios de pago</a>
        <a href="#contacto" class="help-page__jump-link">Contacto</a>
      </nav>
    </header>

    <div class="help-page__grid">
      <article class="help-card help-card--highlight" id="envios">
        <div class="help-card__kicker">Envíos</div>
        <h2 class="help-card__title">Cómo trabajamos los envíos</h2>
        <p class="help-card__text">
          Mostramos opciones de envío en el checkout según tu dirección y los productos del carrito. Vas a poder elegir la alternativa disponible que mejor se adapte a tu compra.
        </p>
        <ul class="help-card__list">
          <li>El costo y la disponibilidad se calculan antes de confirmar el pedido.</li>
          <li>Si retirás en el local, esa opción aparece directamente en el checkout.</li>
          <li>Una vez despachado, el seguimiento se consulta desde tus pedidos.</li>
        </ul>
      </article>

      <article class="help-card" id="devoluciones">
        <div class="help-card__kicker">Devoluciones</div>
        <h2 class="help-card__title">Cambios, devoluciones y problemas</h2>
        <p class="help-card__text">
          Si tu pedido llegó con un inconveniente o necesitás gestionar un cambio, escribinos para revisar el caso y acompañarte con los próximos pasos.
        </p>
        <ul class="help-card__list">
          <li>Guardá el número de pedido para que podamos ayudarte más rápido.</li>
          <li>Si el producto llegó dañado o incompleto, contactanos apenas lo recibas.</li>
          <li>Te indicaremos el canal y la forma de resolución según el caso.</li>
        </ul>
      </article>

      <article class="help-card" id="pagos">
        <div class="help-card__kicker">Pagos</div>
        <h2 class="help-card__title">Medios de pago disponibles</h2>
        <p class="help-card__text">
          Podés comprar con tarjeta o por transferencia, según las opciones habilitadas en el checkout. Cada método muestra su condición comercial antes de confirmar.
        </p>
        <ul class="help-card__list">
          <li>Las cuotas y promociones se informan sobre cada producto y en el checkout.</li>
          <li>Si pagás por transferencia, los datos se muestran al confirmar el pedido.</li>
          <li>El estado del pago y del pedido se sigue desde tu cuenta.</li>
        </ul>
      </article>

      <article class="help-card" id="contacto">
        <div class="help-card__kicker">Contacto</div>
        <h2 class="help-card__title">Canales para escribirnos</h2>
        <p class="help-card__text">
          Si te quedó una duda o querés ayuda con tu compra, podés escribirnos por los canales disponibles abajo.
        </p>

        <div class="help-contact">
          <?php if ($whatsUrl !== ''): ?>
            <a class="v2-btn v2-btn--primary help-contact__cta" href="<?= e($whatsUrl) ?>" target="_blank" rel="noopener noreferrer">
              <span class="help-contact__cta-icon" aria-hidden="true"><?= ui_icon('whatsapp-brand') ?></span>
              <span>WhatsApp</span>
            </a>
            <p class="help-contact__note">Te respondemos lo antes posible.</p>
          <?php else: ?>
            <div class="help-inline-help">
              <div class="help-inline-help__title">WhatsApp no configurado</div>
              <div class="help-inline-help__text">Todavía no hay un enlace de WhatsApp disponible para mostrar en esta página.</div>
            </div>
          <?php endif; ?>
        </div>
      </article>
    </div>

    <section class="help-page__tips" aria-label="Accesos rápidos">
      <h2 class="help-page__section-title">Accesos rápidos</h2>
      <div class="help-page__tips-grid">
        <article class="help-tip">
          <h3 class="help-tip__title">Mis pedidos</h3>
          <p class="help-tip__text">Consultá el estado de tu compra y seguí el avance del pedido desde tu cuenta.</p>
          <a class="help-tip__link" href="<?= url('/cuenta/pedidos') ?>">Ir a Mis pedidos</a>
        </article>

        <article class="help-tip">
          <h3 class="help-tip__title">Mi cuenta</h3>
          <p class="help-tip__text">Completá tus datos y direcciones para acelerar el checkout.</p>
          <a class="help-tip__link" href="<?= url('/cuenta') ?>">Ir a Mi cuenta</a>
        </article>

        <article class="help-tip">
          <h3 class="help-tip__title">Catálogo</h3>
          <p class="help-tip__text">Volvé a explorar productos, marcas y categorías de la tienda.</p>
          <a class="help-tip__link" href="<?= url('/productos') ?>">Ver productos</a>
        </article>
      </div>
    </section>
  </div>
</section>
