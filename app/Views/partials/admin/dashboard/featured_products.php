<?php /** @var array $rows */ ?>
<?php $rows = is_array($rows ?? null) ? array_slice($rows, 0, 10) : []; ?>

<div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
  <div class="flex items-center justify-between">
    <h2 class="text-slate-100 font-semibold">Productos destacados</h2>
    <a href="<?= url('/admin/products') ?>" class="text-sky-300 text-sm hover:underline">Ver todos</a>
  </div>

  <div class="mt-3 overflow-x-auto">
    <table class="min-w-full text-sm">
      <caption class="sr-only">Listado de productos destacados</caption>
      <thead class="text-slate-400">
        <tr>
          <th scope="col" class="text-left py-2">SKU</th>
          <th scope="col" class="text-left">Nombre</th>
          <th scope="col" class="text-right">Precio</th>
          <th scope="col" class="text-left">Estado</th>
          <th scope="col" class="text-left">Acciones</th>
        </tr>
      </thead>
      <tbody class="text-slate-200">
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $id     = isset($r['id']) ? (int)$r['id'] : 0;
              $active = !empty($r['is_active']);
              $badgeClass = $active ? 'bg-emerald-600/20 text-emerald-300' : 'bg-slate-600/20 text-slate-300';
              $badgeText  = $active ? 'activo' : 'inactivo';
              $price      = number_format((int)($r['price'] ?? 0), 0, ',', '.');
            ?>
            <tr class="border-t border-slate-800">
              <td class="py-2 whitespace-nowrap"><?= e($r['sku'] ?? '—') ?></td>
              <td class="max-w-[22rem] truncate"><?= e($r['name'] ?? '—') ?></td>
              <td class="text-right">$<?= $price ?></td>
              <td><span class="px-2 py-0.5 rounded <?= $badgeClass ?> text-xs"><?= e($badgeText) ?></span></td>
              <td>
                <?php if ($id): ?>
                  <a class="text-sky-300 hover:underline" href="<?= url('/admin/products/edit?id='.$id) ?>">Editar</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="py-8 text-slate-400 text-center">
              Sin productos aún. <a class="underline text-sky-300" href="<?= url('/admin/products/new') ?>">Crea el primero</a>.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
