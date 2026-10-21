<?php
// Sub-nav de Ajustes (tabs)
$tab = (string)($_GET['tab'] ?? '');
$tab = trim($tab);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
$basePrefix = rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '', '/');
$norm = function (string $href) use ($basePrefix): string {
  $href = '/' . ltrim($href, '/');
  return ($basePrefix ? $basePrefix : '') . $href;
};

$designPath = $norm('/admin/settings/design/home-hero');
$designSectionsPath = $norm('/admin/settings/design/home-sections');
if ($tab === '' && str_starts_with($path, $designPath)) {
  $tab = 'design-home-hero';
}
if ($tab === '' && str_starts_with($path, $designSectionsPath)) {
  $tab = 'design-home-sections';
}
if ($tab === '') $tab = 'email-marketing';

$btn = function (string $label, string $qtab) use ($tab, $path, $norm): string {
  $href = '/admin/settings?tab=' . urlencode($qtab);
  $full = $norm('/admin/settings');
  $is = str_starts_with($path, $full) && ($tab === $qtab);
  $cls = $is ? 'btn-primary' : 'btn-secondary';
  $aria = $is ? ' aria-current="page"' : '';
  return '<a class="' . $cls . '" href="' . e(url($href)) . '"' . $aria . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};

$btnPath = function (string $label, string $href, string $key) use ($tab, $path, $norm): string {
  $full = $norm($href);
  $is = str_starts_with($path, $full) || $tab === $key;
  $cls = $is ? 'btn-primary' : 'btn-secondary';
  $aria = $is ? ' aria-current="page"' : '';
  return '<a class="' . $cls . '" href="' . e(url($href)) . '"' . $aria . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};
?>

<div class="av3-wrap">
  <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
    <?= $btn('Emails', 'email-marketing') ?>
    <?= $btn('General', 'general') ?>
    <?= $btnPath('Diseno', '/admin/settings/design/home-hero', 'design-home-hero') ?>
    <?= $btnPath('Secciones Home', '/admin/settings/design/home-sections', 'design-home-sections') ?>
    <?= $btn('Fiscal', 'fiscal') ?>
    <?= $btn('Envios', 'envios') ?>
    <?= $btn('Integraciones', 'integraciones') ?>
    <?= $btn('Pagos', 'pagos') ?>
    <?= $btn('Mayoristas', 'mayoristas') ?>
  </div>
</div>
