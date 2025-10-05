<section class="relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-12 md:py-16 grid md:grid-cols-2 items-center gap-10">
        <div class="order-2 md:order-1">
            <span
                class="inline-flex items-center gap-2 text-xs tracking-wider uppercase bg-brand/10 text-brand px-3 py-1 rounded-full border border-brand/20">
                <span class="h-2 w-2 rounded-full bg-brand-secondary"></span>
                Nueva temporada
            </span>
            <h1 class="mt-4 text-3xl md:text-5xl font-extrabold leading-tight">
                Equipá tu próxima <span class="text-brand">pesca</span> con productos probados
            </h1>
            <p class="mt-4 text-gray-600 max-w-xl">
                Cañas, reels, líneas y accesorios elegidos por pescadores. Calidad, garantía y asesoramiento real.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="<?= base_url('productos') ?>"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-full bg-brand text-white hover:opacity-90 transition shadow-md">Ver
                    catálogo</a>
                <a href="<?= base_url('contacto') ?>"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-full border border-brand text-brand hover:bg-brand/10 transition">Asesoramiento</a>
            </div>

            <ul class="mt-6 flex flex-wrap items-center gap-6 text-sm text-gray-500">
                <li class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    Envíos a todo el país
                </li>
                <li class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m-7.5 4.5h9A2.25 2.25 0 0 0 19.5 18V6A2.25 2.25 0 0 0 17.25 3.75h-9A2.25 2.25 0 0 0 6 6v12A2.25 2.25 0 0 0 8.25 20.5z" />
                        </svg>
                    12 cuotas fijas
                </li>
                <li class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75" /></svg>
                    Garantía oficial
                </li>
            </ul>
        </div>

        <div class="order-1 md:order-2">
            <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-xl ring-1 ring-brand/10 bg-gray-100">
                <img src="<?= asset('img/hero/hero-pesca-01.jpg') ?>" alt="Equipo de pesca en acción"
                    class="h-full w-full object-cover">
            </div>
        </div>
    </div>
</section>