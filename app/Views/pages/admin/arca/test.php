<?php
$pageTitle = 'ARCA · Diagnóstico';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('arca.test');
$result = $result ?? [];
$cfg = $result['config'] ?? [];
$warnings = $result['warnings'] ?? [];
$steps = $result['steps'] ?? [];
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php
ob_start(); ?>ARCA · Diagnóstico<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Chequeo rápido de <strong>WSAA</strong> y <strong>WSFE</strong> (homologación/producción). No emite comprobantes.<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
        <a class="btn-secondary" href="<?= e(url('/admin/settings?tab=fiscal')) ?>">Volver a Ajustes · Fiscal</a>
        <a class="btn-secondary" target="_blank" href="<?= e(url('/admin/arca/test.json')) ?>">Ver JSON</a>
      <?php $adminHeaderActions=ob_get_clean();
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'ARCA','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <section class="av3-card av3-flow-3">
      <h2 class="av3-type-base av3-weight-strong">Configuración detectada</h2>
      <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3 av3-type-body">
        <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
          <div class="av3-type-caption av3-text-var-muted">Entorno</div>
          <div class="av3-weight-medium"><?= e((string)($cfg['env'] ?? '')) ?></div>
        </div>
        <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
          <div class="av3-type-caption av3-text-var-muted">Punto de venta</div>
          <div class="av3-weight-medium"><?= e((string)($cfg['pto_vta'] ?? '')) ?></div>
        </div>
        <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
          <div class="av3-type-caption av3-text-var-muted">CUIT (oculto)</div>
          <div class="av3-weight-medium"><?= e((string)($cfg['cuit'] ?? '')) ?></div>
        </div>
        <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
          <div class="av3-type-caption av3-text-var-muted">SOAP</div>
          <div class="av3-weight-medium"><?= !empty($cfg['soap_available']) ? 'Disponible' : 'No disponible' ?></div>
        </div>
      </div>

      <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3 av3-type-body">
        <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div>
              <div class="av3-type-caption av3-text-var-muted">Certificado (.crt)</div>
              <div class="av3-weight-medium"><?= !empty($cfg['cert_path_set']) ? 'Configurado' : 'No configurado' ?></div>
            </div>
            <div class="av3-type-caption <?= !empty($cfg['cert_exists']) ? 'av3-text-emerald-700' : 'av3-text-rose-700' ?>">
              <?= !empty($cfg['cert_exists']) ? 'OK' : 'Falta' ?>
            </div>
          </div>
        </div>
        <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div>
              <div class="av3-type-caption av3-text-var-muted">Clave (.key)</div>
              <div class="av3-weight-medium"><?= !empty($cfg['key_path_set']) ? 'Configurado' : 'No configurado' ?></div>
            </div>
            <div class="av3-type-caption <?= !empty($cfg['key_exists']) ? 'av3-text-emerald-700' : 'av3-text-rose-700' ?>">
              <?= !empty($cfg['key_exists']) ? 'OK' : 'Falta' ?>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($warnings)): ?>
        <div class="av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-3">
          <div class="av3-weight-strong av3-text-amber-900 av3-space-bottom-1">Avisos</div>
          <ul class="list-disc av3-pad-left-5 av3-type-body av3-text-amber-900 av3-flow-1">
            <?php foreach ($warnings as $w): ?>
              <li><?= e((string)$w) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </section>

    <section class="av3-card av3-flow-3">
      <h2 class="av3-type-base av3-weight-strong">Pruebas</h2>
      <div class="av3-flow-2">
        <?php foreach ($steps as $s): ?>
          <?php $ok = !empty($s['ok']); ?>
          <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
            <div class="av3-flex av3-items-center av3-justify-between av3-gap-2">
              <div class="av3-weight-medium"><?= e((string)($s['step'] ?? '')) ?></div>
              <div class="av3-type-caption av3-pad-x2 av3-pad-y1 av3-radius-full <?= $ok ?'av3-bg-emerald-100 av3-text-emerald-800' : 'av3-bg-rose-100 av3-text-rose-800' ?>">
                <?= $ok ? 'OK' : 'ERROR' ?>
              </div>
            </div>
            <?php if ($ok && !empty($s['info'])): ?>
              <pre class="av3-space-top-2 av3-type-caption av3-bg-slate-50 av3-border av3-border-slate-200 av3-radius-card av3-pad-3 av3-overflow-auto"><?= e(json_encode($s['info'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></pre>
            <?php elseif (!$ok && !empty($s['error'])): ?>
              <div class="av3-space-top-2 av3-type-body av3-text-rose-800"><?= e((string)$s['error']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="av3-type-caption av3-text-var-muted">Hora del servidor (UTC): <?= e((string)($result['server_time_utc'] ?? '')) ?></div>
    </section>

  </div>
</section>


