<?php
$pageTitle = 'Automatizaciones';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$automations = is_array($automations_prepared ?? null) ? $automations_prepared : [];
$links = is_array($links ?? null) ? $links : [];
$adminHeaderTitle = 'Automatizaciones';
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
 ['label'=>'Automatizaciones'],
];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e((string)($links['settings'] ?? url('/admin/settings?tab=email-marketing'))).'">Ajustes de email</a>';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-marketing-email" data-email-automations>
    <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
    <?php if ($m=flash('ok')): ?><div class="av3-alert ok" role="status"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m=flash('error')): ?><div class="av3-alert error" role="alert"><?= e($m) ?></div><?php endif; ?>
    <div class="av3-email-page-intro">
      <p>Mensajes que se preparan automáticamente cuando ocurre una acción del cliente.</p>
      <details class="av3-email-help-inline">
        <summary>Cómo funciona</summary>
        <p>Podés activar o pausar cada serie y sus mensajes por separado. Los tiempos de espera se configuran en Ajustes de email. Si pausás una serie, sus pasos no se programan.</p>
      </details>
    </div>
    <?php if (!$automations): ?>
      <section class="av3-email-panel"><div class="av3-email-empty"><h2>Sin automatizaciones disponibles</h2><p>Revisá los ajustes del módulo antes de programar mensajes.</p></div></section>
    <?php else: ?>
      <div class="av3-email-automation-list">
      <?php foreach ($automations as $a): ?>
        <section class="av3-email-panel av3-email-automation" aria-labelledby="email-automation-<?= (int)$a['id'] ?>">
          <header class="av3-email-panel__header av3-email-automation__header">
            <div>
              <h2 id="email-automation-<?= (int)$a['id'] ?>"><?= e((string)$a['name']) ?></h2>
              <p><?= count($a['steps']) ?> <?= count($a['steps']) === 1 ? 'mensaje' : 'mensajes' ?> · <?= e((string)$a['code']) ?></p>
            </div>
            <div class="av3-email-automation__toggle">
              <span class="av3-email-status <?= !empty($a['enabled']) ? 'av3-email-status--ok' : 'av3-email-status--neutral' ?>"><?= !empty($a['enabled']) ? 'Activa' : 'Pausada' ?></span>
              <form method="post" action="<?= e((string)($links['toggle_automation'] ?? url('/admin/marketing/email/automatizaciones/toggle'))) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <input type="hidden" name="enabled" value="<?= !empty($a['enabled']) ? 0 : 1 ?>">
                <button class="av3-ui-btn" type="submit"><?= !empty($a['enabled']) ? 'Pausar serie' : 'Activar serie' ?></button>
              </form>
            </div>
          </header>
          <?php if (!$a['steps']): ?>
            <div class="av3-email-empty av3-email-empty--compact">Esta serie no tiene mensajes configurados.</div>
          <?php else: ?>
            <ol class="av3-email-steps">
              <?php foreach ($a['steps'] as $s): ?>
              <li class="av3-email-step">
                <div class="av3-email-step__main">
                  <span class="av3-email-status <?= !empty($s['enabled']) ? 'av3-email-status--ok' : 'av3-email-status--neutral' ?>"><?= !empty($s['enabled']) ? 'Activo' : 'Pausado' ?></span>
                  <div class="av3-email-step__name"><strong><?= e((string)$s['name']) ?></strong><span><?= e((string)$s['code']) ?></span></div>
                  <div class="av3-email-step__delay"><span>Después de</span><strong><?= e((string)$s['delay_label']) ?></strong></div>
                </div>
                <div class="av3-email-step__content">
                  <div><small>Asunto</small><span><?= e((string)$s['subject']) ?></span></div>
                  <div><small>Plantilla</small><span><?= e((string)$s['template']) ?></span></div>
                </div>
                <form class="av3-email-step__action" method="post" action="<?= e((string)($links['toggle_step'] ?? url('/admin/marketing/email/pasos/toggle'))) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <input type="hidden" name="enabled" value="<?= !empty($s['enabled']) ? 0 : 1 ?>">
                  <button class="av3-ui-btn" type="submit"><?= !empty($s['enabled']) ? 'Pausar' : 'Activar' ?></button>
                </form>
              </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
