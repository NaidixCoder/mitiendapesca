<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 md:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold">Experiencias reales</h2>
                <p class="text-gray-600">Qué dicen nuestros clientes</p>
            </div>
        </div>

        <div class="mt-8 grid md:grid-cols-3 gap-6">
            <?php foreach ([
        ['name'=>'Santiago R.','text'=>'Me asesoraron para armar mi primer equipo. Llegó rapidísimo y excelente calidad.'],
        ['name'=>'María P.',   'text'=>'El reel Shimano vino perfecto y el precio imbatible.'],
        ['name'=>'Julián V.',  'text'=>'Compré online y retiré en tienda, todo en 24hs. Súper recomendable.'],
        ] as $t): ?>
            <blockquote class="rounded-2xl bg-white border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-full bg-brand/15 flex items-center justify-center text-brand font-bold">
                        ★</div>
                    <div>
                        <span class="block font-semibold"><?= e($t['name']) ?></span>
                        <span class="text-xs text-gray-500">Compra verificada</span>
                    </div>
                </div>
                <p class="mt-4 text-gray-700"><?= e($t['text']) ?></p>
            </blockquote>
            <?php endforeach; ?>
        </div>
    </div>
</section>