<?php
/** Encabezado compartido. Variables locales: $adminHeaderTitle, $adminHeaderDescription, $adminHeaderBreadcrumbs, $adminHeaderActions. */
$adminHeaderTitle = (string)($adminHeaderTitle ?? '');
$adminHeaderDescription = (string)($adminHeaderDescription ?? '');
$adminHeaderBreadcrumbs = is_array($adminHeaderBreadcrumbs ?? null) ? $adminHeaderBreadcrumbs : [];
$adminHeaderActions = (string)($adminHeaderActions ?? '');
$adminHeaderClass = preg_replace('/[^a-zA-Z0-9 _-]/', '', (string)($adminHeaderClass ?? ''));
if (!empty($GLOBALS['_admin_master_capture'])) { ob_start(); }
?>
<header class="av3-page-header<?= $adminHeaderClass !== '' ? ' ' . e($adminHeaderClass) : '' ?>" aria-label="Encabezado de página">
  <div class="av3-page-header__main">
    <div class="av3-page-header__copy">
      <h1><?= e($adminHeaderTitle) ?></h1>
      <?php if ($adminHeaderBreadcrumbs): ?>
      <nav class="av3-page-header__breadcrumb" aria-label="Ruta de navegación">
        <ol>
          <?php foreach ($adminHeaderBreadcrumbs as $i => $crumb): ?>
          <li>
            <?php $label=(string)($crumb['label'] ?? ''); $href=(string)($crumb['href'] ?? ''); ?>
            <?php if ($href !== '' && $i < count($adminHeaderBreadcrumbs)-1): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span<?= $i === count($adminHeaderBreadcrumbs)-1 ? ' aria-current="page"' : '' ?>><?= e($label) ?></span><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </nav>
      <?php endif; ?>
    </div>
    <?php if ($adminHeaderActions !== ''): ?><div class="av3-page-header__actions"><?= $adminHeaderActions ?></div><?php endif; ?>
  </div>
</header>
<?php
if (!empty($GLOBALS['_admin_master_capture'])) {
    $GLOBALS['_admin_master_header'] = ob_get_clean();
}
unset($adminHeaderTitle,$adminHeaderDescription,$adminHeaderBreadcrumbs,$adminHeaderActions,$adminHeaderClass);
?>
