<?php /** @var array $rows */ ?>
<section class="max-w-6xl mx-auto p-6 space-y-4">
  <header class="flex items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-semibold">Categorías</h1>
      <p class="text-sm" style="color:var(--muted)">Jerarquía, orden y visibilidad.</p>
    </div>
    <a class="btn-primary" href="<?= url('/admin/categories/new') ?>">Nueva categoría</a>
  </header>

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
          <th class="px-4 py-3">Padre</th>
          <th class="px-4 py-3">Orden</th>
          <th class="px-4 py-3">Activo</th>
          <th class="px-4 py-3">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
          <tr style="border-top:1px solid var(--border)">
            <td class="px-4 py-3"><?= (int)$r['id'] ?></td>
            <td class="px-4 py-3 font-medium"><?= e($r['name']) ?></td>
            <td class="px-4 py-3"><?= e($r['slug']) ?></td>
            <td class="px-4 py-3"><?= e($r['parent_name'] ?? '—') ?></td>
            <td class="px-4 py-3"><?= (int)($r['sort'] ?? 0) ?></td>
            <td class="px-4 py-3">
              <span class="badge-accent" style="background:<?= ((int)$r['is_active']===1?'color-mix(in oklab, var(--success) 18%, transparent)':'rgba(255,255,255,.06)') ?>;
                                               color:<?= ((int)$r['is_active']===1?'var(--success)':'var(--muted)') ?>;">
                <?= (int)$r['is_active']===1 ? 'Sí' : 'No' ?>
              </span>
            </td>
            <td class="px-4 py-3 flex items-center gap-2">
              <a class="btn-secondary" href="<?= url('/admin/categories/edit?id='.(int)$r['id']) ?>">Editar</a>
              <form method="post" action="<?= url('/admin/categories/toggle') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn-secondary"><?= (int)$r['is_active']===1 ? 'Desactivar' : 'Activar' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="px-4 py-6" style="color:var(--muted)">Sin categorías todavía.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div><a class="underline text-sm" href="<?= url('/admin') ?>">← Volver al dashboard</a></div>
</section>
