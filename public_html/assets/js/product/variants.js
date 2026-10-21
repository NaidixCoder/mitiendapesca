// Public product variants selector
export function init() {
  if (!(document.body && document.body.dataset.page === 'product')) return;

  const select = document.getElementById('variant_id');
  const dataEl = document.getElementById('pv-data');
  if (!select || !dataEl) return;

  let variants = [];
  try {
    variants = JSON.parse(dataEl.dataset.variants || '[]') || [];
  } catch {
    variants = [];
  }

  const byId = new Map();
  variants.forEach(v => {
    if (!v || typeof v !== 'object') return;
    byId.set(String(v.id), v);
  });

  const fmt = (n) => {
    const v = Number(n || 0);
    if (!v) return '';
    return '$' + Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  };
  const renderInstallment = (text) => {
    const raw = String(text || '');
    if (!raw) return '';
    const amountMatch = raw.match(/(\$\s?[0-9\.\,]+)/);
    if (!amountMatch) return raw;

    const amount = amountMatch[1];
    const escapedRaw = raw
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
    const escapedAmount = amount
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

    return escapedRaw.replace(
      escapedAmount,
      `<span class="product-buybox__installment-amount">${escapedAmount}</span>`
    );
  };

  const $price = document.getElementById('pp-price');
  const $transfer = document.getElementById('pp-transfer');
  const $badge = document.getElementById('pp-badge');
  const $stock = document.getElementById('pp-stock');
  const $installment = document.getElementById('pp-installment');
  const installmentsCount = Number(dataEl.dataset.installmentsCount || 0);
  const installmentsEnabled = String(dataEl.dataset.installmentsEnabled || '0') === '1';

  const applyVariant = (id) => {
    const v = byId.get(String(id));
    if (!v) return;

    if ($price) {
      const financed = Number(v.list_price || v.price || 0);
      $price.textContent = financed > 0 ? fmt(financed) : 'Precio a consultar';
    }

    if ($transfer) {
      const transfer = Number((v.display_price ?? v.price) || 0);
      if (transfer > 0) {
        $transfer.textContent = fmt(transfer);
      } else {
        $transfer.textContent = '';
      }
    }

    if ($badge) {
      const financed = Number(v.list_price || v.price || 0);
      const transfer = Number((v.display_price ?? v.price) || 0);
      if (financed > transfer && transfer > 0) {
        const pct = Math.round((1 - (transfer / financed)) * 100);
        $badge.textContent = `-${pct}% con transferencia`;
        $badge.classList.remove('ui-is-hidden');
      } else {
        $badge.textContent = '';
        $badge.classList.add('ui-is-hidden');
      }
    }

    if ($installment) {
      const financed = Number(v.list_price || v.price || 0);
      if (installmentsEnabled && financed > 0 && installmentsCount > 1) {
        $installment.innerHTML = renderInstallment(`${installmentsCount} cuotas sin interes de ${fmt(Math.round(financed / installmentsCount))}`);
        $installment.classList.remove('ui-is-hidden');
      } else {
        $installment.innerHTML = '';
        $installment.classList.add('ui-is-hidden');
      }
    }

    if ($stock) {
      const stockValue = Number(v.stock || 0);
      $stock.textContent = String(stockValue);
      window.dispatchEvent(new CustomEvent('product:stock-updated', {
        detail: { stock: stockValue }
      }));
    }

    if (typeof window.__pgUseGroup === 'function') {
      const wanted = (v.has_images || v.has_images === true) ? String(v.id) : 'general';
      window.__pgUseGroup(wanted);
    }
  };

  select.addEventListener('change', () => {
    const id = select.value;
    if (!id) return;
    applyVariant(id);
  });

  if (select.value) {
    applyVariant(select.value);
  }
}
