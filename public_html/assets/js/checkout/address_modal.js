// address_modal.js
// Modal de direccion para Checkout.

export function init() {
  const modal = document.getElementById('checkout-address-modal');
  if (!modal) return;

  const getAddressSelectUrl = () => {
    const root = document.querySelector('[data-address-select-url]');
    return root ? String(root.getAttribute('data-address-select-url') || '') : '';
  };

  const closeBtns = Array.from(modal.querySelectorAll('[data-modal-close]'));
  const backdrop = modal.querySelector('[data-modal-backdrop]');
  const msgEl = modal.querySelector('[data-modal-message]');
  const form = modal.querySelector('form[data-address-form]');
  const defaultMessage = msgEl ? String(msgEl.textContent || '').trim() : '';
  const profilePhone = form ? String(form.dataset.profilePhone || '').trim() : '';
  const fieldLabels = {
    full_name: 'nombre y apellido',
    phone: 'telefono',
    street: 'calle',
    number: 'numero',
    zip: 'codigo postal',
    city: 'localidad',
    state: 'provincia',
  };

  const q = (name) => form ? form.querySelector(`[name="${name}"]`) : null;

  const persistSelectedAddress = async (addressId) => {
    const url = getAddressSelectUrl();
    const csrf = q('_csrf')?.value || '';
    if (!url || !csrf || !(Number(addressId) > 0)) return;

    try {
      await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'fetch',
          'X-CSRF-Token': csrf,
        },
        body: JSON.stringify({ address_id: Number(addressId) }),
      });
    } catch (_) {}
  };

  const setOpen = (open) => {
    modal.classList.toggle('ui-is-hidden', !open);
    document.body.classList.toggle('site-overflow-hidden', open);
  };

  const setValue = (name, val) => {
    const el = q(name);
    if (!el) return;
    el.value = (val ?? '') === null ? '' : String(val ?? '');
  };

  const labelFor = (field) => {
    const input = q(field);
    return input ? input.closest('label') : null;
  };

  const clearLabelState = (field) => {
    const lb = labelFor(field);
    if (!lb) return;
    lb.classList.remove('is-missing');
  };

  const markLabelMissing = (field) => {
    const lb = labelFor(field);
    if (!lb) return;
    lb.classList.add('is-missing');
  };

  const normalizeMeta = (raw = {}) => ({
    id: raw.id || raw.ID || raw.address_id || '',
    fullName: raw.fullName || raw.full_name || raw.nombre || raw.name || '',
    phone: raw.phone || raw.telefono || raw.tel || '',
    street: raw.street || raw.calle || raw.address || '',
    number: raw.number || raw.numero || '',
    floor: raw.floor || raw.piso || '',
    apartment: raw.apartment || raw.depto || raw.department || '',
    zip: raw.zip || raw.codigo_postal || raw.cp || '',
    city: raw.city || raw.localidad || '',
    state: raw.state || raw.provincia || '',
    notes: raw.notes || raw.notas || '',
  });

  const readMetaFromCheckedRadio = () => {
    const el = document.querySelector('input[name="address_id"]:checked');
    if (!el) return {};
    return normalizeMeta({
      id: el.value || '',
      fullName: el.dataset.fullName || el.dataset.full_name || '',
      phone: el.dataset.phone || '',
      street: el.dataset.street || el.dataset.calle || '',
      number: el.dataset.number || el.dataset.numero || '',
      floor: el.dataset.floor || el.dataset.piso || '',
      apartment: el.dataset.apartment || el.dataset.depto || '',
      zip: el.dataset.zip || '',
      city: el.dataset.city || '',
      state: el.dataset.state || '',
      notes: el.dataset.notes || '',
    });
  };

  const readMetaFromRadio = (el) => {
    if (!el) return {};
    return normalizeMeta({
      id: el.value || '',
      fullName: el.dataset.fullName || el.dataset.full_name || '',
      phone: el.dataset.phone || '',
      street: el.dataset.street || el.dataset.calle || '',
      number: el.dataset.number || el.dataset.numero || '',
      floor: el.dataset.floor || el.dataset.piso || '',
      apartment: el.dataset.apartment || el.dataset.depto || '',
      zip: el.dataset.zip || '',
      city: el.dataset.city || '',
      state: el.dataset.state || '',
      notes: el.dataset.notes || '',
    });
  };

  const fill = (meta = {}) => {
    setValue('full_name', meta.fullName || '');
    setValue('phone', meta.phone || '');
    setValue('street', meta.street || '');
    setValue('number', meta.number || '');
    setValue('floor', meta.floor || '');
    setValue('apartment', meta.apartment || '');
    setValue('zip', meta.zip || '');
    setValue('city', meta.city || '');
    setValue('state', meta.state || '');
    setValue('notes', meta.notes || '');

    const idEl = q('id');
    if (idEl) idEl.value = meta.id || '';

    if (form) form.dataset.mode = meta && meta.id ? 'edit' : 'create';
  };

  const computeMissing = (meta = {}) => {
    const missing = [];
    if (!String(meta.fullName || '').trim()) missing.push({ key: 'full_name', label: fieldLabels.full_name });
    if (!String(meta.phone || '').trim()) missing.push({ key: 'phone', label: fieldLabels.phone });
    if (!String(meta.street || '').trim()) missing.push({ key: 'street', label: fieldLabels.street });
    if (!String(meta.number || '').trim()) missing.push({ key: 'number', label: fieldLabels.number });
    if (!String(meta.zip || '').trim()) missing.push({ key: 'zip', label: fieldLabels.zip });
    if (!String(meta.city || '').trim()) missing.push({ key: 'city', label: fieldLabels.city });
    if (!String(meta.state || '').trim()) missing.push({ key: 'state', label: fieldLabels.state });
    return missing;
  };

  const showMessage = (missing = [], message = '') => {
    if (!msgEl) return;
    if (!missing.length && !String(message || '').trim()) {
      msgEl.classList.remove('is-error');
      msgEl.textContent = defaultMessage || 'Ingresa la direccion donde queres recibir tu pedido.';
      return;
    }
    msgEl.classList.add('is-error');
    if (String(message || '').trim()) {
      msgEl.textContent = String(message || '').trim();
      return;
    }
    const labels = missing.map((m) => m.label).join(', ');
    msgEl.textContent = `Completa tu direccion para continuar. Falta: ${labels}.`;
  };

  const applyMissingStyles = (missing = []) => {
    ['full_name', 'phone', 'street', 'number', 'zip', 'city', 'state'].forEach(clearLabelState);
    missing.forEach((m) => markLabelMissing(m.key));
  };

  const applyServerErrors = (errors = {}) => {
    const items = Object.entries(errors)
      .filter(([field, message]) => q(field) && String(message || '').trim() !== '')
      .map(([field, message]) => ({
        key: field,
        label: fieldLabels[field] || field,
        message: String(message || '').trim(),
      }));

    applyMissingStyles(items);
    return items;
  };

  const open = (detail = {}) => {
    const forceCreate = !!(detail && detail.mode === 'create');
    const metaFromDetail = normalizeMeta(detail && detail.meta ? detail.meta : {});
    const metaFromRadio = forceCreate ? {} : readMetaFromCheckedRadio();
    const meta = {
      ...metaFromRadio,
      ...Object.fromEntries(Object.entries(metaFromDetail).filter(([, v]) => String(v || '').trim() !== '')),
    };

    if (forceCreate && !String(meta.phone || '').trim() && profilePhone) {
      meta.phone = profilePhone;
    }

    fill(meta);
    showMessage([]);
    applyMissingStyles([]);
    setOpen(true);

    try { q('full_name')?.focus?.(); } catch (_) {}
  };

  const close = () => setOpen(false);

  window.checkoutOpenAddressModal = (detail = {}) => open(detail);
  window.checkoutCloseAddressModal = () => close();

  document.addEventListener('checkout:openAddressModal', (e) => open(e.detail || {}));

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="checkout-create-address"]');
    if (!btn) return;
    e.preventDefault();
    open({ mode: 'create', meta: {} });
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="checkout-edit-address"]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const card = btn.closest('.checkout-address-card');
    const radio = card ? card.querySelector('input[name="address_id"]') : null;
    open({ meta: readMetaFromRadio(radio) });
  });

  closeBtns.forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      close();
    });
  });

  backdrop?.addEventListener('click', (e) => {
    e.preventDefault();
    close();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (modal.classList.contains('ui-is-hidden')) return;
    e.preventDefault();
    close();
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const mode = String(form.dataset.mode || 'edit').toLowerCase();
    const createUrl = form.dataset.createUrl || '/cuenta/direcciones/crear';
    const updateUrl = form.dataset.updateUrl || '/cuenta/direcciones/guardar';
    const returnUrl = form.dataset.returnUrl || '/checkout?step=2';
    const url = mode === 'create' ? createUrl : updateUrl;

    const meta = normalizeMeta({
      id: q('id')?.value || '',
      fullName: q('full_name')?.value || '',
      phone: q('phone')?.value || '',
      street: q('street')?.value || '',
      number: q('number')?.value || '',
      floor: q('floor')?.value || '',
      apartment: q('apartment')?.value || '',
      zip: q('zip')?.value || '',
      city: q('city')?.value || '',
      state: q('state')?.value || '',
      notes: q('notes')?.value || '',
    });

    const missing = computeMissing(meta);
    if (missing.length) {
      showMessage(missing);
      applyMissingStyles(missing);
      try { q(missing[0].key)?.focus?.(); } catch (_) {}
      return;
    }

    const fd = new FormData(form);
    const btn = form.querySelector('[data-save-btn]');
    const prevText = btn ? btn.textContent : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Guardando...';
    }

    try {
      const res = await fetch(url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'fetch',
        },
      });

      let data = null;
      try { data = await res.json(); } catch (_) { data = null; }

      if (!res.ok || !data || data.ok !== true) {
        if (data && data.errors && typeof data.errors === 'object') {
          const serverErrors = applyServerErrors(data.errors);
          showMessage(serverErrors, data.error || 'Revisa los campos marcados.');
          try { q(serverErrors[0]?.key || '')?.focus?.(); } catch (_) {}
        } else {
          showMessage([], (data && data.error) ? data.error : 'No se pudo guardar la direccion.');
        }
        if (btn) {
          btn.disabled = false;
          btn.textContent = prevText || 'Guardar y continuar';
        }
        return;
      }

      const persistedAddressId = Number(data.id || q('id')?.value || meta.id || 0);
      await persistSelectedAddress(persistedAddressId);
      try { sessionStorage.setItem('checkout_step', '2'); } catch (_) {}
      window.location.href = String(data.redirect_url || returnUrl);
    } catch (err) {
      console.error('Error guardando direccion:', err);
      showMessage([], 'No se pudo guardar la direccion.');
      if (btn) {
        btn.disabled = false;
        btn.textContent = prevText || 'Guardar y continuar';
      }
    }
  });
}
