<?php
// Las vistas del panel ya están en español; impedir sugerencias automáticas
// de traducción sobre el administrador sin afectar al sitio público.
$isAdminDocument = str_starts_with((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''), '/admin/')
    || str_starts_with((string)($pageId ?? ''), 'admin-');
?>
<!doctype html>
<html lang="es-AR"<?= $isAdminDocument ? ' translate="no" class="notranslate"' : '' ?>>
<head>
  <meta charset="utf-8">
  <?php if ($isAdminDocument): ?><meta name="google" content="notranslate"><?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
  // ---------- Variables de página (fallbacks/dinámicos) ----------
  $siteName = 'Mi Pesca & Aventura';

  // render_page incluye el head ANTES de la vista 404. Sus metadatos
  // deben decidirse acá, sin esperar al cuerpo ni heredar SEO del producto.
  $isNotFoundPage = ($name ?? '') === 'errors/404';
  if ($isNotFoundPage) {
    http_response_code(404);
    $seo = [
      'type' => 'website',
      'title' => '404 - Página no encontrada | Mi Pesca & Aventura',
      'description' => 'La página que buscás no existe o fue movida.',
      'robots' => 'noindex,nofollow',
    ];
    $extraHead = '';
  } else {
    $seo = $seo ?? [];
  }

  // Título / descripción con precedencia: $seo > $page* locales > defaults
  $pageTitle = $seo['title']        ?? ($pageTitle ?? $siteName);
  $pageDesc  = $seo['description']  ?? ($pageDesc  ?? 'Catálogo profesional de pesca deportiva. Envíos a todo el país.');
  $metaType  = $seo['type']          ?? ($metaType ?? 'website'); // 'website' | 'product'

  // Canonical: si el controller envía uno, respetarlo. Si no, fallback a $uri/base_url
  $defaultCanonical = (isset($uri) && $uri === '/')
                      ? base_url('/')
                      : base_url(ltrim($uri ?? '/', '/'));
  $canonical = $isNotFoundPage ? null : ($seo['canonical'] ?? ($canonical ?? $defaultCanonical));

  // Robots: por defecto index,follow. Para listados filtrados, el controller puede enviar noindex.
  $robots = $seo['robots'] ?? ($robots ?? 'index,follow');

  // Prev/Next opcionales
  $prevUrl = $seo['prev'] ?? ($prevUrl ?? null);
  $nextUrl = $seo['next'] ?? ($nextUrl ?? null);

  // JSON-LD opcional inyectable desde las páginas (breadcrumbs/product schema)
  $extraHead = $extraHead ?? '';

  // Social overrides (OpenGraph/Twitter)
  $ogTitle = $seo['og_title'] ?? $pageTitle;
  $ogDesc  = $seo['og_description'] ?? $pageDesc;
  $ogUrl   = $seo['og_url'] ?? $canonical;
  $ogImage = $seo['og_image'] ?? asset('img/branding/og-default.jpg');

  $twCard  = $seo['twitter_card'] ?? 'summary_large_image';
  $twTitle = $seo['twitter_title'] ?? $pageTitle;
  $twDesc  = $seo['twitter_description'] ?? $pageDesc;
  $twImage = $seo['twitter_image'] ?? $ogImage;
  ?>

  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <?php if ($canonical !== null && trim((string)$canonical) !== ''): ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
  <?php endif; ?>
  <meta name="robots" content="<?= e($robots) ?>">
  <meta name="theme-color" content="#016d6e">

  <!-- Open Graph -->
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:type" content="<?= $metaType === 'product' ? 'product' : 'website' ?>">
  <meta property="og:locale" content="es_AR">
  <meta property="og:title" content="<?= e($ogTitle) ?>">
  <meta property="og:description" content="<?= e($ogDesc) ?>">
  <?php if (!$isNotFoundPage && $ogUrl !== null): ?>
  <meta property="og:url" content="<?= e($ogUrl) ?>">
  <?php endif; ?>
  <meta property="og:image" content="<?= e($ogImage) ?>">

  <!-- Twitter -->
  <meta name="twitter:card" content="<?= e($twCard) ?>">
  <meta name="twitter:title" content="<?= e($twTitle) ?>">
  <meta name="twitter:description" content="<?= e($twDesc) ?>">
  <meta name="twitter:image" content="<?= e($twImage) ?>">

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset('img/favicon-16.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
  <link rel="manifest" href="<?= e(asset('site.webmanifest')) ?>">
  <link rel="mask-icon" href="<?= e(asset('img/safari-pinned-tab.svg')) ?>" color="#016d6e">

  <!-- CSS -->
  <?php
  $resolvedPageId = (string)($pageId ?? '');
  $isAdminPage = str_starts_with($resolvedPageId, 'admin-') || $resolvedPageId === 'admin';
  $isCatalogPage = in_array($resolvedPageId, ['catalog', 'catalogo', 'search', 'catalogo-search'], true);
  // Compatibilidad pública explícita: app.css dejó de cargarse.

  $assetVersion = null;
  if (defined('APP_ASSET_VERSION')) {
    $assetVersion = (string)APP_ASSET_VERSION;
  } else {
    $debugMode = false;
    try { $debugMode = filter_var(env_safe('APP_DEBUG', false), FILTER_VALIDATE_BOOL); } catch (\Throwable) {}
    if ($debugMode) {
      $assetVersion = (string)time();
    } else {
      $appJsFile = base_path('public_html/assets/js/app.js');
      $assetVersion = (string)(is_file($appJsFile) ? (int)filemtime($appJsFile) : time());
    }
  }
  $assetQuery = '?v=' . rawurlencode((string)$assetVersion);

  $adminStyles = [
    'css/shared/reset.css',
    'css/shared/tokens.css',
    'css/shared/base.css',
    'css/shared/components/ui.css',
    'css/shared/components/buttons.css',
    'css/shared/components/interaction.css',
    'css/admin-v3/components/admin-components.css',
    'css/shared/utilities.css',
    'css/admin-v3/layout.css',
    'css/admin-v3/tokens.css',
    'css/admin-v3/components/common.css',
    'css/admin-v3/components/design-system.css',
    'css/admin-v3/components/page-header.css',
    'css/admin-v3/components/primitives.css',
    'css/admin-v3/pages/dashboard.css',
    'css/admin-v3/pages/analytics.css',
    'css/admin-v3/pages/orders.css',
    'css/admin-v3/pages/seo.css',
    'css/admin-v3/pages/marketing-campaigns.css',
    'css/admin-v3/pages/marketing-email.css',
    'css/admin-v3/pages/products.css',
    'css/admin-v3/pages/catalog.css',
    'css/admin-v3/pages/product-editor.css',
    'css/admin-v3/pages/billing.css',
    'css/admin-v3/components/master-shell.css',
  ];

  // Base compartida: no incluir CSS de otras pantallas en cada respuesta.
  $publicBaseStyles = [
    'css/shared/reset.css',
    'css/shared/tokens.css',
    'css/public/fonts.css',
    'css/shared/base.css',
    'css/shared/components/ui.css',
    'css/shared/components/buttons.css',
    'css/shared/components/interaction.css',
    'css/public/components/icons.css',
    'css/public/components/toast.css',
    'css/public/components/whatsapp-link.css',
    'css/shared/utilities.css',
    'css/public/layouts/public-header.css',
    'css/public/layouts/public-footer.css',
    'css/public/layouts/public-mobile-menu.css',
  ];

  // La ruta de vista es fiable incluso cuando la propia vista asigna pageId
  // después de imprimir el head (p. ej. ayuda y páginas legales).
  $publicView = (string)($name ?? '');
  $publicPageStyles = [
    'public/home' => ['home-hero.css', 'home-sections.css', 'catalog-card.css'],
    'public/ayuda' => ['help.css'],
    'legal/' => ['legal.css'],
    'auth/' => ['auth.css'],
    'errors/' => ['errors.css'],
    'catalogo/' => ['catalog.css', 'catalog-facets.css', 'catalog-card.css'],
    'carrito/' => ['cart.css', 'catalog-card.css', 'related-products.css'],
    'checkout/' => ['checkout.css'],
    // account-orders-index.css contiene también el sidebar compartido de Cuenta.
    'cuenta/' => ['account.css', 'account-orders-index.css'],

  ];
  $publicStyles = $publicBaseStyles;
  foreach ($publicPageStyles as $viewPrefix => $files) {
    if ($publicView === $viewPrefix || str_starts_with($publicView, $viewPrefix)) {
      foreach ($files as $file) {
        $folder = $file === 'related-products.css' ? 'components' : 'pages';
        $publicStyles[] = 'css/public/' . $folder . '/' . $file;
      }
      break;
    }
  }
  // Dependencias específicas sobre la familia de vistas identificada.
  if (str_starts_with($publicView, 'catalogo/')) {
    if (($resolvedPageId === 'product') || str_contains($publicView, 'producto')) {
      $publicStyles[] = 'css/public/pages/product.css';
      $publicStyles[] = 'css/public/components/related-products.css';
    }
    if (str_contains($publicView, 'categorias') || str_contains($publicView, 'marcas')) {
      $publicStyles[] = 'css/public/pages/directory.css';
    }
  }
  if (str_starts_with($publicView, 'cuenta/')) {
    // Antes se importaba globalmente desde app.css; ahora solo en Mi Cuenta.
    $publicStyles[] = 'css/public/components/account.css';
    $accountStyles = [
      'direcciones/index' => ['account-addresses.css'],
      'direcciones/form' => ['account-addresses.css', 'account-address-edit.css'],
      'pedidos/index' => ['account-orders-index.css'],
      'pedidos/show' => ['account-order-show.css'],
      'perfil' => ['account-profile.css'],
      'seguridad' => ['account-security.css'],
      'complete' => ['account-complete.css'],
      'lealtad' => ['account-loyalty.css'],
      // Favoritos reutiliza product-grid, product-card y quick-add de Catálogo.
      'favoritos' => ['catalog.css', 'catalog-card.css', 'account-favorites.css'],
    ];
    foreach ($accountStyles as $suffix => $files) {
      if (str_starts_with(substr($publicView, strlen('cuenta/')), $suffix)) {
        foreach ($files as $file) $publicStyles[] = 'css/public/pages/' . $file;
        break;
      }
    }
  }
  // Evita enlaces duplicados si una dependencia se comparte.
  $publicStyles = array_values(array_unique($publicStyles));
  ?>

  <?php if ($isAdminPage): ?>
  <!-- Admin: flujo v2 -->
  <?php foreach ($adminStyles as $cssFile): ?>
  <link rel="stylesheet" href="<?= e(asset($cssFile) . $assetQuery) ?>">
  <?php endforeach; ?>
  <?php else: ?>
  <!-- Publico: estilos compartidos y específicos por página -->
  <?php foreach ($publicStyles as $cssFile): ?>
  <link rel="stylesheet" href="<?= e(asset($cssFile) . $assetQuery) ?>">
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- CSRF (se rellenará al implementar) -->
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

  <meta name="google-client-id" content="<?= e(env('GOOGLE_CLIENT_ID','')) ?>">
  <meta name="app-base-url" content="<?= rtrim(base_url(''), '/') . '/' ?>">
  <script>window.__ASSET_VERSION = <?= json_encode((string)$assetVersion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>

  <?php
  // JSON-LD global (sitio)
  $globalLd = [
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'Organization',
              '@id'   => rtrim(url('/'), '/') . '#org',
              'name'  => $siteName,
              'url'   => rtrim(url('/'), '/'),
              'logo'  => [
                  '@type' => 'ImageObject',
                  'url'   => asset('img/branding/miPescaYaventura-leyenda.png'),
              ],
          ],
          [
              '@type' => 'WebSite',
              '@id'   => rtrim(url('/'), '/') . '#website',
              'url'   => rtrim(url('/'), '/'),
              'name'  => $siteName,
              'publisher' => ['@id' => rtrim(url('/'), '/') . '#org'],
              'potentialAction' => [
                  '@type' => 'SearchAction',
                  'target' => url('/productos?q={search_term_string}'),
                  'query-input' => 'required name=search_term_string',
              ],
          ],
      ],
  ];
  ?>
  <script type="application/ld+json"><?= json_encode($globalLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>

<?php
// JSON-LD específico por página (producto/categoría/marca, etc.)
$seoJsonLd = $isNotFoundPage ? null : ($seo['jsonld'] ?? null);
if (is_string($seoJsonLd) && trim($seoJsonLd) !== '') {
  echo $seoJsonLd;
} elseif (is_array($seoJsonLd) && !empty($seoJsonLd)) {
  // fallback: array de nodos/graphs
  foreach ($seoJsonLd as $node) {
    if (!is_array($node)) {
      continue;
    }
    echo '<script type="application/ld+json">' .
         json_encode($node, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) .
         '</script>';
  }
}
?>

<?php if (!$isNotFoundPage) echo $extraHead; ?>

</head>
<body<?= $resolvedPageId !== '' ? ' data-page="' . e($resolvedPageId) . '"' : '' ?>>
