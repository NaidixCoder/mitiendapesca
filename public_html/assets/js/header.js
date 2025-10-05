(function () {
    const root = document.querySelector('[x-auth-menu]');
    if (!root) return;
    const btn = root.querySelector('[data-auth-trigger]');
    const menu = root.querySelector('[data-auth-menu]');
    if (!btn || !menu) return;

    function open() {
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', onDocClick);
        document.addEventListener('keydown', onKey);
    }

    function close() {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        document.removeEventListener('click', onDocClick);
        document.removeEventListener('keydown', onKey);
    }

    function onDocClick(e) {
        if (!root.contains(e.target)) close();
    }

    function onKey(e) {
        if (e.key === 'Escape') close();
    }

    btn.addEventListener('click', () => (menu.hidden ? open() : close()));
    // Accesibilidad con Enter/Espacio
    btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            menu.hidden ? open() : close();
        }
    });
})();