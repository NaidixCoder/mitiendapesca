<?php
$pageTitle = 'Ajustes - Emails';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('settings.section', ['name' => 'Email marketing']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$payload = is_array($settings_email_marketing_payload ?? null) ? $settings_email_marketing_payload : [];
extract($payload, EXTR_SKIP);
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/flashes.php'; ?>

    <form method="post" action="<?= e(url('/admin/settings?tab=email-marketing')) ?>" class="av3-flow-4">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="email-marketing">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/subscriptions_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/limits_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/caps_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/web_runner_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/automation_delays_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/email_marketing/actions.php'; ?>

    </form>

  </div>
</section>
