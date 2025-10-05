<?php
/** @var array $rows */
/** @var int $total */
/** @var int $pages */
/** @var int $page */
/** @var string $q */
$pageTitle = 'Usuarios — Admin';
$pageDesc  = 'Gestión mínima de usuarios';

function hdt($v){ if(!$v) return '—'; $t=strtotime($v); return $t?date('d/m/Y H:i',$t):'—'; }
?>
<section class="min-h-[70vh] py-8">
  <div class="max-w-7xl mx-auto px-4 md:px-8">
    <header class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold">Usuarios</h1>
        <p class="text-sm text-[color:var(--muted)]">Total: <?= (int)$total ?></p>
      </div>
      <form method="get" class="flex items-center gap-2">
        <input type="text" name="q" value="<?= e($q ?? '') ?>" placeholder="Buscar por nombre o email" class="input w-64" />
        <button class="btn-secondary">Buscar</button>
        <a href="<?= url('/admin/users') ?>" class="btn-secondary">Limpiar</a>
      </form>
    </header>

    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b border-[color:var(--border)]/60">
            <th class="px-4 py-3">ID</th>
            <th class="px-4 py-3">Nombre</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Rol</th>
            <th class="px-4 py-3">Verificado</th>
            <th class="px-4 py-3">Alta</th>
            <th class="px-4 py-3">Último acceso</th>
            <th class="px-4 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): foreach ($rows as $r): ?>
          <tr class="border-b border-[color:var(--border)]/30 hover:bg-white/5">
            <td class="px-4 py-3"><?= (int)($r['id'] ?? 0) ?></td>
            <td class="px-4 py-3 font-medium"><?= e($r['name'] ?? '—') ?></td>
            <td class="px-4 py-3"><?= e($r['email'] ?? '—') ?></td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                <?= ($r['role'] ?? 'customer')==='admin'
                    ? 'bg-indigo-500/15 text-indigo-200 border border-indigo-400/30'
                    : 'bg-white/10 text-white/90 border border-white/20' ?>">
                <?= e($r['role'] ?? 'customer') ?>
              </span>
            </td>
            <td class="px-4 py-3">
              <?php if ((int)($r['email_verified'] ?? 0) === 1): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-emerald-500/15 text-emerald-300 border border-emerald-400/30">Sí</span>
              <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-amber-500/15 text-amber-300 border border-amber-400/30">No</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3"><?= hdt($r['created_at'] ?? null) ?></td>
            <td class="px-4 py-3"><?= hdt($r['last_login_at'] ?? null) ?></td>
            <td class="px-4 py-3">
              <form method="post" action="<?= url('/admin/users/role') ?>" class="flex items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int)($r['id'] ?? 0) ?>">
                <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '/admin/users') ?>">
                <select name="role" class="input !py-1 !px-2">
                  <option value="customer" <?= ($r['role'] ?? '')==='customer'?'selected':''; ?>>customer</option>
                  <option value="admin"    <?= ($r['role'] ?? '')==='admin'   ?'selected':''; ?>>admin</option>
                </select>
                <button class="btn-secondary">Aplicar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="8" class="px-4 py-6 text-center opacity-75">Sin resultados</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (($pages ?? 1) > 1): ?>
      <nav class="mt-4 flex items-center justify-center gap-2 text-sm">
        <?php for ($p=1; $p<=($pages ?? 1); $p++):
          $u = url('/admin/users').'?'.http_build_query(array_filter(['q'=>$q,'page'=>$p]));
          $active = $p === ($page ?? 1);
        ?>
          <a href="<?= e($u) ?>" class="px-3 py-1.5 rounded-[var(--radius-xs)] border
             <?= $active ? 'bg-white/10 border-white/20' : 'border-white/10 hover:bg-white/5' ?>">
             <?= $p ?>
          </a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>

    <div class="mt-6">
      <a href="<?= url('/') ?>" class="underline text-sm opacity-90 hover:opacity-100">← Volver a la tienda</a>
    </div>
  </div>
</section>
