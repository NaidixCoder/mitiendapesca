<?php
$pageTitle = 'Ajustes � Fiscal';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('settings.section', ['name' => 'Fiscal']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$fiscalPayload = is_array($fiscal_payload ?? null) ? $fiscal_payload : [];
$regime = (string)($fiscalPayload['regime'] ?? 'monotributo');
$regimeLabel = (string)($fiscalPayload['regime_label'] ?? $regime);
$regimeOptions = is_array($fiscalPayload['regime_options'] ?? null) ? $fiscalPayload['regime_options'] : [];
$statusOptions = is_array($fiscalPayload['status_options'] ?? null) ? $fiscalPayload['status_options'] : [];
$isNoFacturar = !empty($fiscalPayload['is_no_facturar']);
?>

<link rel="stylesheet" href="<?= e(url('/assets/css/admin-v3/pages/settings.css')) ?>">
<section class="admin-surface av3-settings-detail av3-settings-detail--fiscal">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/flashes.php'; ?>

    <nav class="av3-settings-detail__toc" aria-label="Ir a una sección">
      <a href="#ajustes-regimen">Régimen</a>
      <a href="#ajustes-arca">ARCA</a>
      <a href="#ajustes-conciliacion">Conciliación</a>
    </nav>
<form method="post" action="<?= e(url('/admin/settings?tab=fiscal')) ?>" class="av3-settings-detail__form av3-flow-4">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="fiscal">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/regime_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/snapshot_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/arca_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/reconciliation_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/fiscal/actions.php'; ?>

    </form>
  </div>
</section>
