<a href="<?= base_url('carrito') ?>" class="relative hover:text-accent transition" aria-label="Ver carrito"
    title="Carrito">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
        class="w-7 h-7" style="color: var(--fg);">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm9 0a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM7.5 14.25h9m-13.5-9h17.25l-1.5 6h-13.5l-2.25-6z" />
    </svg>
    <?php if ($cartQty > 0): ?>
    <span
        class="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 rounded-full bg-white text-gray-900 text-xs grid place-items-center font-semibold">
        <?= $cartQty > 99 ? '99+' : $cartQty ?>
    </span>
    <?php endif; ?>
</a>