// Admin Imports - Preview filters (no HTML building)
export function init() {
  const btns = Array.from(document.querySelectorAll('.js-imports-filter'));
  const rows = Array.from(document.querySelectorAll('.js-imports-row'));
  const search = document.querySelector('.js-imports-search');

  if (!btns.length || !rows.length) return;

  let currentFilter = 'all';
  let currentQuery = '';

  function normalize(s) {
    return String(s || '').toLowerCase().trim();
  }

  function isOkLevel(level) {
    const l = normalize(level);
    return l !== 'error' && l !== 'warn';
  }

  function matchesFilter(row) {
    const level = normalize(row.dataset.level);
    const accion = normalize(row.dataset.accion);

    if (currentFilter === 'all') return true;
    if (currentFilter === 'error') return level === 'error';
    if (currentFilter === 'warn') return level === 'warn';
    if (currentFilter === 'ok') return isOkLevel(level);

    if (currentFilter === 'changes') {
      // Consideramos "con cambios" todo lo que no sea error/warn y no sea "sin cambios"
      if (!isOkLevel(level)) return false;
      return accion && accion.indexOf('sin cambios') === -1 && accion.indexOf('saltar') === -1;
    }

    return true;
  }

  function matchesSearch(row) {
    if (!currentQuery) return true;
    const sku = normalize(row.dataset.sku);
    return sku.includes(currentQuery);
  }

  function apply() {
    rows.forEach((r) => {
      const ok = matchesFilter(r) && matchesSearch(r);
      r.classList.toggle('av3-hidden', !ok);
    });

    // Estilo activo del filtro
    btns.forEach((b) => {
      const active = b.dataset.filter === currentFilter;
      b.classList.toggle('av3-bg-slate-900', active);
      b.classList.toggle('av3-text-white', active);
      b.classList.toggle('av3-border-slate-900', active);
      b.classList.toggle('av3-surface-white', !active);
    });
  }

  btns.forEach((b) => {
    b.addEventListener('click', () => {
      currentFilter = b.dataset.filter || 'all';
      apply();
    });
  });

  if (search) {
    search.addEventListener('input', () => {
      currentQuery = normalize(search.value);
      apply();
    });
  }

  // default: si hay errores, arrancamos mostrando solo errores para UX
  const hasErrors = rows.some(r => normalize(r.dataset.level) === 'error');
  if (hasErrors) currentFilter = 'error';

  apply();
}
