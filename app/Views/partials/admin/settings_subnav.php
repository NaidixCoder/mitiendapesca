<?php
$settingsTab = trim((string)($_GET['tab'] ?? 'index'));
$settingsPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/');
if (str_ends_with($settingsPath, '/design/home-hero')) $settingsTab = 'design-home-hero';
if (str_ends_with($settingsPath, '/design/home-sections')) $settingsTab = 'design-home-sections';
$settingsLinks = [
  ['Centro de ajustes', '/admin/settings', 'index'],
  ['General', '/admin/settings?tab=general', 'general'],
  ['Pagos', '/admin/settings?tab=pagos', 'pagos'],
  ['Mayoristas', '/admin/settings?tab=mayoristas', 'mayoristas'],
  ['Envíos', '/admin/settings?tab=envios', 'envios'],
  ['Fiscal y ARCA', '/admin/settings?tab=fiscal', 'fiscal'],
  ['Email', '/admin/settings?tab=email-marketing', 'email-marketing'],
  ['Hero', '/admin/settings/design/home-hero', 'design-home-hero'],
  ['Secciones Home', '/admin/settings/design/home-sections', 'design-home-sections'],
];
?>
<nav class="av3-wrap av3-flex av3-flex-wrap av3-items-center av3-gap-2" aria-label="Secciones de ajustes">
  <?php foreach ($settingsLinks as [$settingsLabel, $settingsHref, $settingsKey]): ?>
    <a class="<?= $settingsTab === $settingsKey ? 'av3-ui-btn av3-ui-btn--primary av3-ui-btn--small' : 'av3-ui-btn av3-ui-btn--small' ?>"
       href="<?= e(url($settingsHref)) ?>" <?= $settingsTab === $settingsKey ? 'aria-current="page"' : '' ?>><?= e($settingsLabel) ?></a>
  <?php endforeach; ?>
</nav>
