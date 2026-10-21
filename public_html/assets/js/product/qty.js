// Public product qty widget
export function init() {
  if (!(document.body && document.body.dataset.page === 'product')) return;
  const form = document.getElementById('buy-form');
  if (!form) return;
  const input = form.querySelector('#qty');
  const stockEl = document.getElementById('pp-stock');
  if (!input) return;

  const getMin = () => Math.max(1, parseInt(input.min || '1', 10) || 1);
  const getStock = () => Math.max(0, parseInt(stockEl?.textContent || '0', 10) || 0);
  const getMax = () => {
    const stock = getStock();
    // Si no hay stock visible, dejamos max=1 para no romper el control.
    return stock > 0 ? stock : 1;
  };

  const syncMax = () => {
    input.max = String(getMax());
  };

  const clampValue = () => {
    const min = getMin();
    const max = getMax();
    let value = parseInt(input.value || String(min), 10);
    if (!Number.isFinite(value)) value = min;
    value = Math.max(min, Math.min(max, value));
    input.value = String(value);
  };

  syncMax();
  clampValue();

  form.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-qty-btn]');
    if (!btn) return;
    const delta = parseInt(btn.dataset.delta || '0', 10);
    const min = getMin();
    const max = getMax();
    const cur = parseInt(input.value || String(min), 10) || min;
    const next = Math.max(min, Math.min(max, cur + delta));
    input.value = String(next);
  });

  input.addEventListener('input', clampValue);
  input.addEventListener('change', clampValue);

  window.addEventListener('product:stock-updated', (event) => {
    const stock = Math.max(0, Number(event?.detail?.stock || 0));
    if (stockEl) stockEl.textContent = String(stock);
    input.value = '1';
    syncMax();
    clampValue();
  });
}
