// Importaciones V3: filtros de filas solo presentacionales (sin modificar resultados).
export function init() {
  const root = document.querySelector('.av3-imports__board--preview');
  if (!root) return;
  const buttons = Array.from(root.querySelectorAll('.js-imports-filter'));
  const rows = Array.from(root.querySelectorAll('.js-imports-row'));
  const search = root.querySelector('.js-imports-search');
  const count = root.querySelector('[data-imports-count]');
  const empty = root.querySelector('[data-imports-empty]');
  if (!buttons.length || !rows.length) return;

  const norm = value => String(value || '').trim().toLocaleLowerCase('es');
  const levelOf = row => {
    const level = norm(row.dataset.level);
    return level === 'warn' ? 'warning' : level;
  };
  const isOk = row => !['error', 'warning'].includes(levelOf(row));
  let filter = rows.some(row => levelOf(row) === 'error') ? 'error' : 'all';
  let query = '';

  const matches = row => {
    const level = levelOf(row);
    const action = norm(row.dataset.accion);
    const matchesLevel = filter === 'all'
      || (filter === 'error' && level === 'error')
      || ((filter === 'warning' || filter === 'warn') && level === 'warning')
      || (filter === 'ok' && isOk(row))
      || (filter === 'changes' && isOk(row) && !!action
          && !action.includes('sin cambios') && !action.includes('saltar') && !action.includes('no cambiar'));
    const sku = norm(row.dataset.sku);
    const parent = norm(row.dataset.parentSku);
    return matchesLevel && (!query || sku.includes(query) || parent.includes(query));
  };

  const render = () => {
    let visible = 0;
    for (const row of rows) {
      const show = matches(row);
      row.hidden = !show;
      if (show) visible++;
    }
    for (const button of buttons) {
      button.setAttribute('aria-pressed', String(button.dataset.filter === filter));
    }
    if (count) count.textContent = visible === 1 ? '1 fila visible' : `${visible} filas visibles`;
    if (empty) empty.hidden = visible > 0;
  };

  buttons.forEach(button => button.addEventListener('click', () => {
    filter = button.dataset.filter || 'all';
    render();
  }));
  if (search) search.addEventListener('input', () => {
    query = norm(search.value);
    render();
  });
  render();
}
