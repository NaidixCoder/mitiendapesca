<?php
/** Layout maestro de administración. Recibe $adminMasterContent y el encabezado registrado. */
?>
<div class="av3-master-shell">
  <?php require BASE_PATH . '/app/Views/partials/admin/sidebar_v2.php'; ?>
  <button type="button" class="av3-mobile-backdrop" data-av3-mobile-backdrop aria-label="Cerrar menú" tabindex="-1" hidden></button>
  <div class="av3-master-workspace">
    <?php if (!empty($GLOBALS['_admin_master_header'])): ?>
      <div class="av3-master-header">
        <button type="button" id="av3-mobile-menu-toggle" class="av3-mobile-menu-toggle" aria-controls="av3-admin-navigation" aria-expanded="false" aria-label="Abrir menú de navegación">
          <span aria-hidden="true">☰</span>
        </button>
        <?= $GLOBALS['_admin_master_header'] ?>
      </div>
    <?php endif; ?>
    <div class="av3-master-content"><?= $adminMasterContent ?></div>
  </div>
</div>
