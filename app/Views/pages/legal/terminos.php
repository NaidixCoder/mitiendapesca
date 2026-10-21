<?php
$pageTitle = 'Términos y Condiciones';
$pageId = 'legal-terms';
$version = function_exists('env') ? (string) env('TERMS_VERSION', '') : '';
$verLabel = $version !== '' ? $version : 'sin versión';
?>

<section class="legal-page">
  <div class="legal-page__shell">
    <header class="legal-hero">
      <div class="legal-hero__content">
        <div class="legal-hero__eyebrow">Legal</div>
        <h1 class="legal-hero__title">Términos y Condiciones</h1>
        <p class="legal-hero__lead">
          Estos términos regulan el uso del sitio y las compras realizadas en <strong>Mi Tienda Pesca</strong>.
          Al navegar o comprar, aceptás estas condiciones.
        </p>
        <div class="legal-hero__meta">
          <span class="legal-hero__meta-dot" aria-hidden="true"></span>
          <span>Última actualización: <strong><?= htmlspecialchars($verLabel, ENT_QUOTES, 'UTF-8') ?></strong></span>
        </div>
      </div>

      <div class="legal-hero__actions">
        <a class="v2-btn v2-btn--secondary" href="<?= url('/') ?>">Volver al inicio</a>
        <a class="v2-btn v2-btn--primary" href="<?= url('/privacidad') ?>">Ver privacidad</a>
      </div>
    </header>

    <div class="legal-page__grid">
      <aside class="legal-index">
        <h2 class="legal-index__title">Índice</h2>
        <nav class="legal-index__nav" aria-label="Secciones de términos y condiciones">
          <a class="legal-index__link" href="#alcance">1. Alcance</a>
          <a class="legal-index__link" href="#cuenta">2. Cuenta y registro</a>
          <a class="legal-index__link" href="#compras">3. Compras y pagos</a>
          <a class="legal-index__link" href="#envios">4. Envíos y entregas</a>
          <a class="legal-index__link" href="#cambios">5. Cambios y devoluciones</a>
          <a class="legal-index__link" href="#garantias">6. Garantías</a>
          <a class="legal-index__link" href="#responsabilidad">7. Responsabilidad</a>
          <a class="legal-index__link" href="#propiedad">8. Propiedad intelectual</a>
          <a class="legal-index__link" href="#contacto">9. Contacto</a>
        </nav>
        <div class="legal-index__note">
          Si no estás de acuerdo con estos términos, por favor no utilices el sitio ni realices compras.
        </div>
      </aside>

      <main class="legal-page__content">
        <section class="legal-section" id="alcance">
          <h2 class="legal-section__title">1. Alcance</h2>
          <div class="legal-section__body">
            <p>
              Estos Términos regulan el acceso, la navegación y el uso del sitio, así como las compras de productos.
              La utilización del sitio implica la aceptación de estas condiciones.
            </p>
            <p>
              Podemos actualizar estos Términos cuando sea necesario. La versión vigente será la publicada en esta misma página.
            </p>
          </div>
        </section>

        <section class="legal-section" id="cuenta">
          <h2 class="legal-section__title">2. Cuenta y registro</h2>
          <div class="legal-section__body">
            <p>
              Para comprar o acceder a ciertas funciones puede ser necesario crear una cuenta. Sos responsable de mantener
              la confidencialidad de tus credenciales y de la actividad realizada desde tu cuenta.
            </p>
            <p>
              La información que ingreses debe ser verdadera y estar actualizada. Podemos suspender cuentas ante usos indebidos
              o indicios de fraude.
            </p>
          </div>
        </section>

        <section class="legal-section" id="compras">
          <h2 class="legal-section__title">3. Compras y pagos</h2>
          <div class="legal-section__body">
            <p>
              Los precios se informan en el sitio y pueden cambiar sin previo aviso. La disponibilidad de stock se confirma
              durante el procesamiento del pedido.
            </p>
            <p>
              El pago puede realizarse por los medios habilitados en el checkout. La confirmación del pedido se notificará
              por los canales informados por el usuario.
            </p>
          </div>
        </section>

        <section class="legal-section" id="envios">
          <h2 class="legal-section__title">4. Envíos y entregas</h2>
          <div class="legal-section__body">
            <p>
              Los plazos y costos de envío se informan durante el proceso de compra. La entrega depende de la opción elegida,
              la disponibilidad y el operador logístico.
            </p>
            <p>
              Es responsabilidad del cliente brindar datos correctos para la entrega. Reintentos o devoluciones por domicilio
              incorrecto pueden generar costos adicionales.
            </p>
          </div>
        </section>

        <section class="legal-section" id="cambios">
          <h2 class="legal-section__title">5. Cambios y devoluciones</h2>
          <div class="legal-section__body">
            <p>
              Podés solicitar cambios o devoluciones según las condiciones publicadas por la tienda y la normativa aplicable.
              Algunos productos pueden quedar excluidos por su naturaleza o por presentar uso.
            </p>
            <p>
              Para gestionar una devolución o un cambio, seguí los pasos indicados en la sección de ayuda o escribinos por
              los canales disponibles en el sitio.
            </p>
          </div>
        </section>

        <section class="legal-section" id="garantias">
          <h2 class="legal-section__title">6. Garantías</h2>
          <div class="legal-section__body">
            <p>
              Los productos cuentan con las garantías legales y, cuando corresponda, con la cobertura ofrecida por el fabricante.
              El alcance puede variar según el tipo de producto y su uso.
            </p>
          </div>
        </section>

        <section class="legal-section" id="responsabilidad">
          <h2 class="legal-section__title">7. Responsabilidad</h2>
          <div class="legal-section__body">
            <p>
              No seremos responsables por daños indirectos o pérdidas derivadas del uso del sitio o de demoras atribuibles a terceros,
              como operadores de pago o logística, en la medida permitida por la normativa aplicable.
            </p>
          </div>
        </section>

        <section class="legal-section" id="propiedad">
          <h2 class="legal-section__title">8. Propiedad intelectual</h2>
          <div class="legal-section__body">
            <p>
              Los contenidos del sitio, incluyendo marcas, logos, textos, imágenes y recursos gráficos, son propiedad de
              Mi Tienda Pesca o de sus respectivos titulares. No está permitido su uso sin autorización.
            </p>
          </div>
        </section>

        <section class="legal-section" id="contacto">
          <h2 class="legal-section__title">9. Contacto</h2>
          <div class="legal-section__body">
            <p>
              Si necesitás asistencia o querés realizar un reclamo, escribinos desde
              <a href="<?= url('/ayuda#contacto') ?>">Ayuda y contacto</a>.
            </p>
          </div>
        </section>

      </main>
    </div>
  </div>
</section>
