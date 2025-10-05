<?php /** @var array $rows */ ?>
<?php $rows = is_array($rows ?? null) ? array_slice($rows, 0, 10) : []; ?>

<div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
  <div class="flex items-center justify-between">
    <h2 class="text-slate-100 font-semibold">Stock bajo</h2>
    <a href="<?= url('/admin/products') ?>" class="text-sky-300 text-sm hover:underline">Ver todos</a>
  </div>

  <div class="mt-3 overflow-x-auto">
    <table class="min-w-full text-sm">
      <caption class="sr-only">Productos con nivel de stock por debajo del umbral</caption>
      <thead class="text-slate-400">
        <tr>
          <th scope="col" class="text-left py-2">SKU</th>
          <th scope="col" class="text-left">Producto</th>
          <th scope="col" class="text-right">Stock</th>
          <th scope="col" class="text-right">Umbral</th>
          <th scope="col" class="text-left">Acciones</th>
        </tr>
      </thead>
      <tbody class="text-slate-200">
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $id    = isset($r['id']) ? (int)$r['id'] : 0;
              $sku   = $r['sku']  ?? '—';
              $name  = $r['name'] ?? '—';
              $stock = (int)($r['stock'] ?? 0);
              $thr   = (int)($r['low_stock_threshold'] ?? 0);
              $def   = max(0, $thr - $stock); // faltante respecto del umbral
              // Colorear: 0 = crítico (rojo), <umbral = alerta (ámbar)
              $stockClass = $stock <= 0 ? 'text-red-300 font-semibold' : 'text-amber-300 font-semibold';
            ?>
            <tr class="border-t border-slate-800">
              <td class="py-2 whitespace-nowrap"><?= e($sku) ?></td>
              <td class="max-w-[42ch] truncate"><?= e($name) ?></td>
              <td class="text-right <?= $stockClass ?>"><?= $stock ?></td>
              <td class="text-right"><?= $thr ?></td>
              <td>
                <?php if ($id): ?>
                  <a class="text-sky-300 hover:underline" href="<?= url('/admin/products/edit?id='.$id) ?>">Ajustar</a>
                  <?php if ($def > 0): ?>
                    <span class="ml-2 align-middle text-xs px-1.5 py-0.5 rounded bg-amber-600/20 text-amber-300">−<?= $def ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="py-8 text-slate-400 text-center">
              Sin alertas por ahora. <span class="block text-slate-500 text-xs mt-1">Todo el inventario está por encima del umbral.</span>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
