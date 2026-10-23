<?php
$groups = [
  ['title' => 'Tienda y catálogo', 'description' => 'Visibilidad del catálogo, contacto y redes sociales.', 'items' => [['General', '/admin/settings?tab=general']]],
  ['title' => 'Ventas y precios', 'description' => 'Cuotas, descuentos, reservas por transferencia y precios mayoristas.', 'items' => [['Pagos y condiciones', '/admin/settings?tab=pagos'], ['Mayoristas', '/admin/settings?tab=mayoristas']]],
  ['title' => 'Envíos y logística', 'description' => 'Correo Argentino, retiro local, cotización, embalaje y remitente.', 'items' => [['Envíos', '/admin/settings?tab=envios']]],
  ['title' => 'Facturación e impuestos', 'description' => 'Situación fiscal, emisión ARCA y estados de conciliación.', 'items' => [['Facturación y ARCA', '/admin/settings?tab=fiscal']]],
  ['title' => 'Comunicaciones', 'description' => 'Suscripciones, automatizaciones, límites y ejecución de emails.', 'items' => [['Email marketing', '/admin/settings?tab=email-marketing']]],
  ['title' => 'Diseño y apariencia', 'description' => 'Carrusel principal, secciones de productos y contenido del inicio.', 'items' => [['Hero del inicio', '/admin/settings/design/home-hero'], ['Secciones del inicio', '/admin/settings/design/home-sections']]],
];
?>
<section class="av3-settings-center" aria-label="Centro de configuración">
  <p class="av3-type-body av3-text-var-muted">Configuraciones agrupadas según su función. Cada sección conserva sus parámetros y operaciones actuales.</p>
  <div class="av3-settings-center__grid">
    <?php foreach ($groups as $group): ?>
      <article class="av3-ui-card av3-settings-center__card">
        <div class="av3-ui-card-header"><h2 class="av3-ui-card-title"><?= e($group['title']) ?></h2></div>
        <div class="av3-ui-card-body">
          <p class="av3-type-body av3-text-var-muted"><?= e($group['description']) ?></p>
          <nav class="av3-settings-center__links" aria-label="<?= e($group['title']) ?>">
            <?php foreach ($group['items'] as $item): ?>
              <a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url($item[1])) ?>"><?= e($item[0]) ?> &rarr;</a>
            <?php endforeach; ?>
          </nav>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
