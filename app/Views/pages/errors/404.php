<?php
http_response_code(404);

// SEO (no indexar páginas de error)
$pageTitle = '404 — Página no encontrada | Mi Pesca & Aventura';
$pageDesc  = 'La página que buscás no existe o fue movida.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';

$popular = [
  ['label'=>'Reels','url'=>'/productos?categoria=reel'],
  ['label'=>'Cañas','url'=>'/productos?categoria=cana'],
  ['label'=>'Señuelos','url'=>'/productos?categoria=senuelo'],
  ['label'=>'Camping','url'=>'/productos?categoria=camping'],
];
?>

<section class="container mx-auto px-4 py-16 sm:py-20">
  <div class="mx-auto max-w-3xl text-center">
    <!-- Ilustración simple -->
    <div class="relative mx-auto mb-8 h-28 w-28">
      <span class="absolute inset-0 rounded-full bg-brand/10 blur-2xl"></span>
      <div class="relative flex h-28 w-28 items-center justify-center rounded-3xl border border-gray-100 bg-white shadow-sm">
        <!-- Icono 404 -->
        <svg width="48" height="48" viewBox="0 0 24 24" class="opacity-90">
          <path fill="currentColor" d="M12 2a9 9 0 1 0 9 9A9.01 9.01 0 0 0 12 2m3.5 7a1.5 1.5 0 1 1 1.5 1.5A1.5 1.5 0 0 1 15.5 9M7 9.75A1.25 1.25 0 1 1 8.25 11A1.25 1.25 0 0 1 7 9.75M16 16H8a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2" />
        </svg>
      </div>
    </div>

    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Página no encontrada</h1>
    <p class="mt-3 text-gray-600">El enlace puede estar roto o la página fue movida. Probá buscar lo que necesitás o explorá el catálogo.</p>

    <!-- Buscador -->
    <form method="get" action="<?= e(url('/productos')) ?>" class="mt-6 flex items-stretch gap-3 max-w-xl mx-auto">
      <input type="search" name="q" placeholder="Buscar cañas, reels, señuelos…"
             class="flex-1 rounded-xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand/30">
      <button class="rounded-xl px-5 py-3 bg-brand text-white hover:opacity-90">Buscar</button>
    </form>

    <!-- Acciones -->
    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
      <a href="<?= e(url('/')) ?>" class="px-4 py-2 rounded-xl border border-gray-200 hover:border-gray-300">Volver al inicio</a>
      <a href="<?= e(url('/productos')) ?>" class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:opacity-90">Ver catálogo</a>
    </div>

    <!-- Categorías populares -->
    <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
      <?php foreach ($popular as $p): ?>
        <a href="<?= e(url(ltrim($p['url'],'/'))) ?>" class="px-3 py-1.5 rounded-full border border-gray-200 hover:border-gray-300 text-sm">
          <?= e($p['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
