import { normalizeMoney, formatMoney, isMoneyField } from './money.js';
import { toastSafe } from './feedback.js';
import { cssEscape, flashEl } from './utils.js';
import { syncSaveToolbar, isRowDirty } from './save_actions.js';

export function bindPricingBulkActions(ctx) {
  const { root, on, ivaSelect, marginInput, bulkCostInput, bulkPriceInput } = ctx;

  const stripPct = (v) => String(v || '').replace(/%/g, '').trim();
  const parseNum = (v) => {
    const raw = String(v || '').trim();
    if (!raw) return NaN;
    const cleaned = raw.replace(/[^0-9,.-]/g, '');
    const normalized = cleaned.replace(/\.(?=.*\.)/g, '').replace(',', '.');
    const n = Number(normalized);
    return Number.isFinite(n) ? n : NaN;
  };
  const getIvaFactor = () => {
    if (!ivaSelect) return NaN;
    const n = parseNum(ivaSelect.value);
    if (!Number.isFinite(n)) return NaN;
    return n > 1 ? (n / 100) : n;
  };
  const getMarginFactor = () => {
    if (!marginInput) return NaN;
    const n = parseNum(stripPct(marginInput.value));
    if (!Number.isFinite(n)) return NaN;
    return n >= 1 ? (n / 100) : n;
  };
  const formatPctInside = () => {
    if (!marginInput) return;
    const n = parseNum(stripPct(marginInput.value));
    if (!Number.isFinite(n)) return;
    marginInput.value = String(n).replace(/\.0+$/, '') + '%';
  };
  const unformatPctInside = () => {
    if (!marginInput) return;
    marginInput.value = stripPct(marginInput.value);
  };
  const syncHiddenMargins = () => {
    if (!marginInput) return;
    const raw = stripPct(marginInput.value);
    root.querySelectorAll('.pv-row input[data-field="margin_pct"]').forEach((h) => {
      h.value = raw;
      if (raw !== '') h.value = String(raw) + '%';
      h.dispatchEvent(new Event('input', { bubbles: true }));
    });
  };
  const computePrice = (cost, iva, marginFactor) => {
    if (!Number.isFinite(cost) || cost <= 0) return NaN;
    if (!Number.isFinite(iva)) return NaN;
    if (!Number.isFinite(marginFactor)) return NaN;
    return Math.round(cost * (1 + iva) * (1 + marginFactor));
  };
  const recalcBulkPriceFromCostAndMargin = () => {
    if (!bulkCostInput || !bulkPriceInput) return;
    if (document.activeElement === bulkPriceInput) return;
    const iva = getIvaFactor();
    const m = getMarginFactor();
    const cost = parseNum(bulkCostInput.value);
    const next = computePrice(cost, iva, m);
    if (!Number.isFinite(next)) return;
    bulkPriceInput.value = formatMoney(String(next));
    bulkPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const updateRowMarginFromRowPrice = (row) => {
    const iva = getIvaFactor();
    if (!Number.isFinite(iva)) return;
    const costInp = row.querySelector('input[data-field="cost"]');
    const priceInp = row.querySelector('input[data-field="price"]');
    const rowMarginInp = row.querySelector('input[data-field="margin_pct"]');
    if (!costInp || !priceInp || !rowMarginInp) return;
    if (document.activeElement === rowMarginInp) return;
    const cost = parseNum(costInp.value);
    const price = parseNum(priceInp.value);
    if (!Number.isFinite(cost) || !Number.isFinite(price) || cost <= 0) return;
    const m = (price / (cost * (1 + iva))) - 1;
    if (!Number.isFinite(m)) return;
    const pct = Math.round(m * 1000) / 10;
    rowMarginInp.value = String(pct).replace(/\.0+$/, '') + '%';
    rowMarginInp.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const recalcAllPricesFromMargin = () => {
    const iva = getIvaFactor();
    const m = getMarginFactor();
    if (!Number.isFinite(iva) || !Number.isFinite(m)) return;
    root.querySelectorAll('.pv-row').forEach((row) => {
      const costInp = row.querySelector('input[data-field="cost"]');
      const priceInp = row.querySelector('input[data-field="price"]');
      if (!costInp || !priceInp) return;
      const cost = parseNum(costInp.value);
      if (!Number.isFinite(cost)) return;
      const price = Math.round(cost * (1 + iva) * (1 + m));
      priceInp.value = formatMoney(String(price));
      priceInp.dispatchEvent(new Event('input', { bubbles: true }));
    });
  };
  const formatRowMarginPctInputs = () => {
    root.querySelectorAll('[data-role="vs-row"] input[data-field="margin_pct"]').forEach((inp) => {
      if (!(inp instanceof HTMLInputElement)) return;
      const raw = String(inp.value || '').replace(/%/g, '').trim();
      if (raw === '') return;
      inp.value = raw + '%';
    });
  };

  if (marginInput) {
    if (stripPct(marginInput.value) !== '') formatPctInside();
    on(marginInput, 'focus', () => {
      unformatPctInside();
      try { marginInput.select(); } catch (_) {}
    });
    on(marginInput, 'blur', () => {
      if (stripPct(marginInput.value) === '') return;
      formatPctInside();
      syncHiddenMargins();
      recalcAllPricesFromMargin();
      recalcBulkPriceFromCostAndMargin();
    });
    on(marginInput, 'input', () => syncHiddenMargins());
  }

  formatRowMarginPctInputs();

  on(root, 'input', (e) => {
    const inp = e.target;
    if (!(inp instanceof HTMLInputElement)) return;
    const row = inp.closest?.('.pv-row');
    if (!row) return;
    const field = inp.getAttribute('data-field') || '';
    if (field === 'cost') {
      const iva = getIvaFactor();
      const m = getMarginFactor();
      if (!Number.isFinite(iva) || !Number.isFinite(m)) return;
      const cost = parseNum(inp.value);
      if (!Number.isFinite(cost)) return;
      const priceInp = row.querySelector('input[data-field="price"]');
      if (!priceInp) return;
      const price = Math.round(cost * (1 + iva) * (1 + m));
      priceInp.value = formatMoney(String(price));
      priceInp.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (field === 'price') updateRowMarginFromRowPrice(row);
  });

  const stripPctValue = (v) => String(v || '').replace(/%/g, '').trim();
  const parsePctToFactor = (pctStr) => {
    const n = parseNum(stripPctValue(pctStr));
    if (!Number.isFinite(n)) return NaN;
    return n > 2 ? (n / 100) : n;
  };
  const factorToPct = (factor) => {
    if (!Number.isFinite(factor)) return '';
    const pct = factor * 100;
    const rounded = Math.round(pct * 100) / 100;
    return String(rounded).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
  };
  const getIvaPct = () => {
    const n = parseNum(ivaSelect ? ivaSelect.value : '');
    return Number.isFinite(n) ? n : 0;
  };
  const toMoneyRaw = (num) => {
    if (!Number.isFinite(num)) return '';
    const rounded = Math.round(num * 100) / 100;
    return String(rounded).replace(/\.0+$/, '');
  };
  const setMoneyValue = (inp, num) => {
    if (!(inp instanceof HTMLInputElement)) return;
    const raw = toMoneyRaw(num);
    inp.dataset.raw = raw;
    inp.value = raw === '' ? '' : formatMoney(raw);
    inp.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const setPctValue = (inp, pctStr) => {
    if (!(inp instanceof HTMLInputElement)) return;
    const clean = stripPctValue(pctStr);
    inp.value = clean === '' ? '' : (clean + '%');
    inp.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const recalcRowPrice = (row) => {
    const costEl = row.querySelector('input[data-field="cost"]');
    const marginEl = row.querySelector('input[data-field="margin_pct"]');
    const priceEl = row.querySelector('input[data-field="price"]');
    if (!(costEl instanceof HTMLInputElement) || !(marginEl instanceof HTMLInputElement) || !(priceEl instanceof HTMLInputElement)) return;
    const cost = Number(normalizeMoney(costEl.dataset.raw || costEl.value));
    const m = parsePctToFactor(marginEl.value);
    if (!Number.isFinite(cost) || !Number.isFinite(m)) return;
    const iva = getIvaPct() / 100;
    setMoneyValue(priceEl, cost * (1 + iva) * (1 + m));
  };
  const recalcRowMarginFromPrice = (row) => {
    const costEl = row.querySelector('input[data-field="cost"]');
    const marginEl = row.querySelector('input[data-field="margin_pct"]');
    const priceEl = row.querySelector('input[data-field="price"]');
    if (!(costEl instanceof HTMLInputElement) || !(marginEl instanceof HTMLInputElement) || !(priceEl instanceof HTMLInputElement)) return;
    const cost = Number(normalizeMoney(costEl.dataset.raw || costEl.value));
    const price = Number(normalizeMoney(priceEl.dataset.raw || priceEl.value));
    if (!Number.isFinite(cost) || !Number.isFinite(price) || cost <= 0) return;
    const iva = getIvaPct() / 100;
    const base = cost * (1 + iva);
    if (base <= 0) return;
    const pct = factorToPct((price / base) - 1);
    if (pct === '') return;
    setPctValue(marginEl, pct);
  };
  const recalcBulkPrice = () => {
    if (!(bulkCostInput instanceof HTMLInputElement) || !(marginInput instanceof HTMLInputElement) || !(bulkPriceInput instanceof HTMLInputElement)) return;
    const cost = Number(normalizeMoney(bulkCostInput.dataset.raw || bulkCostInput.value));
    const m = parsePctToFactor(marginInput.value);
    if (!Number.isFinite(cost) || !Number.isFinite(m)) return;
    const iva = getIvaPct() / 100;
    setMoneyValue(bulkPriceInput, cost * (1 + iva) * (1 + m));
  };
  const recalcBulkMarginFromPrice = () => {
    if (!(bulkCostInput instanceof HTMLInputElement) || !(marginInput instanceof HTMLInputElement) || !(bulkPriceInput instanceof HTMLInputElement)) return;
    const cost = Number(normalizeMoney(bulkCostInput.dataset.raw || bulkCostInput.value));
    const price = Number(normalizeMoney(bulkPriceInput.dataset.raw || bulkPriceInput.value));
    if (!Number.isFinite(cost) || !Number.isFinite(price) || cost <= 0) return;
    const iva = getIvaPct() / 100;
    const base = cost * (1 + iva);
    if (base <= 0) return;
    const pct = factorToPct((price / base) - 1);
    if (pct === '') return;
    setPctValue(marginInput, pct);
  };

  on(root, 'focusin', (e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) return;
    const field = String(el.dataset.field || '');
    if (field === 'margin_pct') {
      el.value = String(el.value || '').replace(/%/g, '').trim();
      return;
    }
    if (!isMoneyField(field)) return;
    el.value = normalizeMoney(el.value);
  });

  on(root, 'focusout', (e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) return;
    const field = String(el.dataset.field || '');
    if (field === 'margin_pct') {
      const rawPct = String(el.value || '').replace(/%/g, '').trim();
      if (rawPct !== '') el.value = rawPct + '%';
      const row = el.closest('[data-role="vs-row"]');
      if (row) recalcRowPrice(row);
      else if (el.closest('[data-role="vs-bulk"]')) recalcBulkPrice();
      return;
    }
    if (!isMoneyField(field)) return;
    const raw = normalizeMoney(el.value);
    el.dataset.raw = raw;
    el.value = raw === '' ? '' : formatMoney(raw);
    if (field === 'cost') {
      const row = el.closest('[data-role="vs-row"]');
      if (row) recalcRowPrice(row);
      else if (el.closest('[data-role="vs-bulk"]')) recalcBulkPrice();
    } else if (field === 'price') {
      const row = el.closest('[data-role="vs-row"]');
      if (row) recalcRowMarginFromPrice(row);
      else if (el.closest('[data-role="vs-bulk"]')) recalcBulkMarginFromPrice();
    }
  });

  on(root, 'click', (e) => {
    const el = e.target;
    if (!(el instanceof HTMLElement)) return;
    const role = String(el.dataset.role || '');
    if (role !== 'vs-bulk-apply-all' && role !== 'vs-bulk-apply-empty') return;
    e.preventDefault();
    const onlyEmpty = role === 'vs-bulk-apply-empty';

    const bulkInputs = Array.from(root.querySelectorAll('[data-role="vs-bulk-value"][data-field]'))
      .filter((i) => i instanceof HTMLInputElement)
      .map((i) => i);

    const fieldsWithValue = bulkInputs
      .map((inp) => ({
        field: String(inp.dataset.field || '').trim(),
        value: String(inp.value || '').trim(),
      }))
      .filter((x) => x.field && x.value !== '');

    if (fieldsWithValue.length === 0) {
      toastSafe('warn', 'Nada para aplicar: carga al menos un valor en acciones masivas.');
      return;
    }

    let applied = 0;
    let touchedRows = 0;
    const rows = Array.from(root.querySelectorAll('[data-role="vs-row"]'))
      .filter((r) => r instanceof HTMLElement)
      .map((r) => r);

    rows.forEach((row) => {
      let rowChanged = false;
      fieldsWithValue.forEach(({ field, value }) => {
        const inp = row.querySelector(`input[data-field="${cssEscape(field)}"]`);
        if (!(inp instanceof HTMLInputElement)) return;
        const current = isMoneyField(field) ? normalizeMoney(inp.value) : String(inp.value || '').trim();
        const incoming = isMoneyField(field) ? normalizeMoney(value) : value;
        if (incoming === '') return;
        if (onlyEmpty && current !== '') return;
        if (current === incoming) return;

        inp.value = isMoneyField(field) ? formatMoney(incoming) : incoming;
        inp.dispatchEvent(new Event('input', { bubbles: true }));
        applied++;
        rowChanged = true;
      });
      if (rowChanged) touchedRows++;
    });

    if (applied === 0) {
      toastSafe('warn', onlyEmpty ? 'No se encontraron campos vacios para completar.' : 'No se aplicaron cambios.');
    } else {
      toastSafe('ok', onlyEmpty
        ? `Completado: ${applied} campo(s) vacio(s) en ${touchedRows} variante(s).`
        : `Aplicado: ${applied} campo(s) en ${touchedRows} variante(s).`
      );
    }

    syncSaveToolbar(root);
  });

  on(root, 'input', (e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) return;
    if (!el.dataset.field) return;
    if (isMoneyField(String(el.dataset.field || ''))) {
      el.dataset.raw = normalizeMoney(el.value);
    }
    el.classList.remove('input-error');
    el.removeAttribute('aria-invalid');
    el.removeAttribute('title');
    const row = el.closest('[data-role="vs-row"]');
    if (row instanceof HTMLElement) {
      row.classList.toggle('pv-dirty', isRowDirty(row));
    }
    flashEl(el);
    syncSaveToolbar(root);
  });
}
