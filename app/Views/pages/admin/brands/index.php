<?php /** @var array $rows */ $q = trim($q ?? ''); ?>
<section class="max-w-5xl mx-auto p-6 space-y-4">
  <header class="flex items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-semibold">Marcas</h1>
      <p class="text-sm" style="color:var(--muted)">Gestioná las marcas disponibles.</p>
    </div>
    <a class="btn-primary" href="<?= url('/admin/brands/new') ?>">Nueva marca</a>
  </header>

  <form class="flex items-center gap-2" method="get">
    <input class="input w-72" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o slug">
    <button class="btn-secondary">Buscar</button>
    <a class="btn-secondary" href="<?= url('/admin/brands') ?>">Limpiar</a>
  </form>

  <?php if ($m = flash('ok')): ?>
    <div class="card p-3" style="border-left:4px solid var(--success)"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash('error')): ?>
    <div class="card p-3" style="border-left:4px solid var(--danger)"><?= e($m) ?></div>
  <?php endif; ?>

  <div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left" style="color:var(--muted)">
          <th class="px-4 py-3">ID</th>
          <th class="px-4 py-3">Nombre</th>
          <th class="px-4 py-3">Slug</th>
          <th class="px-4 py-3">Creada</th>
          <th class="px-4 py-3">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
          <tr style="border-top:1px solid var(--border)">
            <td class="px-4 py-3"><?= (int)$r['id'] ?></td>
            <td class="px-4 py-3 font-medium"><?= e($r['name']) ?></td>
            <td class="px-4 py-3"><?= e($r['slug']) ?></td>
            <td class="px-4 py-3"><?= e($r['created_at'] ?? '') ?></td>
            <td class="px-4 py-3">
              <a class="btn-secondary" href="<?= url('/admin/brands/edit?id='.(int)$r['id']) ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="px-4 py-6" style="color:var(--muted)">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div><a class="underline text-sm" href="<?= url('/admin') ?>">← Volver al dashboard</a></div>
</section>
