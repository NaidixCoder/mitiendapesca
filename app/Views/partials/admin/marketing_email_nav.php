<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$items = [
  ['Resumen','/admin/marketing/email'],
  ['Suscriptores','/admin/marketing/email/suscriptores'],
  ['Automatizaciones','/admin/marketing/email/automatizaciones'],
  ['Envíos','/admin/marketing/email/campanas'],
  ['Cola','/admin/marketing/email/cola'],
  ['Plantillas','/admin/marketing/email/templates'],
];
?>
<nav class="av3-email-nav" aria-label="Herramientas de email marketing">
<?php foreach ($items as $i => [$label,$route]):
  $full = parse_url(url($route), PHP_URL_PATH) ?: '';
  $active = $i === 0 ? rtrim($path,'/') === rtrim($full,'/') : str_starts_with($path,$full);
?>
  <a href="<?= e(url($route)) ?>" class="<?= $active ? 'is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
