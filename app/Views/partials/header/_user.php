<div class="relative" x-auth-menu>
    <?php if (!$isAuth): ?>
    <div class="flex items-center gap-3">
        <a href="<?= base_url('login') ?>"
            class="px-3 py-1.5 rounded-[var(--radius-xs)] text-gray-900 text-sm hover:bg-white transition">Ingresar</a>
        <a href="<?= base_url('registro') ?>" class="text-white/90 hover:text-white text-sm underline">Crear cuenta</a>
    </div>
    <?php else: ?>
    <button type="button" class="flex items-center gap-2 group" data-auth-trigger aria-haspopup="menu"
        aria-expanded="false" aria-label="Abrir menú de usuario">
        <?php if (!empty($avatarUrl)): ?>
        <img src="<?= e($avatarUrl) ?>" alt="avatar" width="36" height="36" referrerpolicy="no-referrer"
            class="w-9 h-9 rounded-full object-cover" style="background: var(--surface-weak)" />
        <?php else: ?>
        <span class="w-9 h-9 rounded-full grid place-items-center text-sm font-medium"
            style="background: var(--surface-weak); border: 1px solid var(--border);">
            <?= e($initials) ?>
        </span>
        <?php endif; ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
            class="opacity-80 group-hover:opacity-100 transition">
            <path d="M7 10l5 5 5-5z" /></svg>
    </button>

    <div class="absolute right-0 mt-2 w-56 card overflow-hidden border border-white/10 shadow-xl backdrop-blur-sm"
        data-auth-menu role="menu" aria-label="Menú de usuario" hidden>
        <div class="px-4 py-3 border-b border-[color:var(--border)]/60">
            <p class="text-xs text-white/70">Sesión iniciada</p>
            <p class="text-sm font-medium truncate"><?= e($userName) ?></p>
        </div>
        <nav class="py-1">
            <a href="<?= base_url('cuenta') ?>" class="block px-4 py-2 text-sm hover:bg-white/5" role="menuitem">Mi
                cuenta</a>
            <a href="<?= base_url('pedidos') ?>" class="block px-4 py-2 text-sm hover:bg-white/5" role="menuitem">Mis
                pedidos</a>
            <a href="<?= base_url('direcciones') ?>" class="block px-4 py-2 text-sm hover:bg-white/5"
                role="menuitem">Direcciones</a>
            <?php if (function_exists('is_admin') && is_admin()): ?>
            <a href="<?= base_url('admin') ?>" class="block px-4 py-2 text-sm hover:bg-white/5"
                role="menuitem">Admin</a>
            <?php endif; ?>
            <form method="post" action="<?= base_url('logout') ?>">
                <?= csrf_field() ?>
                <button
                    class="w-full text-left px-4 py-2 text-sm hover:bg-[color:var(--danger)]/10 text-[color:var(--danger)]"
                    role="menuitem">Cerrar sesión</button>
            </form>
        </nav>
    </div>
    <?php endif; ?>
</div>