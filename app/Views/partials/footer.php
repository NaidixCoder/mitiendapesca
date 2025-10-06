<?php
// Footer profesional — oscuro, limpio y modular
?>
<footer class="mt-16 border-t border-slate-800/60 bg-slate-900/40 text-slate-300">
  <!-- Grid principal -->
  <div class="mx-auto max-w-7xl px-6 py-12 grid gap-10 md:grid-cols-4">
    <!-- Brand + tagline -->
    <div>
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2">
        <!-- Logo simple (SVG placeholder) -->
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="11" class="stroke-emerald-500/70" stroke="currentColor" stroke-width="2"/>
          <path d="M16 9c-1.5 2.5-3.8 4.1-7 4-1.5 0-2.5 1.4-1.8 2.7C8 18 10.5 19 13 19c2.9 0 5.7-1.4 7-4" class="stroke-emerald-400/80" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        </svg>
        <span class="text-sm font-semibold tracking-wide text-slate-100">Mi Tienda de Pesca</span>
      </a>
      <p class="mt-3 text-sm text-slate-400 leading-6">
        Equipos confiables, atención real y envíos a todo el país. <br>
        Hacemos pesca con seriedad — y estilo.
      </p>

      <!-- Redes -->
      <div class="mt-4 flex items-center gap-3">
        <a href="#" class="group inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/40 hover:bg-slate-800" aria-label="Instagram">
          <svg width="18" height="18" viewBox="0 0 24 24" class="text-slate-400 group-hover:text-slate-200" fill="currentColor" aria-hidden="true">
            <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm5 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm6.5-.25a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z"/>
          </svg>
        </a>
        <a href="#" class="group inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/40 hover:bg-slate-800" aria-label="YouTube">
          <svg width="18" height="18" viewBox="0 0 24 24" class="text-slate-400 group-hover:text-slate-200" fill="currentColor" aria-hidden="true">
            <path d="M23.5 6.2a3.5 3.5 0 0 0-2.5-2.5C19 3.3 12 3.3 12 3.3s-7 0-9 .4A3.5 3.5 0 0 0 .5 6.2 36.3 36.3 0 0 0 0 12c0 1.9.2 3.8.5 5.8a3.5 3.5 0 0 0 2.5 2.5c2 .4 9 .4 9 .4s7 0 9-.4a3.5 3.5 0 0 0 2.5-2.5c.3-2 .5-3.9.5-5.8 0-1.9-.2-3.8-.5-5.8ZM9.7 15.3V8.7L15.8 12l-6.1 3.3Z"/>
          </svg>
        </a>
        <a href="#" class="group inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/40 hover:bg-slate-800" aria-label="WhatsApp">
          <svg width="18" height="18" viewBox="0 0 24 24" class="text-slate-400 group-hover:text-slate-200" fill="currentColor" aria-hidden="true">
            <path d="M20 3.9A10 10 0 0 0 3.5 17.7L2 22l4.4-1.5A10 10 0 1 0 20 3.9ZM12 20a8 8 0 1 1 4.6-14.6A8 8 0 0 1 12 20Zm4-6.4c-.2-.1-1.3-.7-1.4-.7s-.3-.1-.5.1-.5.7-.6.9-.2.2-.4.1a6.5 6.5 0 0 1-3-2.6c-.2-.3.2-.3.3-.9s-.1-.6-.2-.7l-.5-.6c-.1-.1-.3-.2-.5-.1a1.6 1.6 0 0 0-.6.3c-.2.2-.7.7-.7 1.6s.8 1.8.9 2 .9 1.4 2.1 2.1 2.4.9 2.7.9.9-.1 1.2-.6.6-1 .7-1.2c.1-.2 0-.3 0-.3Z"/>
          </svg>
        </a>
      </div>
    </div>

    <!-- Columnas de navegación -->
    <div class="grid grid-cols-2 gap-8 md:col-span-2 md:grid-cols-3">
      <div>
        <div class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Tienda</div>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a class="hover:text-slate-100" href="<?= url('/productos') ?>">Productos</a></li>
          <li><a class="hover:text-slate-100" href="#">Ofertas</a></li>
          <li><a class="hover:text-slate-100" href="#">Marcas</a></li>
          <li><a class="hover:text-slate-100" href="#">Novedades</a></li>
        </ul>
      </div>
      <div>
        <div class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Ayuda</div>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a class="hover:text-slate-100" href="#">Envíos</a></li>
          <li><a class="hover:text-slate-100" href="#">Devoluciones</a></li>
          <li><a class="hover:text-slate-100" href="#">Medios de pago</a></li>
          <li><a class="hover:text-slate-100" href="#">Contacto</a></li>
        </ul>
      </div>
      <div>
        <div class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Legal</div>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a class="hover:text-slate-100" href="#">Términos y condiciones</a></li>
          <li><a class="hover:text-slate-100" href="#">Política de privacidad</a></li>
          <li><a class="hover:text-slate-100" href="<?= url('/sitemap.xml') ?>">Sitemap</a></li>
          <li><a class="hover:text-slate-100" href="<?= url('/robots.txt') ?>">Robots</a></li>
        </ul>
      </div>
    </div>

    <!-- Newsletter / contacto -->
    <div>
      <div class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Newsletter</div>
      <form class="mt-3 flex gap-2" action="#" method="post" onsubmit="return false;">
        <?= csrf_field() ?>
        <input type="email" required placeholder="tu@email"
               class="min-w-0 flex-1 rounded-lg border border-slate-800 bg-slate-900/50 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        <button class="rounded-lg bg-emerald-600/90 px-3 py-2 text-sm text-white hover:bg-emerald-600">Suscribirme</button>
      </form>
      <p class="mt-2 text-xs text-slate-500">Prometemos cero spam. Solo lo bueno 🎣</p>
    </div>
  </div>

  <!-- Barra inferior -->
  <div class="border-t border-slate-800/60">
    <div class="mx-auto max-w-7xl px-6 py-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <p class="text-xs text-slate-500">
        &copy; <?= date('Y') ?> Mi Tienda de Pesca. Todos los derechos reservados.
      </p>
      <div class="flex items-center gap-3 text-[11px] text-slate-500">
        <span class="inline-flex items-center gap-1 rounded-md border border-slate-800 bg-slate-900/40 px-2 py-1">🇦🇷 ARS</span>
        <span class="inline-flex items-center gap-1 rounded-md border border-slate-800 bg-slate-900/40 px-2 py-1">SSL Activo</span>
        <span class="inline-flex items-center gap-1 rounded-md border border-slate-800 bg-slate-900/40 px-2 py-1">Pagos: Visa · MP · Deb</span>
        <button id="toTop" class="ml-1 inline-flex items-center gap-1 rounded-md border border-slate-800 bg-slate-900/40 px-2 py-1 hover:bg-slate-800">
          ↑ Arriba
        </button>
      </div>
    </div>
  </div>
</footer>

<!-- JS de la app (módulos) -->
<script type="module" src="<?= asset('js/app.js') ?>" defer></script>

<!-- Mini hook para “voler arriba” -->
<script>
  (function(){
    var btn = document.getElementById('toTop');
    if (!btn) return;
    btn.addEventListener('click', function(){ window.scrollTo({top:0, behavior:'smooth'}); });
  })();
</script>

</body>
</html>
