<?php
// Espera $p
$price = number_format((float)$p['price'], 0, ',', '.');
$list  = $p['list_price'] ? number_format((float)$p['list_price'], 0, ',', '.') : null;
$off   = ($list && $p['price'] < $p['list_price']) ? max(5, min(70, round(100 - ($p['price']/$p['list_price'])*100))) : null;
?>
<article class="js-card group relative rounded-2xl border border-gray-100 p-3 hover:shadow-md hover:-translate-y-[2px] transition bg-white">
  <a href="<?= e(url('/producto').'?slug='.rawurlencode($p['slug'])) ?>" class="block">
    <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-gray-50">
      <img src="<?= e($p['thumb']) ?>" alt="<?= e($p['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy"/>
      <?php if (!empty($p['is_new'])): ?><span class="absolute top-2 left-2 px-2 py-1 text-xs rounded-full bg-emerald-600 text-white">Nuevo</span><?php endif; ?>
      <?php if (!empty($p['is_sale'])): ?><span class="absolute top-2 right-2 px-2 py-1 text-xs rounded-full bg-rose-600 text-white">Oferta<?= $off?' -'.$off.'%':'' ?></span><?php endif; ?>
      <?php foreach(($p['badges'] ?? []) as $b): ?><span class="absolute bottom-2 left-2 px-2 py-1 text-xs rounded-full bg-gray-900/90 text-white"><?= e($b) ?></span><?php endforeach; ?>
    </div>
    <div class="mt-3 space-y-1">
      <h3 class="font-medium leading-tight line-clamp-2 group-hover:text-brand"><?= e($p['title']) ?></h3>
      <div class="flex items-center gap-2">
        <?php if ($list && $off): ?><span class="text-sm text-gray-400 line-through">$<?= $list ?></span><?php endif; ?>
        <span class="text-lg font-semibold">$<?= $price ?></span>
      </div>
      <div class="flex items-center gap-1 text-amber-500 text-sm">
        <?php $stars=(int)floor($p['rating']); for($i=1;$i<=5;$i++): ?><span><?= $i <= $stars ? '★' : '☆' ?></span><?php endfor; ?>
        <span class="text-gray-400">(<?= (int)$p['reviews'] ?>)</span>
      </div>
    </div>
  </a>
  <div class="mt-3 flex items-center gap-2">
    <button class="flex-1 px-3 py-2 rounded-xl bg-brand text-white hover:opacity-90">Agregar</button>
    <button class="px-3 py-2 rounded-xl border border-gray-200 hover:border-gray-300" title="Favorito">♡</button>
  </div>
</article>
