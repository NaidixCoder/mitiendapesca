<?php /** @var array $rows, $q, $page, $pages, $total */ ?>
<section class="max-w-7xl mx-auto p-6 space-y-4">
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-slate-100">Productos</h1>
      <p class="text-slate-400 text-sm">Total: <?= (int)($total ?? 0) ?></p>
    </div>
    <div class="flex items-center gap-2">
      <form method="get" class="flex items-center gap-2">
        <input class="input w-64" type="text" name="q" value="<?= e($q ?? '') ?>" placeholder="Buscar por nombre o SKU">
        <button class="btn-secondary">Buscar</button>
        <a class="btn-secondary" href="<?= url('/admin/products') ?>">Limpiar</a>
      </form>
      <a class="btn-primary" href="<?= url('/admin/products/new') ?>">Nuevo</a>
    </div>
  </header>

  <div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="text-slate-400">
        <tr>
          <th class="text-left py-2 px-3">SKU</th>
          <th class="text-left px-3">Nombre</th>
          <th class="text-right px-3">Precio</th>
          <th class="text-left px-3">Estado</th>
          <th class="text-left px-3">Acciones</th>
        </tr>
      </thead>
      <tbody class="text-slate-200">
        <?php if (!empty($rows)): foreach ($rows as $r): $id=(int)$r['id']; ?>
        <tr class="border-t border-slate-800">
          <td class="py-2 px-3 whitespace-nowrap"><?= e($r['sku']) ?></td>
          <td class="px-3"><?= e($r['name']) ?></td>
          <td class="px-3 text-right">$<?= number_format((int)$r['price'],0,',','.') ?></td>
          <td class="px-3">
            <?php $active = !empty($r['is_active']); ?>
            <span class="px-2 py-0.5 rounded text-xs <?= $active?'bg-emerald-600/20 text-emerald-300':'bg-slate-600/20 text-slate-300' ?>">
              <?= $active?'activo':'inactivo' ?>
            </span>
          </td>
          <td class="px-3">
            <a class="text-sky-300 hover:underline" href="<?= url('/admin/products/edit?id='.$id) ?>">Editar</a>
          </td>
        </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="5" class="py-8 text-center text-slate-400">Sin resultados</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (($pages ?? 1) > 1): ?>
  <nav class="mt-4 flex items-center justify-center gap-2 text-sm">
    <?php for ($p=1;$p<=($pages ?? 1);$p++):
      $u = url('/admin/products').'?'.http_build_query(array_filter(['q'=>$q,'page'=>$p]));
      $active = $p===($page ?? 1);
    ?>
      <a href="<?= e($u) ?>" class="px-3 py-1.5 rounded border <?= $active?'bg-white/10 border-white/20':'border-white/10 hover:bg-white/5' ?>">
        <?= $p ?>
      </a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</section>
