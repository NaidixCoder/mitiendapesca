export function init() {
    // Form de búsqueda: recortar y evitar enviar si está vacío
    const searchInput = document.querySelector('form[action$="/productos"] input[name="q"]');
    const searchForm = searchInput ? searchInput.form : null;

    if (searchForm && searchInput) {
        // Evitá doble binding si el DOM se rehidrata
        if (!searchForm.hasAttribute('data-searchbar-bound')) {
            searchForm.setAttribute('data-searchbar-bound', '1');
            searchForm.addEventListener('submit', () => {
                searchInput.value = (searchInput.value || '').trim();
                if (searchInput.value === '') {
                    // si está vacío, quitamos el name para no enviar q=
                    searchInput.name = '';
                }
            });
        }
    }

    // Orden: fallback si no hay onchange inline
    const selOrden = document.querySelector('select[name="orden"]');
    if (selOrden && !selOrden.hasAttribute('data-autosubmit-bound')) {
        selOrden.setAttribute('data-autosubmit-bound', '1');
        selOrden.addEventListener('change', () => {
            const form = selOrden.form;
            if (form) form.submit();
        });
    }
}