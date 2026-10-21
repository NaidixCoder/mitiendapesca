<?php
$pageTitle = 'Política de Privacidad';
$pageId = 'legal-privacy';
$version = function_exists('env') ? (string) env('PRIVACY_VERSION', '') : '';
$verLabel = $version !== '' ? $version : 'sin versión';
?>

<section class="legal-page">
  <div class="legal-page__shell">
    <header class="legal-hero">
      <div class="legal-hero__content">
        <div class="legal-hero__eyebrow">Legal</div>
        <h1 class="legal-hero__title">Política de Privacidad</h1>
        <p class="legal-hero__lead">
          Explicamos cómo recopilamos, usamos y protegemos tus datos personales cuando utilizás
          <strong>Mi Tienda Pesca</strong>.
        </p>
        <div class="legal-hero__meta">
          <span class="legal-hero__meta-dot" aria-hidden="true"></span>
          <span>Última actualización: <strong><?= htmlspecialchars($verLabel, ENT_QUOTES, 'UTF-8') ?></strong></span>
        </div>
      </div>

      <div class="legal-hero__actions">
        <a class="v2-btn v2-btn--secondary" href="<?= url('/') ?>">Volver al inicio</a>
        <a class="v2-btn v2-btn--primary" href="<?= url('/terminos') ?>">Ver términos</a>
      </div>
    </header>

    <div class="legal-page__grid">
      <aside class="legal-index">
        <h2 class="legal-index__title">Índice</h2>
        <nav class="legal-index__nav" aria-label="Secciones de política de privacidad">
          <a class="legal-index__link" href="#datos">1. Datos que recopilamos</a>
          <a class="legal-index__link" href="#uso">2. Uso de la información</a>
          <a class="legal-index__link" href="#cookies">3. Cookies y sesiones</a>
          <a class="legal-index__link" href="#seguridad">4. Seguridad de los datos</a>
          <a class="legal-index__link" href="#derechos">5. Derechos del usuario</a>
          <a class="legal-index__link" href="#contacto">6. Contacto</a>
        </nav>
      </aside>

      <main class="legal-page__content">
        <section class="legal-section" id="datos">
          <h2 class="legal-section__title">1. Datos que recopilamos</h2>
          <div class="legal-section__body">
            <p>
              Recopilamos los datos necesarios para crear y gestionar tu cuenta, procesar pedidos, brindar soporte
              y cumplir obligaciones legales.
            </p>
            <p>
              Esto puede incluir datos de identificación, contacto, información de compra y datos técnicos relacionados
              con el uso del sitio.
            </p>
          </div>
        </section>

        <section class="legal-section" id="uso">
          <h2 class="legal-section__title">2. Uso de la información</h2>
          <div class="legal-section__body">
            <p>
              Utilizamos la información para operar el sitio, procesar pedidos, mejorar la experiencia del usuario
              y cumplir con requisitos legales y regulatorios.
            </p>
            <p>
              No utilizamos tus datos para fines distintos a los aquí descriptos sin tu consentimiento.
            </p>
          </div>
        </section>

        <section class="legal-section" id="cookies">
          <h2 class="legal-section__title">3. Cookies y sesiones</h2>
          <div class="legal-section__body">
            <p>
              Utilizamos cookies y tecnologías similares para mantener tu sesión iniciada, mejorar la seguridad
              y optimizar el funcionamiento del sitio.
            </p>
            <p>
              Podés configurar tu navegador para rechazar cookies, aunque algunas funcionalidades podrían verse afectadas.
            </p>
          </div>
        </section>

        <section class="legal-section" id="seguridad">
          <h2 class="legal-section__title">4. Seguridad de los datos</h2>
          <div class="legal-section__body">
            <p>
              Implementamos medidas técnicas y organizativas razonables para proteger tus datos frente a accesos no autorizados,
              pérdidas o alteraciones.
            </p>
            <p>
              Aun así, ningún sistema es completamente seguro, por lo que no podemos garantizar seguridad absoluta.
            </p>
          </div>
        </section>

        <section class="legal-section" id="derechos">
          <h2 class="legal-section__title">5. Derechos del usuario</h2>
          <div class="legal-section__body">
            <p>
              Podés ejercer tus derechos de acceso, rectificación, actualización o eliminación de tus datos personales,
              conforme a la normativa aplicable.
            </p>
            <p>
              Para hacerlo, escribinos por los canales publicados en el sitio.
            </p>
          </div>
        </section>

        <section class="legal-section" id="contacto">
          <h2 class="legal-section__title">6. Contacto</h2>
          <div class="legal-section__body">
            <p>
              Si tenés consultas sobre esta Política de Privacidad o querés ejercer tus derechos, escribinos desde
              <a href="<?= url('/ayuda#contacto') ?>">Ayuda y contacto</a>.
            </p>
          </div>
        </section>

      </main>
    </div>
  </div>
</section>
