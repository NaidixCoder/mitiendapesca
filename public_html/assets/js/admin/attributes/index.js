// Admin: Atributos + Plantillas
export function init() {
  const root = document.querySelector('[data-role="attr-sets-root"]');
  const meta = document.querySelector('[data-role="attr-sets-meta"]');
  if (!root || !meta) return;

  const endpointItems = meta.dataset.endpointItems || '';
  const endpointDelete = meta.dataset.endpointDelete || '';
  const endpointEdit = meta.dataset.endpointEdit || '';
  const endpointSave = meta.dataset.endpointSave || '';
  const csrf = meta.dataset.csrf || '';

  const modal = root.querySelector('[data-role="set-modal"]');
  const modalBody = root.querySelector('[data-role="set-modal-body"]');

  const toastSafe = (kind, msg) => {
    try { if (typeof window.toast === 'function') { window.toast(kind, msg); return; } } catch (e) {}
    if (msg) alert(msg);
  };

  const openModal = () => { if (modal) modal.classList.remove('av3-hidden'); };
  const closeModal = () => { if (modal) modal.classList.add('av3-hidden'); };

  root.querySelectorAll('[data-role="set-modal-close"],[data-role="set-modal-close-2"],[data-role="set-modal-backdrop"]')
    .forEach(el => el.addEventListener('click', closeModal));

  const setModalLoading = () => {
    if (!modalBody) return;
    modalBody.innerHTML = '<div class="av3-type-body av3-text--color-var---admin-muted">Cargando…</div>';
    openModal();
  };

  const loadHtml = async (url, opts = {}) => {
    setModalLoading();
    try {
      const res = await fetch(url, { credentials: 'same-origin', ...opts });
      const html = await res.text();
      if (modalBody) modalBody.innerHTML = html;
    } catch (e) {
      if (modalBody) modalBody.innerHTML = '<div class="av3-type-body av3-text--color-var---admin-muted">No se pudo cargar.</div>';
    }
  };

  const loadItems = async (setId) => {
    if (!endpointItems) return;
    const url = endpointItems + '?id=' + encodeURIComponent(String(setId));
    await loadHtml(url);
  };

  const loadEdit = async (setId) => {
    if (!endpointEdit) return;
    const url = endpointEdit + '?id=' + encodeURIComponent(String(setId));
    await loadHtml(url);
  };

  const deleteSet = async (setId) => {
    if (!endpointDelete) return;
    const ok = window.confirm('¿Eliminar esta plantilla? Esta acción no se puede deshacer.');
    if (!ok) return;

    try {
      const fd = new FormData();
      fd.append('id', String(setId));
      if (csrf) fd.append('_csrf', csrf);

      const res = await fetch(endpointDelete, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || data.ok !== true) {
        toastSafe('error', (data && data.message) ? data.message : 'No se pudo eliminar.');
        return;
      }

      const row = root.querySelector('[data-set-row="'+String(setId)+'"]');
      if (row) row.remove();
      toastSafe('success', data.message || 'Plantilla eliminada.');
    } catch (e) {
      toastSafe('error', 'No se pudo eliminar.');
    }
  };

  const saveEditForm = async (form) => {
    if (!endpointSave) return;
    try {
      const fd = new FormData(form);
      // CSRF redundante por si el fragmento no lo incluye (igual lo trae)
      if (csrf && !fd.get('_csrf')) fd.append('_csrf', csrf);

      const btn = form.querySelector('[data-role="set-save"]');
      if (btn) { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }

      const res = await fetch(endpointSave, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json().catch(() => null);

      if (btn) { btn.disabled = false; btn.removeAttribute('aria-busy'); }

      if (!res.ok || !data || data.ok !== true) {
        toastSafe('error', (data && data.message) ? data.message : 'No se pudo guardar.');
        return;
      }

      // actualizar fila en tabla (nombre/cat/marca/items)
      const row = (data.data && data.data.id) ? root.querySelector('[data-set-row="'+String(data.data.id)+'"]') : null;
      if (row && data.data) {
        const tds = row.querySelectorAll('td');
        // columnas: 0 name,1 category,2 brand,3 items,4 actions
        if (tds[0]) tds[0].textContent = data.data.name || '';
        if (tds[1]) tds[1].textContent = data.data.category_name || '—';
        if (tds[2]) tds[2].textContent = data.data.brand_name || '—';
        if (tds[3]) tds[3].textContent = String(data.data.items_count ?? '');
      }

      toastSafe('success', data.message || 'Plantilla actualizada.');
      closeModal();
    } catch (e) {
      toastSafe('error', 'No se pudo guardar.');
    }
  };

  
const addValuesToEditForm = async (form, btn) => {
  if (!endpointEdit) return;
  const attrId = parseInt(btn.dataset.attrId || '0', 10);
  if (!attrId) return;

  const input = form.querySelector('[data-role="set-add-values-input"][data-attr-id="' + String(attrId) + '"]');
  const csv = input ? String(input.value || '').trim() : '';
  if (!csv) {
    toastSafe('error', 'Escribí valores separados por coma.');
    return;
  }

  const fd = new FormData(form);
  fd.set('add_values_attribute_id', String(attrId));
  fd.set('add_values_csv', csv);

  await loadHtml(endpointEdit, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  });
};

const addAttributeToEditForm = async (form) => {
    if (!endpointEdit) return;
    const sel = form.querySelector('[data-role="set-add-attr-select"]');
    if (!sel) return;
    const addId = parseInt(sel.value || '0', 10);
    if (!addId) return;

    const fd = new FormData(form);
    fd.set('add_attribute_id', String(addId));

    // re-render del fragmento preservando selección actual
    await loadHtml(endpointEdit, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
  };

  // clicks en tabla (ver/editar/eliminar)
  root.addEventListener('click', (ev) => {
    const btnView = ev.target.closest('[data-role="set-view"]');
    if (btnView) {
      const id = btnView.dataset.setId;
      if (id) loadItems(id);
      return;
    }
    const btnEdit = ev.target.closest('[data-role="set-edit"]');
    if (btnEdit) {
      const id = btnEdit.dataset.setId;
      if (id) loadEdit(id);
      return;
    }
    const btnDel = ev.target.closest('[data-role="set-delete"]');
    if (btnDel) {
      const id = btnDel.dataset.setId;
      if (id) deleteSet(id);
      return;
    }
  });

  // submit/acciones dentro del modal (delegación)
  root.addEventListener('submit', (ev) => {
    const form = ev.target.closest('[data-role="set-edit-form"]');
    if (!form) return;
    ev.preventDefault();
    saveEditForm(form);
  });

  
  root.addEventListener('click', (ev) => {
    const btnAddAttr = ev.target.closest('[data-role="set-add-attr"]');
    if (btnAddAttr) {
      const form = ev.target.closest('[data-role="set-edit-form"]');
      if (form) addAttributeToEditForm(form);
      return;
    }

    const btnAddValues = ev.target.closest('[data-role="set-add-values"]');
    if (btnAddValues) {
      const form = ev.target.closest('[data-role="set-edit-form"]');
      if (form) addValuesToEditForm(form, btnAddValues);
      return;
    }
  });

}
