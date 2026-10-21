<?php
$pageId = 'admin-email-campaign-form';
$c = is_array($campaign ?? null) ? $campaign : [];
$id = (int)($c['id'] ?? 0);
$templatesLite = is_array($templatesLite ?? null) ? $templatesLite : [];
$form = is_array($form ?? null) ? $form : [];
$ui = is_array($ui ?? null) ? $ui : [];
$links = is_array($links ?? null) ? $links : [];
$notice = is_array($notice ?? null) ? $notice : [];
$aud = (string)old('audience', (string)($form['audience'] ?? 'all'));
$audDays = (int)old('aud_days', (string)($form['aud_days'] ?? 60));
$audMinTotal = (int)old('aud_min_total', (string)($form['aud_min_total'] ?? 200000));
$st = (string)old('status', (string)($form['status'] ?? 'draft'));
$scheduledAtInput = (string)old('scheduled_at', (string)($form['scheduled_at_input'] ?? ''));
$showScheduled = ($st === 'scheduled');
$adminHeaderTitle = $id > 0 ? 'Editar envío masivo' : 'Nuevo envío masivo';
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
 ['label'=>'Envíos','href'=>url('/admin/marketing/email/campanas')],
 ['label'=>$adminHeaderTitle],
];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e((string)($links['back'] ?? url('/admin/marketing/email/campanas'))).'">Volver a envíos</a>';
?>
<section class="admin-surface">
<div class="av3-wrap av3-marketing-email av3-email-composer" data-email-composer>
  <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <div class="av3-email-composer__info">
    <p><strong>Guardar no envía correos.</strong> Para iniciar un envío, elegí Programada con fecha y hora, o usá «Programar ahora» desde el detalle.</p>
    <details><summary>Cómo funciona el seguimiento</summary><p>Los enlaces del botón permiten registrar clics y atribuir compras. Probá primero la vista previa y el envío de prueba.</p></details>
  </div>
  <?php if (!empty($notice['show'])): ?>
    <div class="av3-email-notice av3-email-notice--<?= e((string)($notice['type'] ?? 'info')) ?>" role="status">
      <strong><?= e((string)($notice['title'] ?? 'Revisá antes de guardar')) ?></strong>
      <?= e((string)($notice['message'] ?? '')) ?>
    </div>
  <?php endif; ?>
