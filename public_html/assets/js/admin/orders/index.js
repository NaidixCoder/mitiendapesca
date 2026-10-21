// Pedidos: listado operativo. El detalle conserva todas las acciones de negocio.
export function initOrdersIndex() {
  const table = document.querySelector('[data-orders-index]');
  if (table) {
    table.addEventListener('click', (event) => {
      const row = event.target.closest('tr[data-href]');
      if (!row || event.target.closest('a,button,select,input,label')) return;
      const href = row.getAttribute('data-href');
      if (href) window.location.assign(href);
    });
  }
  const sort = document.querySelector('[data-orders-auto-submit]');
  sort?.addEventListener('change', () => {
    const form = sort.closest('form');
    if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
  });
}
