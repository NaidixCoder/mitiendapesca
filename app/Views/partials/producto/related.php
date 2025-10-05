<section class="mt-10">
  <h2 class="text-xl font-semibold mb-4">Relacionados</h2>
  <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
    <?php foreach ($related as $p): ?>
      <a href="<?= e(url('/producto').'?slug='.rawurlencode($p['slug'])) ?>" class="group block rounded-2xl border border-gray-100 p-3 hover:shadow-sm">
        <div class="aspect-square rounded-xl overflow-hidden bg-gray-50">
          <img src="<?= e($p['thumb']) ?>" alt="<?= e($p['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition"/>
        </div>
        <p class="mt-2 line-clamp-2 group-hover:text-brand"><?= e($p['title']) ?></p>
        <p class="font-medium">$<?= number_format((float)$p['price'],0,',','.') ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
