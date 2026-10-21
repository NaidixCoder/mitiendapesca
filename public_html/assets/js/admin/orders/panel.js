// admin/orders/panel.js — Panel de pedidos en dashboard
export function initPendingOrdersPanel(){
  const card = document.getElementById('pending-orders');
  if (!card) return;
  const statusEndpoint = card.getAttribute('data-status-endpoint') || null;

  card.addEventListener('click', (e) => {
    const tr = e.target.closest('tr.pending-orders-row, tr.row-link');
    if (!tr) return;
    if (e.target.closest('a,button,select,input,label')) return;
    const href = tr.getAttribute('data-href');
    if (href) window.location.href = href;
  });

  card.addEventListener('change', async (e) => {
    const sel = e.target.closest('.order-status-select');
    if (!sel || !statusEndpoint) return;
    const id = Number(sel.getAttribute('data-id') || 0);
    const next = String(sel.value || '');
    if (!id || !next) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
    const prev = sel.getAttribute('data-current') || sel.value;

    sel.disabled = true;
    try {
      const res = await fetch(new URL(statusEndpoint, document.baseURI), {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, token ? { 'X-CSRF-TOKEN': token } : {}),
        credentials: 'same-origin',
        body: JSON.stringify({ id, status: next })
      });
      const j = await res.json().catch(() => null);
      if (!res.ok || !j || !(j.ok === true || j.ok === 1)) throw new Error('bad');

      // UI
      sel.setAttribute('data-current', next);
      const tr = sel.closest('tr');
      const badge = tr?.querySelector('td:nth-child(4) .badge');
      if (badge) {
        badge.textContent = j.result?.next_label || next;
        if (j.result?.badge_class) badge.className = j.result.badge_class;
      }
    } catch (err) {
      alert('No se pudo actualizar el estado.');
      sel.value = prev;
    } finally {
      sel.disabled = false;
    }
  });
}
