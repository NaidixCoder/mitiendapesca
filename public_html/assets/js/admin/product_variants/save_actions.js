import { postForm } from './api.js';
import { toInt } from './validators.js';
import { normalizeMoney, formatMoney, isMoneyField } from './money.js';
import { startAction, successScoped, warnScoped } from './notify.js';
import { markRowError } from './errors.js';
import { initAttrQuickCreate } from './attr_quick.js';

export async function saveDirtyRows(root, productId) {
  const btn = root.querySelector('[data-role="vs-save-all"]');
  const endpoint = root.dataset.endpointVariantSave;
  if (!btn || !endpoint) return;

  const dirtyRows = getDirtyRows(root);
  if (dirtyRows.length === 0) {
    syncSaveToolbar(root);
    return;
  }

  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Guardando...';

  let ok = 0;
  let fail = 0;
  const action = startAction('save-rows');

  for (const row of dirtyRows) {
    row.classList.add('av3-opacity-60');

    const variantId = toInt(row.dataset.variantId);
    if (!variantId || variantId <= 0) {
      row.classList.remove('pv-dirty');
      row.classList.remove('av3-opacity-60');
      continue;
    }
    const payload = collectRowPayload(row);

    if (payload.low_stock_threshold == null || payload.low_stock_threshold === '') payload.low_stock_threshold = 3;
    if (payload.is_active == null || payload.is_active === '') payload.is_active = 1;

    try {
      await postForm(endpoint, {
        product_id: productId,
        variant_id: variantId,
        ...payload,
      });

      row.querySelectorAll('input[data-field][data-orig]').forEach((inp) => {
        const field = String(inp.dataset.field || '');
        if (inp.type === 'checkbox') {
          inp.dataset.orig = inp.checked ? '1' : '0';
          return;
        }

        if (isMoneyField(field)) {
          const raw = normalizeMoney(inp.value);
          inp.dataset.orig = raw;
          inp.dataset.raw = raw;
          inp.value = raw === '' ? '' : formatMoney(raw);
          return;
        }

        inp.dataset.orig = inp.value;
      });

      ok++;
      row.classList.remove('pv-dirty');
      row.classList.add('pv-saved');
      window.setTimeout(() => row.classList.remove('pv-saved'), 950);
    } catch (err) {
      console.error(err);
      markRowError(row, err);
      fail++;
    } finally {
      row.classList.remove('av3-opacity-60');
    }
  }

  if (fail === 0) {
    successScoped(action, ok === 1 ? 'Variante guardada.' : `Variantes guardadas: ${ok}.`);
  } else {
    warnScoped(action, `Guardado parcial: ${ok} ok / ${fail} fallo. Revisa las filas en rojo.`);
  }

  btn.textContent = originalText;
  syncSaveToolbar(root);
  initAttrQuickCreate(root, productId);
}

function collectRowPayload(row) {
  const payload = {};
  row.querySelectorAll('input[data-field]').forEach((inp) => {
    const k = inp.dataset.field;
    if (!k) return;
    if (inp.type === 'checkbox') {
      payload[k] = inp.checked ? 1 : 0;
      return;
    }
    if (isMoneyField(String(k))) {
      payload[k] = normalizeMoney(inp.value);
      return;
    }
    if (String(k) === 'margin_pct') {
      payload[k] = String(inp.value || '').replace(/%/g, '').trim();
      return;
    }
    payload[k] = inp.value;
  });
  const selectedTagIds = Array.from(row.querySelectorAll('input[data-role="vs-variant-tag"]:checked'))
    .map((inp) => toInt(inp.value))
    .filter((id) => id > 0);
  payload.tag_ids = selectedTagIds.join(',');
  return payload;
}

export function getDirtyRows(root) {
  const rows = Array.from(root.querySelectorAll('[data-role="vs-row"]'));
  return rows.filter((row) => {
    const vid = toInt(row.dataset.variantId);
    if (!vid || vid <= 0) return false;
    return isRowDirty(row);
  });
}

export function isRowDirty(row) {
  const currentTagIds = Array.from(row.querySelectorAll('input[data-role="vs-variant-tag"]:checked'))
    .map((inp) => toInt(inp.value))
    .filter((id) => id > 0)
    .sort((a, b) => a - b)
    .join(',');
  const origTagIds = String(row.dataset.origTagIds || '')
    .split(',')
    .map((v) => toInt(v))
    .filter((id) => id > 0)
    .sort((a, b) => a - b)
    .join(',');
  if (currentTagIds !== origTagIds) {
    return true;
  }

  const inputs = Array.from(row.querySelectorAll('input[data-field][data-orig]'));
  return inputs.some((inp) => {
    const field = String(inp.dataset.field || '');
    const orig = String(inp.dataset.orig ?? '');
    if (isMoneyField(field)) {
      return normalizeMoney(inp.value) !== normalizeMoney(orig);
    }
    if (field === 'margin_pct') {
      const a = String(inp.value || '').replace(/%/g, '').trim();
      const b = String(orig || '').replace(/%/g, '').trim();
      return a !== b;
    }
    return inp.value !== orig;
  });
}

export function syncSaveToolbar(root) {
  const countEl = root.querySelector('[data-role="vs-changes-count"], [data-role="vs-dirty-count"]');
  const btn = root.querySelector('[data-role="vs-save-all"]');
  const savebar = root.querySelector('[data-role="vs-savebar"]');
  if (!countEl || !btn) return;

  const n = getDirtyRows(root).length;
  countEl.textContent = String(n);
  const dirtyFlag = (n > 0) ? '1' : '0';
  countEl.dataset.dirty = dirtyFlag;
  if (savebar) savebar.dataset.dirty = dirtyFlag;
  btn.disabled = !(n > 0);
}
