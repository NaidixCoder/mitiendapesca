import { postForm, setConfigCollapsed } from './api.js';
import { createFeedback } from './feedback.js';
import { computeCombinationCount, getMetaFlag, getMissingSelectionNames } from './selection.js';
import { initAttrQuickCreate } from './attr_quick.js';
import { bindLifecycleActions } from './lifecycle_actions.js';
import { saveDirtyRows, isRowDirty, syncSaveToolbar } from './save_actions.js';
import { bindPricingActions } from './pricing_actions.js';
import { bindAttributeActions } from './attribute_actions.js';
import { toInt } from './validators.js';
import { normalizeErrMessage } from './errors.js';

export function bindSaleUI(root, productId, reloadFragment = null) {
  const fb = createFeedback(root);
  const setVariantsBusy = (busy) => {
    try {
      const curr = Number.parseInt(String(window.__TP_VARIANTS_BUSY_COUNT || 0), 10) || 0;
      const next = busy ? (curr + 1) : Math.max(0, curr - 1);
      window.__TP_VARIANTS_BUSY_COUNT = next;
      if (document.body) {
        document.body.dataset.tpVariantsBusy = next > 0 ? '1' : '0';
      }
      root.dataset.vsBusy = next > 0 ? '1' : '0';
    } catch (_) {}
  };
  if (!root._vsActionQueue) root._vsActionQueue = Promise.resolve();
  const enqueue = (task) => {
    if (typeof task !== 'function') return Promise.resolve();
    const wrapped = async () => {
      setVariantsBusy(true);
      try { return await task(); }
      finally { setVariantsBusy(false); }
    };
    const run = root._vsActionQueue.then(wrapped, wrapped);
    root._vsActionQueue = run.catch(() => {});
    return run;
  };
  const reload = async () => {
    if (typeof reloadFragment === 'function') {
      setVariantsBusy(true);
      try { await reloadFragment(root, productId); }
      finally { setVariantsBusy(false); }
    }
  };

  if (root._vsAbort instanceof AbortController) root._vsAbort.abort();
  const ac = new AbortController();
  root._vsAbort = ac;
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn, { signal: ac.signal });
  const setBusy = (btn, busy) => {
    if (!btn) return;
    const isBusy = !!busy;
    btn.disabled = isBusy;
    btn.classList.toggle('av3-opacity-60', isBusy);
    btn.classList.toggle('cursor-wait', isBusy);
    btn.setAttribute('aria-busy', isBusy ? 'true' : 'false');
  };

  const ivaSelect = root.querySelector('[data-role="vs-iva-select"]');
  const marginInput = root.querySelector('[data-role="vs-bulk-value"][data-field="margin_pct"]');
  const bulkCostInput = root.querySelector('[data-role="vs-bulk-value"][data-field="cost"]');
  const bulkPriceInput = root.querySelector('[data-role="vs-bulk-value"][data-field="price"]');

  try { initAttrQuickCreate(root, productId); } catch (_) {}

  on(root.querySelector('[data-role="vs-config-toggle"]'), 'click', (e) => {
    e.preventDefault();
    const collapsed = isConfigCollapsed(root);
    setConfigCollapsedDom(root, !collapsed);
    setConfigCollapsed(productId, !collapsed);
    syncGenerateWrapFromDom(root);
  });

  bindPricingActions({
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    setBusy,
    ivaSelect,
    marginInput,
    bulkCostInput,
    bulkPriceInput,
  });

  bindAttributeActions({
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    setBusy,
    syncGenerateWrapFromDom,
    setConfigCollapsedDom: (collapsed) => setConfigCollapsedDom(root, collapsed),
    setConfigCollapsed: (collapsed) => setConfigCollapsed(productId, collapsed),
  });

  on(root.querySelector('[data-role="vs-save-all"]'), 'click', async (e) => {
    e.preventDefault();
    await saveDirtyRows(root, productId);
  });

  bindLifecycleActions({
    root,
    on,
    fb,
    enqueue,
    postForm,
    reload,
    productId,
    toInt,
    normalizeErrMessage,
  });
}

export function syncGenerateWrapFromDom(root) {
  const wrap = root.querySelector('[data-role="vs-generate-wrap"]');
  if (!wrap) return;

  const btn = root.querySelector('[data-role="vs-generate-btn"], [data-role="vs-generate"]');
  const countEl = root.querySelector('[data-role="vs-combo-count"]');
  const total = computeCombinationCount(root);
  const hasVariants = getMetaFlag(root, 'hasVariants');
  const dirtySel = (root.dataset.vsSelectionDirty === '1');
  const missing = getMissingSelectionNames(root);

  if (countEl) {
    if (total > 0) {
      if (hasVariants && !dirtySel) {
        countEl.textContent = 'Combinaciones al dia. Para cambiar, ajusta la seleccion.';
      } else {
        countEl.textContent = dirtySel
          ? `Cambios pendientes: se generaran hasta ${total} variante(s).`
          : `Se generaran hasta ${total} variante(s).`;
      }
    } else {
      countEl.textContent = missing.length
        ? `Selecciona al menos 1 valor en: ${missing.join(', ')}.`
        : 'Selecciona al menos 1 valor por atributo para generar.';
    }
  }

  if (btn instanceof HTMLButtonElement) {
    const canGenerate = (total > 0) && (!hasVariants || dirtySel);
    btn.disabled = !canGenerate;
    if (total > 0) {
      if (hasVariants && !dirtySel) {
        btn.textContent = 'Combinaciones al dia';
      } else {
        btn.textContent = dirtySel
          ? `Actualizar combinaciones (${total})`
          : `Generar ${total} variante(s)`;
      }
    } else {
      btn.textContent = 'Generar combinaciones';
    }
    btn.classList.toggle('av3-opacity-60', btn.disabled);
  }
}

export function ensureUnsavedGuard() {
  if (window.__pvUnsavedGuard) return;
  window.__pvUnsavedGuard = true;
  window.addEventListener('beforeunload', (e) => {
    const roots = Array.from(document.querySelectorAll('#variants-sale, [data-variants-root]'));
    const hasPending = roots.some((root) => {
      const rows = Array.from(root.querySelectorAll('[data-role="vs-row"]'));
      const hasDirty = rows.some((row) => isRowDirty(row));
      const hasDirtySel = (root.dataset && root.dataset.vsSelectionDirty === '1');
      return hasDirty || hasDirtySel;
    });
    if (!hasPending) return;
    e.preventDefault();
    e.returnValue = '';
  });
}

function isConfigCollapsed(root) {
  const body = root.querySelector('[data-role="vs-config-body"]');
  return !!(body && body.classList.contains('av3-hidden'));
}

function setConfigCollapsedDom(root, collapsed) {
  const body = root.querySelector('[data-role="vs-config-body"]');
  const toggle = root.querySelector('[data-role="vs-config-toggle"]');
  if (body) body.classList.toggle('av3-hidden', collapsed);
  if (toggle) toggle.textContent = collapsed ? 'Editar' : 'Listo';
}

export function syncConfigCollapsedFromStorage(root, productId) {
  const collapsed = getStoredConfigCollapsed(productId);
  setConfigCollapsedDom(root, collapsed);
  syncGenerateWrapFromDom(root);
  syncSaveToolbar(root);
}

function getStoredConfigCollapsed(productId) {
  try {
    return localStorage.getItem(`pv:config:collapsed:${productId}`) === '1';
  } catch (_) {
    return false;
  }
}
