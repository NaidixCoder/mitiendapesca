// Admin → Productos → Wizard global
// - Cambios sin guardar al cambiar de tab
// - Busy state en guardado
// - "Guardar y continuar" para creación (id=0) via JSON

export function init() {
  const root = document.getElementById('product-tabs-root');
  const form = document.getElementById('product-form');
  if (!root || !form) return;

  const modal = document.getElementById('product-unsaved-modal');
  const msgEl = modal ? modal.querySelector('[data-unsaved-message]') : null;
  const btnClose = modal ? modal.querySelector('[data-unsaved-close]') : null;
  const btnCancel = modal ? modal.querySelector('[data-unsaved-cancel]') : null;
  const btnDiscard = modal ? modal.querySelector('[data-unsaved-discard]') : null;
  const btnSave = modal ? modal.querySelector('[data-unsaved-save]') : null;

  const base = document.querySelector('meta[name="app-base-url"]')?.getAttribute('content') || '';

  // Base path (soporta subcarpetas). Si `base` es URL absoluta, extrae el pathname.
  const basePath = (() => {
    if (!base) return '';
    try {
      const u = new URL(base, window.location.origin);
      let p = u.pathname || '';
      if (p === '/') return '';
      return p.replace(/\/$/, '');
    } catch (_) {
      if (base[0] === '/') return base.replace(/\/$/, '');
      return '';
    }
  })();
  const appBase = basePath;

  const getId = () => {
    const v = form.querySelector('input[name="id"]')?.value;
    const id = Number.parseInt(String(v || '0'), 10);
    return Number.isFinite(id) ? id : 0;
  };

  const nextInput = form.querySelector('input[name="next"]');
  const getActiveTab = () => {
    try {
      const hidden = document.getElementById('product-active-tab');
      const hv = String(hidden?.value || '').trim();
      if (hv) return hv;
      const activeBtn = root.querySelector('#tabs-nav .tab-btn.is-active[data-tab]');
      return String(activeBtn?.getAttribute('data-tab') || '').trim();
    } catch (_) {
      return '';
    }
  };

// --- Badges del wizard (puntos en tabs) ---
const tabsNav = document.getElementById('tabs-nav');

const debounce = (fn, ms = 200) => {
  let t = null;
  return (...args) => {
    if (t) clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
};

const refreshWizardBadges = debounce(async () => {
  const id = getId();
  if (!tabsNav || !id) return;
  // Evita refresh global durante mutaciones AJAX de variantes.
  if (document.body?.dataset?.tpVariantsBusy === '1') return;
  // En tab Variantes, el refresh de badges no aporta al flujo inmediato y agrega ruido de red/repintado.
  if (getActiveTab() === 'variantes') return;

  try {
    const url = `${appBase}/admin/products/wizard-status.json?id=${encodeURIComponent(String(id))}&_t=${Date.now()}`;
    const res = await fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
      cache: 'no-store',
    });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || data.ok !== true) return;

    const tabs = data.status && data.status.tabs ? data.status.tabs : null;
    if (!tabs) return;

    applyWizardBadges(tabs);
  } catch (_) {
    // silencioso
  }
}, 250);

const findBadgeEl = (key) => {
  // Preferimos el data attribute (lo agrega el server). Fallback al primer puntito que exista.
  const byAttr = tabsNav.querySelector(`[data-wiz-badge="${key}"]`);
  if (byAttr) return byAttr;
  const btn = tabsNav.querySelector(`button[data-tab="${key}"]`);
  if (!btn) return null;
  return btn.querySelector('span.av3-radius-full') || null;
};

const setBadge = (key, ok) => {
  const el = findBadgeEl(key);
  if (!el) return;

  el.classList.remove('av3-bg-emerald-500', 'av3-bg-amber-500');
  el.classList.add(ok ? 'av3-bg-emerald-500' : 'av3-bg-amber-500');
  el.setAttribute('title', ok ? 'Completo' : 'Pendiente');
  el.dataset.wizState = ok ? 'ok' : 'pending';
};

const applyWizardBadges = (tabs) => {
  Object.entries(tabs).forEach(([key, v]) => {
    if (!v || typeof v.ok !== 'boolean') return;
    setBadge(key, v.ok);
  });
};

// API global + eventos (para módulos AJAX como Imágenes / Variantes)
window.__TP_WIZARD = window.__TP_WIZARD || {};
window.__TP_WIZARD.refresh = refreshWizardBadges;
document.addEventListener('tp:wizard:refresh', () => refreshWizardBadges());
  // Inicial
  setTimeout(() => refreshWizardBadges(), 80);



  let isDirty = false;
  let pendingTab = null;

  function setDirty(v) {
    isDirty = !!v;
    try {
      // Pequeña pista visual (sin ensuciar la UI): cambia el título
      if (isDirty) {
        if (!document.title.startsWith('● ')) document.title = `● ${document.title}`;
      } else {
        document.title = document.title.replace(/^●\s+/, '');
      }
    } catch (_) {}
  }

  // Cambios dentro del form
  const markDirty = () => setDirty(true);
  form.addEventListener('input', markDirty, true);
  form.addEventListener('change', markDirty, true);

  // Busy state al submit
  form.addEventListener('submit', () => {
    // Nota: el submit normal hace redirect; igual bloqueamos para evitar doble click
    const buttons = Array.from(document.querySelectorAll('button[form="product-form"], #product-form button[type="submit"]'));
    buttons.forEach(b => {
      b.disabled = true;
      b.classList?.add?.('av3-opacity-60');
    });
    setDirty(false);
  });

  // beforeunload
  window.addEventListener('beforeunload', (e) => {
    if (!isDirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  function openModal(message) {
    if (!modal) return;
    if (msgEl && message) msgEl.textContent = message;
    modal.classList.remove('av3-hidden');
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('av3-hidden');
  }

  // API para otros módulos (ej: Variantes) que necesiten forzar el modal de "cambios sin guardar"
  // con un guardado custom (AJAX) antes de continuar.
  root.__openUnsavedModal = (tab, opts = {}) => {
    pendingTab = tab;
    if (msgEl && opts.message) msgEl.textContent = String(opts.message);
    root.__unsavedCustomSave = (typeof opts.onSave === 'function') ? opts.onSave : null;
    openModal();
  };

  function tabLabel(tab) {
    const b = root.querySelector(`.tab-btn[data-tab="${tab}"]`);
    return (b?.textContent || tab || '').replace(/\s+/g, ' ').trim();
  }

  // Interceptar cambios de tab
  root.addEventListener('product:wizard:before-tab-change', (e) => {
    const detail = e.detail || {};
    const from = String(detail.from || '').trim();
    const to = String(detail.to || '').trim();
    if (!to || from === to) return;
    if (!isDirty) return;

    e.preventDefault();
    pendingTab = to;
    openModal(`Tenés cambios sin guardar en “${tabLabel(from)}”. ¿Qué querés hacer?`);
  });

  // Modal actions
  const onClose = () => {
    pendingTab = null;
    root.__unsavedCustomSave = null;
    closeModal();
  };

  btnClose?.addEventListener('click', onClose);
  btnCancel?.addEventListener('click', onClose);

  btnDiscard?.addEventListener('click', () => {
    const t = pendingTab;
    setDirty(false);
    pendingTab = null;
    root.__unsavedCustomSave = null;
    closeModal();
    if (t && typeof root.__showProductTab === 'function') {
      root.__showProductTab(t);
    }
  });

  async function saveAndContinue(tab) {
    const targetTab = tab;
    if (!targetTab) return;

    // Preferimos submit normal si ya existe id
    const id = getId();
    if (id > 0) {
      if (nextInput) nextInput.value = `${basePath}/admin/products/edit?id=${id}&tab=${encodeURIComponent(targetTab)}`;
      // submit normal
      form.requestSubmit?.();
      if (!form.requestSubmit) form.submit();
      return;
    }

    // Create via JSON (id=0)
    try {
      btnSave && (btnSave.disabled = true);
      btnDiscard && (btnDiscard.disabled = true);
      btnCancel && (btnCancel.disabled = true);

      // Para creación: no podemos interpolar id en "next" todavía; redirigimos manualmente.
      if (nextInput) nextInput.value = `${basePath}/admin/products`;

      const fd = new FormData(form);
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'fetch'
        },
        body: fd,
        credentials: 'same-origin'
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data || data.ok !== true || !data.id) {
        const msg = (data && data.message) ? String(data.message) : 'No se pudo guardar el producto.';
        if (typeof window.toast === 'function') window.toast('error', msg);
        btnSave && (btnSave.disabled = false);
        btnDiscard && (btnDiscard.disabled = false);
        btnCancel && (btnCancel.disabled = false);
        return;
      }

      // OK
      setDirty(false);
      window.location.href = `${appBase}/admin/products/edit?id=${encodeURIComponent(String(data.id))}&tab=${encodeURIComponent(targetTab)}`;
    } catch (err) {
      if (typeof window.toast === 'function') window.toast('error', 'No se pudo guardar el producto.');
      btnSave && (btnSave.disabled = false);
      btnDiscard && (btnDiscard.disabled = false);
      btnCancel && (btnCancel.disabled = false);
    }
  }

  btnSave?.addEventListener('click', async () => {
    const t = pendingTab;
    if (!t) { closeModal(); return; }

    // Si otro módulo (ej: Variantes) necesita guardar con su propia lógica,
    // puede setear un handler temporal en root.__unsavedCustomSave.
    const custom = root.__unsavedCustomSave;
    if (typeof custom === 'function') {
      try {
        btnSave && (btnSave.disabled = true);
        btnDiscard && (btnDiscard.disabled = true);
        btnCancel && (btnCancel.disabled = true);

        const ok = await custom(t);
        if (ok === false) {
          // El handler decidió no continuar (mantener modal abierto)
          btnSave && (btnSave.disabled = false);
          btnDiscard && (btnDiscard.disabled = false);
          btnCancel && (btnCancel.disabled = false);
          return;
        }

        // Continuar a la pestaña target
        pendingTab = null;
        root.__unsavedCustomSave = null;
        closeModal();
        if (typeof root.__showProductTab === 'function') root.__showProductTab(t);
        return;
      } catch (err) {
        console.error(err);
        // Mantener modal abierto para que el usuario decida.
        btnSave && (btnSave.disabled = false);
        btnDiscard && (btnDiscard.disabled = false);
        btnCancel && (btnCancel.disabled = false);
        return;
      }
    }

    // Default (form submit)
    pendingTab = null;
    closeModal();
    saveAndContinue(t);
  });
}
