// Atributos y plantillas — interacciones propias del módulo Admin V3.
export function init() {
  const page = document.querySelector('.av3-attributes');
  if (!page || page.dataset.attributesInitialized === '1') return;
  page.dataset.attributesInitialized = '1';

  // Pestañas reales con URLs navegables: funcionan también sin JavaScript.
  const tabs = [...page.querySelectorAll('[data-attr-tab]')];
  const tabPanels = [...page.querySelectorAll('[data-attr-panel]')];
  const actionLinks = [...document.querySelectorAll('[data-attr-tab-action]')];
  const tablist = page.querySelector('[data-role="attributes-tabs"]');
  const setTab = (name, updateHistory = false) => {
    const tabName = name === 'templates' ? 'templates' : 'attributes';
    tabs.forEach((tab) => {
      const selected = tab.dataset.attrTab === tabName;
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
      tab.tabIndex = selected ? 0 : -1;
    });
    tabPanels.forEach((panel) => { panel.hidden = panel.dataset.attrPanel !== tabName; });
    actionLinks.forEach((link) => { link.hidden = link.dataset.attrTabAction !== tabName; });
    page.dataset.activeTab = tabName;
    page.querySelectorAll('.av3-attributes__menu[open]').forEach((menu) => { menu.open = false; });
    if (updateHistory) {
      const tab = tabs.find((item) => item.dataset.attrTab === tabName);
      if (tab) window.history.pushState({ attributesTab: tabName }, '', tab.href);
    }
  };
  // En desktop los filtros son siempre visibles; en móvil se pliegan si no están activos.
  // SSR mantiene <details open> para que un desktop sin JS también pueda operar.
  const board = page.querySelector('.av3-attributes__board');
  const advanced = page.querySelector('.av3-attributes__advanced');
  if (board && advanced) {
    let lastWide = null;
    const syncFilters = () => {
      const wide = board.getBoundingClientRect().width >= 780;
      if (wide === lastWide) return; // no colapsar una apertura manual en móvil
      advanced.open = wide || advanced.dataset.hasFilters === 'true';
      lastWide = wide;
    };
    syncFilters();
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(syncFilters).observe(board);
    } else {
      window.addEventListener('resize', syncFilters);
    }
  }
  if (tablist && tabs.length === 2) {
    tablist.addEventListener('click', (event) => {
      const clicked = event.target.closest('[data-attr-tab]');
      if (!clicked || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      if (clicked.getAttribute('aria-selected') !== 'true') setTab(clicked.dataset.attrTab, true);
    });
    tablist.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      const current = tabs.findIndex((tab) => tab === document.activeElement);
      if (current < 0) return;
      event.preventDefault();
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1
        : (current + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
      setTab(tabs[next].dataset.attrTab, true);
      tabs[next].focus();
    });
    window.addEventListener('popstate', () => {
      const params = new URLSearchParams(window.location.search);
      const legacyTemplates = !params.has('tab') && (params.has('sets_q') || params.has('sets_category_id') || params.has('sets_brand_id'));
      setTab(params.get('tab') === 'templates' || legacyTemplates ? 'templates' : 'attributes');
    });
    setTab(page.dataset.activeTab);
  }
  page.addEventListener('click', (event) => {
    const summary = event.target.closest('.av3-attributes__menu > summary');
    if (summary) {
      const targetMenu = summary.closest('.av3-attributes__menu');
      page.querySelectorAll('.av3-attributes__menu[open]').forEach((menu) => {
        if (menu !== targetMenu) menu.open = false;
      });
    } else if (!event.target.closest('.av3-attributes__menu')) {
      page.querySelectorAll('.av3-attributes__menu[open]').forEach((menu) => { menu.open = false; });
    }
  });
  document.addEventListener('click', (event) => {
    if (page.contains(event.target)) return;
    page.querySelectorAll('.av3-attributes__menu[open]').forEach((menu) => { menu.open = false; });
  });
  page.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const openMenu = page.querySelector('.av3-attributes__menu[open]');
    if (openMenu) {
      event.preventDefault();
      openMenu.open = false;
      openMenu.querySelector('summary')?.focus();
    }
  });

  page.addEventListener('submit', (event) => {
    const deleteForm = event.target.closest('[data-confirm-value-delete]');
    if (!deleteForm) return;
    if (!window.confirm('¿Eliminar este valor del catálogo? Esta acción no se puede deshacer.')) {
      event.preventDefault();
    }
  });

  const root = page.querySelector('[data-role="attr-sets-root"]');
  const meta = page.querySelector('[data-role="attr-sets-meta"]');
  if (!root || !meta) return;

  const endpointItems = meta.dataset.endpointItems || '';
  const endpointDelete = meta.dataset.endpointDelete || '';
  const endpointEdit = meta.dataset.endpointEdit || '';
  const endpointSave = meta.dataset.endpointSave || '';
  const csrf = meta.dataset.csrf || '';
  const modal = root.querySelector('[data-role="set-modal"]');
  const modalBody = root.querySelector('[data-role="set-modal-body"]');
  const dialog = root.querySelector('[data-role="set-modal-dialog"]');
  // Un portal evita que el containment del panel limite el overlay al ancho de la tarjeta.
  if (modal) document.body.appendChild(modal);
  let previousFocus = null;
  let previousOverflow = '';
  let requestToken = 0;

  const notify = (kind, message) => {
    try {
      if (typeof window.toast === 'function') {
        window.toast(kind, message);
        return;
      }
    } catch (_) {}
    if (message) window.alert(message);
  };

  const openModal = () => {
    if (!modal || !dialog) return;
    if (modal.hidden) {
      previousFocus = document.activeElement;
      previousOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
    }
    modal.hidden = false;
    dialog.focus();
  };

  const closeModal = () => {
    if (!modal || modal.hidden) return;
    ++requestToken;
    modal.hidden = true;
    document.body.style.overflow = previousOverflow;
    if (previousFocus && typeof previousFocus.focus === 'function' && previousFocus.isConnected) {
      previousFocus.focus();
    }
  };

  const showError = (message) => {
    if (!modalBody) return;
    const div = document.createElement('div');
    div.className = 'av3-attributes__notice av3-attributes__notice--warning';
    div.textContent = message;
    modalBody.replaceChildren(div);
  };

  const loadHtml = async (url, opts = {}) => {
    if (!modalBody) return;
    const activeToken = ++requestToken;
    modalBody.textContent = 'Cargando…';
    openModal();
    try {
      const response = await fetch(url, { credentials: 'same-origin', ...opts });
      const html = await response.text();
      if (activeToken !== requestToken) return;
      if (!response.ok) {
        showError('No se pudo cargar el contenido. Revisá tu sesión e intentá nuevamente.');
        return;
      }
      modalBody.innerHTML = html;
      const firstField = modalBody.querySelector('[data-role="set-edit-form"] input[name="name"]');
      if (firstField) firstField.focus();
      else if (dialog) dialog.focus();
    } catch (_) {
      if (activeToken === requestToken) showError('No se pudo conectar con el servidor.');
    }
  };

  const loadItems = (id) => {
    if (endpointItems) loadHtml(endpointItems + '?id=' + encodeURIComponent(String(id)));
  };
  const loadEdit = (id) => {
    if (endpointEdit) loadHtml(endpointEdit + '?id=' + encodeURIComponent(String(id)));
  };

  const deleteSet = async (id) => {
    if (!endpointDelete || !window.confirm('¿Eliminar esta plantilla? Esta acción no se puede deshacer.')) return;
    const data = new FormData();
    data.set('id', String(id));
    data.set('_csrf', csrf);
    try {
      const response = await fetch(endpointDelete, {
        method: 'POST', body: data, credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload || payload.ok !== true) {
        notify('error', payload?.message || 'No se pudo eliminar la plantilla.');
        return;
      }
      root.querySelectorAll('[data-set-row="' + String(id) + '"]').forEach((row) => row.remove());
      const remaining = root.querySelectorAll('[data-role="sets-desktop"] [data-set-row]').length;
      const count = root.querySelector('[data-role="sets-count"]');
      if (count) count.textContent = remaining + (remaining === 1 ? ' plantilla mostrada' : ' plantillas mostradas');
      if (!remaining) {
        root.querySelectorAll('[data-role="sets-desktop"], [data-role="sets-mobile"]').forEach((list) => { list.hidden = true; });
        const empty = root.querySelector('[data-role="sets-empty"]');
        if (empty) empty.hidden = false;
      }
      notify('success', payload.message || 'Plantilla eliminada.');
    } catch (_) {
      notify('error', 'No se pudo eliminar la plantilla.');
    }
  };

  const formError = (form, message) => {
    const feedback = form.querySelector('[data-role="set-form-error"]');
    if (feedback) {
      feedback.hidden = false;
      feedback.textContent = message;
      feedback.focus?.();
    } else notify('error', message);
  };

  const saveEditForm = async (form) => {
    if (!endpointSave) return;
    const button = form.querySelector('[data-role="set-save"]');
    if (button?.disabled) return;
    const feedback = form.querySelector('[data-role="set-form-error"]');
    if (feedback) { feedback.hidden = true; feedback.textContent = ''; }
    const data = new FormData(form);
    if (!data.get('_csrf')) data.set('_csrf', csrf);
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
    try {
      const response = await fetch(endpointSave, {
        method: 'POST', body: data, credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || payload?.ok !== true) {
        formError(form, payload?.message || 'No se pudieron guardar los cambios.');
        return;
      }
      const record = payload.data;
      if (record && record.id) {
        const values = {
          name: record.name || '',
          category_name: record.category_name || '—',
          brand_name: record.brand_name || '—',
          items_count: String(record.items_count ?? 0)
        };
        root.querySelectorAll('[data-set-row="' + String(record.id) + '"]').forEach((row) => {
          Object.entries(values).forEach(([key, value]) => {
            row.querySelectorAll('[data-set-field="' + key + '"]').forEach((element) => {
              element.textContent = value;
            });
          });
        });
      }
      closeModal();
      notify('success', payload.message || 'Plantilla actualizada.');
    } catch (_) {
      formError(form, 'No se pudo conectar con el servidor.');
    } finally {
      if (button) { button.disabled = false; button.removeAttribute('aria-busy'); }
    }
  };

  const updateEditFragment = async (form, field, value) => {
    if (!endpointEdit) return;
    const data = new FormData(form);
    data.set(field, String(value));
    await loadHtml(endpointEdit, {
      method: 'POST', body: data,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
  };

  const handleClick = (event) => {
    const close = event.target.closest('[data-role="set-modal-close"], [data-role="set-modal-close-2"], [data-role="set-modal-backdrop"]');
    if (close) { closeModal(); return; }

    const view = event.target.closest('[data-role="set-view"]');
    if (view) { loadItems(view.dataset.setId); return; }

    const edit = event.target.closest('[data-role="set-edit"]');
    if (edit) {
      const menu = edit.closest('.av3-attributes__menu');
      if (menu) { menu.querySelector('summary')?.focus(); menu.open = false; }
      loadEdit(edit.dataset.setId); return;
    }

    const remove = event.target.closest('[data-role="set-delete"]');
    if (remove) {
      const menu = remove.closest('.av3-attributes__menu');
      if (menu) { menu.querySelector('summary')?.focus(); menu.open = false; }
      deleteSet(remove.dataset.setId); return;
    }

    const addAttr = event.target.closest('[data-role="set-add-attr"]');
    if (addAttr) {
      const form = addAttr.closest('[data-role="set-edit-form"]');
      const selectedId = form?.querySelector('[data-role="set-add-attr-select"]')?.value || '0';
      if (form && Number(selectedId) > 0) updateEditFragment(form, 'add_attribute_id', selectedId);
      return;
    }

    const addValues = event.target.closest('[data-role="set-add-values"]');
    if (addValues) {
      const form = addValues.closest('[data-role="set-edit-form"]');
      const id = addValues.dataset.attrId || '0';
      const input = form?.querySelector('[data-role="set-add-values-input"][data-attr-id="' + id + '"]');
      const csv = String(input?.value || '').trim();
      if (!csv) { notify('error', 'Escribí los valores que querés incorporar.'); return; }
      if (!window.confirm('Los valores nuevos se guardarán en el catálogo ahora, incluso si cancelás esta plantilla. ¿Continuar?')) return;
      if (form) {
        const data = new FormData(form);
        data.set('add_values_attribute_id', id);
        data.set('add_values_csv', csv);
        loadHtml(endpointEdit, {
          method: 'POST', body: data,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
      }
    }
  };
  root.addEventListener('click', handleClick);
  modal?.addEventListener('click', handleClick);

  modal?.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-role="set-edit-form"]');
    if (!form) return;
    event.preventDefault();
    saveEditForm(form);
  });

  modal?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeModal();
      return;
    }
    if (event.key !== 'Tab' || !dialog) return;
    const focusables = Array.from(dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), a[href], [tabindex="0"]'))
      .filter(element => element.getClientRects().length > 0);
    if (!focusables.length) { event.preventDefault(); dialog.focus(); return; }
    const first = focusables[0], last = focusables[focusables.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
}
