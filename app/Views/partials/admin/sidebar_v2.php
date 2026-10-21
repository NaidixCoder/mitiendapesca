<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
$basePrefix = rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '', '/');

$norm = function (string $href) use ($basePrefix): string {
  $href = '/' . ltrim($href, '/');
  return ($basePrefix ? $basePrefix : '') . $href;
};

$icon = function (string $name): string {
  $icons = [
    'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z"/></svg>',
    'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10l1 2h3v2h-1l-1.2 8.4a2 2 0 0 1-2 1.6H9a2 2 0 0 1-2-1.6L5.8 8H4V6h2l1-2zm2.3 10h7.4l.9-6H8.4l.9 6zM10 20a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>',
    'billing' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2zm2 5v2h8V7H8zm0 4v2h8v-2H8z"/></svg>',
    'products' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7 12 3l9 4-9 4-9-4zm2 4.5 7 3.2v5.3L5 16.8v-5.3zm14 0v5.3l-7 3.2v-5.3l7-3.2z"/></svg>',
    'seo' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a6 6 0 1 0 3.9 10.6l4.2 4.2 1.4-1.4-4.2-4.2A6 6 0 0 0 10 4zm0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"/></svg>',
    'marketing' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10v4h3l4 4V6l-4 4H3zm12.5-1.5a4.5 4.5 0 0 1 0 7l1.2 1.6a6.5 6.5 0 0 0 0-10.2l-1.2 1.6z"/></svg>',
    'analytics' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2zm2-2h2V9H6v8zm5 0h2V5h-2v12zm5 0h2v-6h-2v6z"/></svg>',
    'brands' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 8 4v6c0 5.2-3.4 9.8-8 11-4.6-1.2-8-5.8-8-11V6l8-4zm-1 6v7l5-3.5L11 8z"/></svg>',
    'categories' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h7v6H4V5zm9 0h7v4h-7V5zM4 13h7v6H4v-6zm9-2h7v8h-7v-8z"/></svg>',
    'suppliers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4.4 0-8 2-8 4.5V21h16v-2.5C20 16 16.4 14 12 14z"/></svg>',
    'attributes' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h6v4H3v-4zm0-7h12v4H3v-4zm0-7h18v4H3V3z"/></svg>',
    'imports' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10.2l3.6-3.6L17 11l-5 5-5-5 1.4-1.4 3.6 3.6V3h2zM5 19h14v2H5v-2z"/></svg>',
    'audit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 6v6c0 5.1 3.4 9.8 8 11 4.6-1.2 8-5.9 8-11V6l-8-4zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>',
    'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zM8 12a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-2.8 0-5 1.3-5 3v2h8v-2c0-1.1.4-2.1 1.1-2.9A7.9 7.9 0 0 0 8 14zm8 0c-2.8 0-5 1.3-5 3v2h10v-2c0-1.7-2.2-3-5-3z"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m19.4 13.5.1-1.5-.1-1.5 2-1.6-2-3.4-2.4 1a7.7 7.7 0 0 0-2.6-1.5L14 2h-4l-.4 2.5A7.7 7.7 0 0 0 7 6L4.6 5l-2 3.4 2 1.6-.1 1.5.1 1.5-2 1.6 2 3.4 2.4-1a7.7 7.7 0 0 0 2.6 1.5L10 22h4l.4-2.5A7.7 7.7 0 0 0 17 18l2.4 1 2-3.4-2-1.6zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>',
    'web' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm6.9 8h-3a13.7 13.7 0 0 0-1.3-5A7 7 0 0 1 18.9 11zM12 5c.9 1 1.8 3 2 6h-4c.2-3 1.1-5 2-6zM9.4 6a13.7 13.7 0 0 0-1.3 5h-3A7 7 0 0 1 9.4 6zM5.1 13h3a13.7 13.7 0 0 0 1.3 5A7 7 0 0 1 5.1 13zm6.9 6c-.9-1-1.8-3-2-6h4c-.2 3-1.1 5-2 6zm2.6-1a13.7 13.7 0 0 0 1.3-5h3a7 7 0 0 1-4.3 5z"/></svg>',
    'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5v16h5v2H3V2h7v2zm7.6 7L15 8.4 16.4 7l5 5-5 5-1.4-1.4 2.6-2.6H9v-2h8.6z"/></svg>',
  ];

  return $icons[$name] ?? $icons['settings'];
};

$item = function (string $href, string $label, string $iconName, bool $exact = false) use ($path, $norm, $icon): string {
  $full = $norm($href);
  $is = $exact ? ($path === $full) : str_starts_with($path, $full);
  $cls = $is ? 'av3-sidebar-link is-active' : 'av3-sidebar-link';
  $aria = $is ? ' aria-current="page"' : '';
  $title = ' title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';
  return '<a class="' . $cls . '" href="' . url($href) . '"' . $aria . $title . '><span class="av3-sidebar-icon">' . $icon($iconName) . '</span><span class="av3-sidebar-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
};

$group = function (string $title, array $items, bool $first = false) use ($item): void {
  $groupClass = $first ? 'av3-group is-first' : 'av3-group';
  echo '<section class="' . $groupClass . '">';
  echo '<h3 class="av3-group-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
  echo '<div class="av3-group-items">';
  foreach ($items as $row) {
    echo $item($row['href'], $row['label'], $row['icon'], $row['exact'] ?? false);
  }
  echo '</div>';
  echo '</section>';
};
?>
<aside id="av3-admin-navigation" class="av3-sidebar" data-av3-sidebar aria-label="Navegación administrativa">
  <div class="av3-brand-row">
    <div class="av3-brand">MI PESCA</div>
    <button type="button" id="av3-toggle" class="av3-toggle" aria-label="Colapsar barra lateral">&#9776;</button>
  </div>

  <nav class="av3-nav" aria-label="Navegacion Admin">
    <?php $group('Operacion', [
      ['href' => '/admin', 'label' => 'Dashboard', 'icon' => 'dashboard', 'exact' => true],
      ['href' => '/admin/orders', 'label' => 'Pedidos', 'icon' => 'orders'],
      ['href' => '/admin/facturacion/control', 'label' => 'Facturacion', 'icon' => 'billing'],
      ['href' => '/admin/seo/auditoria', 'label' => 'SEO', 'icon' => 'seo'],
      ['href' => '/admin/marketing/campanas', 'label' => 'Marketing', 'icon' => 'marketing'],
      ['href' => '/admin/analytics', 'label' => 'Analiticas', 'icon' => 'analytics'],
    ], true); ?>

    <?php $group('Catalogo', [
      ['href' => '/admin/products', 'label' => 'Productos', 'icon' => 'products'],
      ['href' => '/admin/brands', 'label' => 'Marcas', 'icon' => 'brands'],
      ['href' => '/admin/categories', 'label' => 'Categorias', 'icon' => 'categories'],
      ['href' => '/admin/proveedores', 'label' => 'Proveedores', 'icon' => 'suppliers'],
      ['href' => '/admin/attributes', 'label' => 'Atributos', 'icon' => 'attributes'],
      ['href' => '/admin/catalog-tags', 'label' => 'Tags', 'icon' => 'attributes'],
      ['href' => '/admin/imports', 'label' => 'Importaciones', 'icon' => 'imports'],
    ]); ?>

    <?php $group('Sistema', [
      ['href' => '/admin/audit', 'label' => 'Auditoria', 'icon' => 'audit'],
      ['href' => '/admin/users', 'label' => 'Usuarios', 'icon' => 'users'],
      ['href' => '/admin/settings', 'label' => 'Ajustes', 'icon' => 'settings'],
    ]); ?>
  </nav>

  <div class="av3-nav-bottom" aria-label="Acciones generales">
    <a class="av3-sidebar-link" href="<?= url('/') ?>" title="Web">
      <span class="av3-sidebar-icon"><?= $icon('web') ?></span>
      <span class="av3-sidebar-label">Web</span>
    </a>

    <form method="post" action="<?= url('/logout') ?>" class="av3-logout-form">
      <?= csrf_field() ?>
      <button type="submit" class="av3-sidebar-link av3-link-danger" title="Cerrar sesion">
        <span class="av3-sidebar-icon"><?= $icon('logout') ?></span>
        <span class="av3-sidebar-label">Cerrar sesion</span>
      </button>
    </form>
  </div>
</aside>
