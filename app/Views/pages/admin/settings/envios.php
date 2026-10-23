<?php
$pageTitle = 'Ajustes · Envíos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('settings.section', ['name' => 'Envios']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$cfg = $cfg ?? [];
$enabled = ((string)($cfg['correo_enabled'] ?? '0') === '1');
$mode = strtolower(trim((string)($cfg['correo_mode'] ?? 'test')));
if (!in_array($mode, ['test','prod'], true)) $mode = 'test';
$useEnv = ((string)($cfg['correo_use_env'] ?? '1') === '1');
$mockImport = ((string)($cfg['correo_mock_import'] ?? '0') === '1');
$customerId = trim((string)($cfg['correo_customer_id'] ?? ''));
?>

<link rel="stylesheet" href="<?= e(url('/assets/css/admin-v3/pages/settings.css')) ?>">
<section class="admin-surface av3-settings-detail av3-settings-detail--envios">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/flashes.php'; ?>

    <nav class="av3-settings-detail__toc" aria-label="Ir a una sección">
      <a href="#ajustes-correo">Correo Argentino</a>
      <a href="#ajustes-checkout">Checkout y retiro</a>
      <a href="#ajustes-remitente">Remitente</a>
    </nav>
<form method="post" action="<?= e(url('/admin/settings?tab=envios')) ?>" class="av3-settings-detail__form av3-flow-4">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="envios">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/correo_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/checkout_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/sender_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/actions.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/envios/next_steps.php'; ?>

    </form>

  </div>
</section>
