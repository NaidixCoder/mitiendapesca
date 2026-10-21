<?php
$pageId = 'admin-email-templates';
$pageTitle = 'Plantillas';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$payload = is_array($templates_payload ?? null) ? $templates_payload : [];
$templates = is_array($payload['templates_prepared'] ?? null) ? $payload['templates_prepared'] : [];
$notice = is_array($payload['notice'] ?? null) ? $payload['notice'] : ['show'=>false];
$pagination = is_array($payload['pagination'] ?? null) ? $payload['pagination'] : [];
$range_label = (string)($payload['range_label'] ?? '');
$per = (int)($payload['per'] ?? 25);
$adminHeaderTitle = 'Plantillas de email';
$adminHeaderBreadcrumbs = [
  ['label'=>'Inicio','href'=>url('/admin')],
  ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
  ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
  ['label'=>'Plantillas'],
];
$adminHeaderActions = '<a class="av3-ui-btn av3-ui-btn--primary" href="'.e(url('/admin/marketing/email/campanas/new')).'">Nueva campaña</a>';
?>
<section class="admin-surface">
 <div class="av3-wrap av3-marketing-email" data-email-templates>
  <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <?php if (!empty($notice['show'])): ?><div class="av3-email-notice av3-email-notice--<?= e((string)($notice['type'] ?? 'info')) ?>" role="status"><?= e((string)($notice['message'] ?? '')) ?></div><?php endif; ?>
  <section class="av3-email-panel" aria-labelledby="email-templates-title">
    <header class="av3-email-panel__header">
      <div><h2 id="email-templates-title">Biblioteca de plantillas</h2><p><?= e((string)($range_label ?? '')) ?> · Guardadas desde el editor de campañas</p></div>
      <form class="av3-email-page-size" method="get" action="<?= url('/admin/marketing/email/templates') ?>">
        <label for="email-template-per">Por página</label><select id="email-template-per" name="per" >
          <?php foreach ([25,50] as $n): ?><option value="<?= $n ?>"<?= (int)($per ?? 25)===$n ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
        </select>
        <button class="av3-ui-btn" type="submit">Aplicar</button>
      </form>
    </header>
    <?php if (!$templates): ?>
      <div class="av3-email-empty"><h3>Tu biblioteca todavía está vacía</h3><p>Podés guardar diseños como plantilla desde una campaña de email y reutilizarlos más adelante.</p><a class="av3-ui-btn av3-ui-btn--primary" href="<?= url('/admin/marketing/email/campanas/new') ?>">Crear campaña</a></div>
    <?php else: ?>
      <div class="av3-email-table-wrap">
        <table class="av3-email-table">
          <thead><tr><th scope="col">Plantilla</th><th scope="col">Modo</th><th scope="col">Asunto sugerido</th><th scope="col">Creada</th><th scope="col" class="av3-email-right">Acciones</th></tr></thead>
          <tbody><?php foreach ($templates as $t): ?><tr>
            <td><a class="av3-email-record-name" href="<?= e((string)$t['show_url']) ?>"><?= e((string)$t['name']) ?></a></td>
            <td><span class="av3-email-status av3-email-status--neutral"><?= e((string)$t['mode_label']) ?></span></td>
            <td><?= !empty($t['subject_empty']) ? '—' : e((string)$t['subject']) ?></td>
            <td><?= e((string)$t['created_label']) ?></td>
            <td class="av3-email-right"><?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_template_actions.php'; ?></td>
          </tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <div class="av3-email-mobile-list">
        <?php foreach ($templates as $t): ?><article class="av3-email-mobile-item">
          <div class="av3-email-mobile-item__top"><a class="av3-email-record-name" href="<?= e((string)$t['show_url']) ?>"><?= e((string)$t['name']) ?></a><span class="av3-email-status av3-email-status--neutral"><?= e((string)$t['mode_label']) ?></span></div>
          <div class="av3-email-mobile-item__meta">Asunto: <?= !empty($t['subject_empty']) ? 'Sin definir' : e((string)$t['subject']) ?></div>
          <div class="av3-email-mobile-item__meta">Creada: <?= e((string)$t['created_label']) ?></div>
          <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_template_actions.php'; ?>
        </article><?php endforeach; ?>
      </div>
      <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_pagination.php'; ?>
    <?php endif; ?>
  </section>
  <p class="av3-email-page-note">Las plantillas almacenan HTML. Revisá su contenido y enlaces antes de programar cualquier envío.</p>
 </div>
</section>
