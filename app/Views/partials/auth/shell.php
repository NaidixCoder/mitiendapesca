<?php
// Espera: $title, $subtitle, y contenido en $content (string de HTML)
?>
<section class="min-h-[78vh] flex items-center justify-center py-10">
  <div class="w-full max-w-5xl mx-auto px-4">
    <div class="grid md:grid-cols-5 gap-6 items-stretch">
      <aside class="md:col-span-2 hidden md:flex">
        <div class="card p-8 w-full relative overflow-hidden">
          <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full opacity-20"
               style="background: radial-gradient(closest-side, var(--brand), transparent 70%);"></div>
          <header class="mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand/10 border border-brand/30 text-sm">
              Acceso seguro
            </span>
            <h2 class="text-2xl font-semibold mt-4 leading-tight"><?= e($title ?? '') ?></h2>
            <?php if (!empty($subtitle)): ?>
              <p class="text-sm text-[color:var(--muted)] mt-2"><?= e($subtitle) ?></p>
            <?php endif; ?>
          </header>
          <ul class="space-y-3 text-sm">
            <li class="flex items-start gap-3">
              <svg class="mt-1" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
              <span>Checkout más rápido y seguro.</span>
            </li>
            <li class="flex items-start gap-3">
              <svg class="mt-1" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
              <span>Historial y seguimiento de pedidos.</span>
            </li>
            <li class="flex items-start gap-3">
              <svg class="mt-1" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
              <span>Ofertas anticipadas para miembros.</span>
            </li>
          </ul>
        </div>
      </aside>

      <div class="md:col-span-3">
        <div class="card p-6 sm:p-8">
          <?= $content ?? '' ?>
        </div>
      </div>
    </div>
  </div>
</section>
