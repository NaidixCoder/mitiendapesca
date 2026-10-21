import { postForm } from './api.js';
import { toastSafe } from './feedback.js';

export function initAttrQuickCreate(root, productId) {
  const openBtn = root.querySelector('[data-role="vs-attr-quick-open"]');
  const modal = root.querySelector('[data-role="vs-attr-quick-modal"]');
  if (!openBtn || !modal) return;

  const backdrop = modal.querySelector('[data-role="vs-attr-quick-backdrop"]');
  const closeBtn = modal.querySelector('[data-role="vs-attr-quick-close"]');
  const cancelBtn = modal.querySelector('[data-role="vs-attr-quick-cancel"]');
  const saveBtn = modal.querySelector('[data-role="vs-attr-quick-save"]');

  const nameInp = modal.querySelector('[data-role="vs-attr-quick-name"]');
  const typeSel = modal.querySelector('[data-role="vs-attr-quick-type"]');
  const addToProductChk = modal.querySelector('[data-role="vs-attr-quick-add-to-product"]');

  const valuesWrap = modal.querySelector('[data-role="vs-attr-quick-values-wrap"]');
  const valueInp = modal.querySelector('[data-role="vs-attr-quick-value"]');
  const valueAddBtn = modal.querySelector('[data-role="vs-attr-quick-value-add"]');
  const valuesBox = modal.querySelector('[data-role="vs-attr-quick-values"]');
  const valuesHidden = modal.querySelector('[data-role="vs-attr-quick-values-hidden"]');

  const errBox = modal.querySelector('[data-role="vs-attr-quick-error"]');
  const endpoint = root.dataset.endpointAttrQuick || '/admin/attributes/quick';
  const nounSingular = 'opcion de variante';

  if (modal._vsAttrQuickInit) return;
  modal._vsAttrQuickInit = true;

  let values = [];

  const showError = (msg) => {
    if (!errBox) return;
    errBox.textContent = msg || `No se pudo crear la ${nounSingular}.`;
    errBox.classList.remove('av3-hidden');
  };

  const clearError = () => {
    if (!errBox) return;
    errBox.textContent = '';
    errBox.classList.add('av3-hidden');
  };

  const syncHidden = () => {
    if (valuesHidden) valuesHidden.value = JSON.stringify(values);
  };

  const renderChips = () => {
    if (!valuesBox) return;
    valuesBox.innerHTML = '';
    values.forEach((v) => {
      const chip = document.createElement('span');
      chip.className = 'av3-inline-flex av3-items-center av3-gap-2 av3-radius-full av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-x3 av3-pad-y1 av3-type-caption';
      const txt = document.createElement('span');
      txt.textContent = v;
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'av3-text-slate-500 av3-hover-text-slate-900';
      rm.textContent = 'x';
      rm.addEventListener('click', () => {
        values = values.filter((x) => x !== v);
        syncHidden();
        renderChips();
      }, { signal: root._vsAbort?.signal });
      chip.appendChild(txt);
      chip.appendChild(rm);
      valuesBox.appendChild(chip);
    });
  };

  const addValuesFromInput = () => {
    if (!valueInp) return;
    const raw = (valueInp.value || '').trim();
    if (!raw) return;
    const parts = raw.split(',').map((s) => s.trim()).filter(Boolean);
    parts.forEach((p) => {
      if (!values.includes(p)) values.push(p);
    });
    valueInp.value = '';
    syncHidden();
    renderChips();
  };

  const resetForm = () => {
    clearError();
    if (nameInp) nameInp.value = '';
    if (typeSel) typeSel.value = 'select';
    if (addToProductChk) addToProductChk.checked = true;
    values = [];
    syncHidden();
    renderChips();
    if (valuesWrap) valuesWrap.classList.remove('av3-hidden');
  };

  const open = () => {
    resetForm();
    modal.classList.remove('av3-hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('av3-overflow-hidden');
    setTimeout(() => { try { nameInp && nameInp.focus(); } catch (_) {} }, 0);
  };

  const close = () => {
    modal.classList.add('av3-hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('av3-overflow-hidden');
  };

  const onTypeChange = () => {
    const t = (typeSel && typeSel.value) ? typeSel.value : 'select';
    if (!valuesWrap) return;
    if (t === 'select') {
      valuesWrap.classList.remove('av3-hidden');
    } else {
      valuesWrap.classList.add('av3-hidden');
      values = [];
      syncHidden();
      renderChips();
    }
  };

  const save = async () => {
    clearError();
    const name = (nameInp && nameInp.value) ? nameInp.value.trim() : '';
    const type = (typeSel && typeSel.value) ? typeSel.value : 'select';
    if (!name) { showError('El nombre es obligatorio.'); return; }
    if (!['select', 'text', 'number'].includes(type)) { showError('Tipo invalido.'); return; }

    const payload = { name, type, is_variant_axis: '1' };
    if (type === 'select') payload.values = JSON.stringify(values);

    saveBtn.disabled = true;
    try {
      const data = await postForm(endpoint, payload);
      if (!data || typeof data !== 'object' || !data.ok || !data.id) {
        throw new Error('Respuesta invalida del servidor.');
      }

      const attrSelect = root.querySelector('[data-role="vs-attr-select"]');
      if (attrSelect) {
        const idStr = String(data.id);
        let opt = Array.from(attrSelect.options).find((o) => String(o.value) === idStr);
        if (!opt) {
          opt = document.createElement('option');
          opt.value = idStr;
          opt.textContent = data.name || name;
          attrSelect.appendChild(opt);
        } else {
          opt.textContent = data.name || opt.textContent;
          opt.disabled = false;
        }

        if (addToProductChk && addToProductChk.checked) {
          attrSelect.value = idStr;
          attrSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
          attrSelect.value = '';
        }
      }

      close();
      toastSafe('ok', 'Opcion de variante creada.');
    } catch (err) {
      const msg = (err && err.data && err.data.message) ? err.data.message
        : (err && err.data && typeof err.data === 'string') ? err.data
          : (err && err.message) ? err.message
            : `No se pudo crear la ${nounSingular}.`;
      showError(msg);
    } finally {
      saveBtn.disabled = false;
    }
  };

  openBtn.addEventListener('click', open, { signal: root._vsAbort?.signal });
  [closeBtn, cancelBtn, backdrop].filter(Boolean).forEach((el) => {
    el.addEventListener('click', close, { signal: root._vsAbort?.signal });
  });

  if (typeSel) typeSel.addEventListener('change', onTypeChange, { signal: root._vsAbort?.signal });
  if (valueAddBtn) valueAddBtn.addEventListener('click', addValuesFromInput, { signal: root._vsAbort?.signal });
  if (valueInp) {
    valueInp.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter') { ev.preventDefault(); addValuesFromInput(); }
    }, { signal: root._vsAbort?.signal });
  }
  if (saveBtn) saveBtn.addEventListener('click', save, { signal: root._vsAbort?.signal });
}
