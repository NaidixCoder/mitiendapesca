<?php
$items = $items ?? [];
$title = $title ?? 'Nuevos ingresos';
$badge = $badge ?? 'Nuevo';
?>
<section class="py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 md:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold"><?= e($title) ?></h2>
                <p class="text-gray-600">Lo último en el shop</p>
            </div>
            <a href="<?= base_url('productos') ?>" class="text-brand hover:underline">Ver todos</a>
        </div>

        <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($items as $p): ?>
            <article
                class="group rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition">
                <a class="block" href="<?= base_url('producto') . '?slug=' . e($p['slug']) ?>">
                    <div class="aspect-square bg-gray-50 overflow-hidden">
                        <img class="h-full w-full object-cover group-hover:scale-105 transition"
                            src="<?= asset($p['img']) ?>" alt="<?= e($p['title']) ?>">
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold line-clamp-2"><?= e($p['title']) ?></h3>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-lg font-bold text-brand"><?= e($p['price']) ?></span>
                            <span
                                class="inline-flex items-center text-xs px-2 py-1 rounded-full bg-brand/10 text-brand"><?= e($badge) ?></span>
                        </div>
                    </div>
                </a>
                <div class="px-4 pb-4">
                    <a href="<?= base_url('producto') . '?slug=' . e($p['slug']) ?>"
                        class="w-full block text-center rounded-full border border-brand text-brand py-2 hover:bg-brand/10 transition">
                        Ver detalle
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>