<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
$basePrefix = rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '', '/');
$tab = static function (string $href, string $label) use ($path, $basePrefix): string {
  $full = $basePrefix . '/' . ltrim($href, '/');
  $active = str_starts_with($path, $full);
  return '<a class="av3-marketing-nav__link'.($active ? ' is-active' : '').'" href="'.e(url($href)).'"'
      .($active ? ' aria-current="page"' : '').'>'.e($label).'</a>';
};
?>
<nav class="av3-marketing-nav" aria-label="Secciones de Marketing">
  <?= $tab('/admin/marketing/campanas', 'Campañas') ?>
  <?= $tab('/admin/marketing/email', 'Email marketing') ?>
</nav>
