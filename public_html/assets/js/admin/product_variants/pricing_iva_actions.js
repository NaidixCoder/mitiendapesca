import { postForm } from './api.js';
import { showOverlay, hideOverlay } from './state.js';
import { normalizeMoney, formatMoney } from './money.js';
import { toastSafe } from './feedback.js';
import { syncSaveToolbar } from './save_actions.js';

export function bindPricingIvaActions(ctx) {
  const { root, on, productId, reload, setBusy, ivaSelect } = ctx;

  on(ivaSelect, 'change', () => {
    const ivaSaveBtn = root.querySelector('[data-role="vs-iva-save"]');
    if (!ivaSaveBtn) return;
    ivaSaveBtn.click();
  });

  const ivaSaveBtn = root.querySelector('[data-role="vs-iva-save"]');
  on(ivaSaveBtn, 'click', async (e) => {
    e.preventDefault();
    const endpointIvaUpdate = root.dataset.endpointIvaUpdate;
    if (!endpointIvaUpdate || !ivaSelect) return;

    let overlayTimer = null;
    try {
      setBusy(ivaSaveBtn, true);
      overlayTimer = window.setTimeout(() => showOverlay('Guardando IVA...', 'Recalculando precios'), 200);
      const resp = await postForm(endpointIvaUpdate, {
        id: String(productId),
        iva_rate: String(ivaSelect.value || ''),
      });
      if (!resp || resp.ok === false) {
        toastSafe('error', resp?.message || 'No se pudo guardar el IVA.');
      } else {
        toastSafe('ok', resp.message || 'IVA guardado y precios recalculados.');
        const mul = Number(resp.mul);
        const ivaFactor = Number(resp.new);
        const okUi = applyIvaUpdateToUi(root, mul, ivaFactor);
        if (!okUi) await reload();
      }
    } catch (_) {
      toastSafe('error', 'No se pudo guardar el IVA. Reintenta.');
    } finally {
      try { window.clearTimeout(overlayTimer); } catch (_) {}
      hideOverlay();
      setBusy(ivaSaveBtn, false);
    }
  });
}

function applyIvaUpdateToUi(rootEl, mul, ivaFactor) {
  try {
    if (!Number.isFinite(mul) || mul <= 0) return false;
    if (!Number.isFinite(ivaFactor) || ivaFactor < 0 || ivaFactor > 1) return false;

    const chip = rootEl.querySelector('[data-role="vs-iva-chip"]');
    if (chip) {
      const pct = (ivaFactor * 100);
      const txt = String(pct).replace(/\.0+$/, '').replace(/(\.\d)0$/, '$1');
      chip.textContent = `IVA del producto: ${txt}%`;
    }

    const rows = Array.from(rootEl.querySelectorAll('[data-role="vs-row"]'));
    for (const row of rows) {
      const priceInp = row.querySelector('input[data-field="price"]');
      const listInp = row.querySelector('input[data-field="list_price"]');
      const costInp = row.querySelector('input[data-field="cost"]');

      if (priceInp && String(priceInp.value || '').trim() !== '') {
        const cur = Number(normalizeMoney(priceInp.value));
        if (Number.isFinite(cur)) {
          const next = Math.round(cur * mul);
          priceInp.value = formatMoney(String(next));
          priceInp.dataset.orig = String(next);
        }
      }

      if (listInp && String(listInp.value || '').trim() !== '') {
        const cur = Number(normalizeMoney(listInp.value));
        if (Number.isFinite(cur)) {
          const next = Math.round(cur * mul);
          listInp.value = formatMoney(String(next));
          listInp.dataset.orig = String(next);
        }
      }

      const hint = row.querySelector('[data-role="vs-cost-hint"]');
      if (hint && costInp && String(costInp.value || '').trim() !== '') {
        const cost = Number(normalizeMoney(costInp.value));
        if (Number.isFinite(cost)) {
          const cf = cost * (1 + ivaFactor);
          const pct = (ivaFactor * 100);
          const pctTxt = String(pct).replace(/\.0+$/, '').replace(/(\.\d)0$/, '$1');
          hint.textContent = `IVA: ${pctTxt}% - Final: ${cf.toFixed(2)}`;
        }
      }
    }

    syncSaveToolbar(rootEl);
    return true;
  } catch (_) {
    return false;
  }
}
