<?php
/** @var bool $isRI */
$crumbs = [
  ['label' => 'Admin', 'href' => base_url('admin')],
  ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
  ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
];
?>
<?php render_partial('admin/subnav'); ?>
<main class="av3-fiscal-page av3-fiscal-report-hub">
  <?php
  $adminHeaderTitle = 'Reportes fiscales';
  $adminHeaderDescription = 'Consultas y conciliación de la operación fiscal.';
  ob_start(); ?>
    <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/control')) ?>">Volver al control</a>
  <?php
  $adminHeaderActions = ob_get_clean();
  $adminHeaderBreadcrumbs = $crumbs;
  require BASE_PATH . '/app/Views/partials/admin/page_header.php';
  render_partial('admin/facturacion_nav');
  ?>
  <section class="av3-fiscal-section" aria-labelledby="reportes-principal">
    <header class="av3-fiscal-section-heading">
      <div>
        <h2 id="reportes-principal">Análisis de facturación</h2>
        <p>Información fiscal consolidada y diferencias para revisar.</p>
      </div>
    </header>
    <div class="av3-fiscal-report-grid">
      <article class="av3-fiscal-report-tile av3-fiscal-report-tile--featured">
        <span class="av3-fiscal-eyebrow">Visión general</span>
        <h3>Resumen y conciliación</h3>
        <p>Facturas y notas de crédito, series diarias y mensuales, diferencias entre pedidos y comprobantes.</p>
        <a class="btn btn-primary" href="<?= e(base_url('admin/facturacion/reportes/resumen')) ?>">Consultar resumen</a>
      </article>
      <article class="av3-fiscal-report-tile">
        <span class="av3-fiscal-eyebrow">Seguimiento operativo</span>
        <h3>Comprobantes pendientes</h3>
        <p>Emisiones pendientes, errores y casos que requieren intervención.</p>
        <div class="av3-fiscal-tile-actions">
          <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/reportes/comprobantes-pendientes')) ?>">Ver pendientes</a>
          <a class="av3-fiscal-link" href="<?= e(base_url('admin/facturacion/alertas')) ?>">Alertas fiscales</a>
        </div>
      </article>
    </div>
  </section>
  <?php if ($isRI): ?>
    <section class="av3-fiscal-section" aria-labelledby="reportes-iva">
      <header class="av3-fiscal-section-heading">
        <div>
          <h2 id="reportes-iva">IVA y períodos</h2>
          <p>Resultados mensuales calculados y saldos disponibles.</p>
        </div>
      </header>
      <div class="av3-fiscal-report-grid">
        <article class="av3-fiscal-report-tile">
          <span class="av3-fiscal-eyebrow">IVA</span>
          <h3>Reporte mensual</h3>
          <p>IVA Ventas, IVA Compras y saldo por período. Respeta los cierres existentes.</p>
          <div class="av3-fiscal-tile-actions">
            <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/reportes/mensual')) ?>">Consultar reporte</a>
            <a class="av3-fiscal-link" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Ver cierres</a>
          </div>
        </article>
        <article class="av3-fiscal-report-tile">
          <span class="av3-fiscal-eyebrow">IVA</span>
          <h3>Saldo por mes</h3>
          <p>Consulta compacta de saldo IVA y comparación entre períodos.</p>
          <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/reportes/saldo-iva')) ?>">Ver saldos</a>
        </article>
      </div>
    </section>
  <?php else: ?>
    <section class="av3-fiscal-explainer" aria-label="Información de régimen fiscal">
      <h2>Información para monotributo</h2>
      <p>Para este régimen, priorizá el resumen, la conciliación y el seguimiento de pendientes. Los reportes específicos de IVA están destinados a responsables inscriptos.</p>
      <a class="av3-fiscal-link" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Consultar cierres mensuales</a>
    </section>
  <?php endif; ?>
</main>
