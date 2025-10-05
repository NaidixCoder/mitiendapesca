<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 md:px-8">
        <div class="rounded-2xl border border-brand/20 bg-brand/5 p-6 md:p-8">
            <div class="md:flex items-center justify-between gap-6">
                <div class="max-w-xl">
                    <h3 class="text-xl md:text-2xl font-bold">Ofertas, lanzamientos y tips</h3>
                    <p class="text-gray-600">Recibí novedades antes que nadie (sin spam).</p>
                </div>
                <form class="mt-6 md:mt-0 flex w-full max-w-lg" method="post" action="<?= base_url('newsletter') ?>">
                    <?php if (!empty($csrfToken)): ?>
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <?php endif; ?>
                    <input type="email" name="email" required
                        class="flex-1 rounded-l-full px-4 py-3 text-sm border border-brand/30 focus:outline-none focus:ring-2 focus:ring-brand-secondary"
                        placeholder="tu@email.com">
                    <button
                        class="rounded-r-full px-5 py-3 bg-brand text-white hover:opacity-90 transition">Suscribirme</button>
                </form>
            </div>
        </div>
    </div>
</section>