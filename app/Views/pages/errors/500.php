<?php
http_response_code(500);

// SEO (no indexar páginas de error)
$pageTitle = '500 — Error del servidor | Mi Pesca & Aventura';
$pageDesc  = 'Ocurrió un problema procesando tu solicitud.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<section class="container mx-auto px-4 py-16 sm:py-20">
  <div class="mx-auto max-w-3xl text-center">
    <!-- Ilustración simple -->
    <div class="relative mx-auto mb-8 h-28 w-28">
      <span class="absolute inset-0 rounded-full bg-rose-500/10 blur-2xl"></span>
      <div class="relative flex h-28 w-28 items-center justify-center rounded-3xl border border-gray-100 bg-white shadow-sm">
        <!-- Icono 500 -->
        <svg width="48" height="48" viewBox="0 0 24 24" class="text-rose-600">
          <path fill="currentColor" d="M11 15h2v2h-2zm0-8h2v6h-2zM1 21h22L12 2" />
        </svg>
      </div>
    </div>

    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Ups… algo salió mal</h1>
    <p class="mt-3 text-gray-600">Tuvimos un problema procesando tu solicitud. Podés intentar de nuevo o volver al inicio.</p>

    <!-- Acciones -->
    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
      <button onclick="location.reload()" class="px-4 py-2 rounded-xl bg-brand text-white hover:opacity-90">Reintentar</button>
      <a href="<?= e(url('/')) ?>" class="px-4 py-2 rounded-xl border border-gray-200 hover:border-gray-300">Ir al inicio</a>
      <a href="<?= e(url('/contacto')) ?>" class="px-4 py-2 rounded-xl border border-gray-200 hover:border-gray-300">Contacto</a>
    </div>

    <!-- Tips -->
    <div class="mt-8 mx-auto max-w-xl text-left rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
      <h2 class="font-semibold mb-2">¿Qué podés intentar?</h2>
      <ul class="list-disc pl-5 text-gray-600 space-y-1">
        <li>Actualizar la página.</li>
        <li>Volver a intentar en unos minutos.</li>
        <li>Si persiste, escribinos desde <a href="<?= e(url('/contacto')) ?>" class="underline">Contacto</a>.</li>
      </ul>
    </div>
  </div>
</section>
