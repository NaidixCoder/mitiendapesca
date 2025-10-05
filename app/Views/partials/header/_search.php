<form class="relative w-full max-w-md" method="get" action="<?= base_url('productos') ?>" role="search"
    aria-label="Buscar productos">
    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="¿Qué estás buscando?"
        class="w-full rounded-full pl-4 pr-12 py-2 text-sm text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-white/70 focus:border-transparent bg-white" />
    <button type="submit"
        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-800 hover:text-gray-900 transition"
        aria-label="Buscar" title="Buscar">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
        </svg>
    </button>
</form>