<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php
  // ---------- Variables de página (fallbacks) ----------
  $siteName  = 'Mi Pesca & Aventura';
  $pageTitle = $pageTitle ?? $siteName;
  $pageDesc  = $pageDesc  ?? 'Catálogo profesional de pesca deportiva. Envíos a todo el país.';
  $metaType  = $metaType  ?? 'website';         // 'website' | 'product' (para detalle)
  $canonical = (isset($uri) && $uri === '/')
                ? base_url('/')
                : base_url(ltrim($uri ?? '/', '/'));
  // JSON-LD opcional inyectable desde las páginas (breadcrumbs/product schema)
  $extraHead = $extraHead ?? '';
  ?>

  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#016d6e">

  <!-- Open Graph -->
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:type" content="<?= $metaType === 'product' ? 'product' : 'website' ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDesc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(asset('img/branding/og-default.jpg')) ?>">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDesc) ?>">
  <meta name="twitter:image" content="<?= e(asset('img/branding/og-default.jpg')) ?>">

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset('img/favicon-16.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
  <link rel="manifest" href="<?= e(asset('site.webmanifest')) ?>">
  <link rel="mask-icon" href="<?= e(asset('img/safari-pinned-tab.svg')) ?>" color="#016d6e">

  <!-- CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/brand.css')) ?>">

  <!-- CSRF (se rellenará al implementar) -->
  <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">

  <?= $extraHead /* JSON-LD opcional */ ?>
</head>
<body class="bg-gray-50 text-gray-900" <?= !empty($pageId) ? 'data-page="'.e($pageId).'"' : '' ?>>
