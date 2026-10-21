// checkout_steps.js
// Wizard estilo Mercado Libre (solo UI). Sin tocar funcionalidad de pagos.
// Requiere: app.js llama init().

export function init() {
  const stepSections = Array.from(document.querySelectorAll('[data-checkout-step]'));
  if (!stepSections.length) return;

  const stepperBtns = Array.from(document.querySelectorAll('[data-stepper-go]'));
  const badges = Array.from(document.querySelectorAll('[data-stepper-badge]'));

  const btnNext = Array.from(document.querySelectorAll('[data-checkout-next]'));
  const btnPrev = Array.from(document.querySelectorAll('[data-checkout-prev]'));

  const getSelectedAddressInput = () => document.querySelector('input[name="address_id"]:checked');

  const addressErr = document.getElementById('checkout-address-error');
  const methodErr = document.getElementById('checkout-method-error');
  const methodCards = Array.from(document.querySelectorAll('[data-payment-method]'));
  const selectedMethodInput = document.getElementById('checkout-selected-payment-method');

  const setStepCardEnabled = (step, enabled) => {
    let card = document.querySelector(`[data-stepper-card="${step}"]`);
    if (!card) return;

    const href = card.getAttribute('data-stepper-href') || '';
    const isAnchor = card.tagName.toLowerCase() === 'a';

    if (enabled) {
      if (isAnchor && href) card.setAttribute('href', href);
      card.removeAttribute('aria-disabled');
      card.removeAttribute('tabindex');
    } else {
      if (isAnchor) card.removeAttribute('href');
      card.setAttribute('aria-disabled', 'true');
      card.setAttribute('tabindex', '-1');
    }

    card.classList.toggle('is-disabled', !enabled);
    card.classList.toggle('is-enabled', enabled);
  };

  

const getPaymentSelectUrl = () => {
  const root = document.querySelector('[data-payment-select-url]');
  return root ? String(root.getAttribute('data-payment-select-url') || '') : '';
};

const getAddressSelectUrl = () => {
  const root = document.querySelector('[data-address-select-url]');
  return root ? String(root.getAttribute('data-address-select-url') || '') : '';
};

const getCsrf = () => {
  // Source of truth en el proyecto: la mayorÃ­a de forms usan _csrf.
  // Dejamos fallback a csrf por compatibilidad.
  const el = document.querySelector('input[name="_csrf"]') || document.querySelector('input[name="csrf"]');
  return el ? String(el.value || '') : '';
};

const persistPaymentMethod = async (method) => {
  const url = getPaymentSelectUrl();
  if (!url) return { ok: true }; // si no estÃ¡, no bloqueamos
  const csrf = getCsrf();
  if (!csrf) return { ok: false, error: 'Falta CSRF.' };

  const fd = new FormData();
  // verify_csrf_or_abort() suele leer _csrf o el header X-CSRF-Token.
  fd.append('_csrf', csrf);
  fd.append('method', method);

  try {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrf,
      },
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) return { ok: false, error: data.error || 'No se pudo guardar el mÃ©todo de pago.' };
    return { ok: true };
  } catch (_) {
    return { ok: false, error: 'Error de red al guardar el mÃ©todo de pago.' };
  }
};

const persistSelectedAddress = async (addressId) => {
  const url = getAddressSelectUrl();
  if (!url) return { ok: true };
  const csrf = getCsrf();
  if (!csrf) return { ok: false, error: 'Falta CSRF.' };

  try {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'fetch',
        'X-CSRF-Token': csrf,
      },
      body: JSON.stringify({ address_id: Number(addressId || 0) }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) return { ok: false, error: data.error || 'No se pudo actualizar la direcciÃ³n.' };
    return { ok: true, data };
  } catch (_) {
    return { ok: false, error: 'Error de red al actualizar la direcciÃ³n.' };
  }
};

const isCardMethod = (method) => {
  const value = String(method || '').trim();
  return value === 'tarjeta_credito' || value === 'tarjeta_debito';
};

const primaryCta = document.getElementById('checkout-primary-cta');
  const primaryCtaLabel = document.getElementById('checkout-primary-cta-label');
  const primaryHint = document.getElementById('checkout-primary-hint');

  
  // --- navegaciÃ³n server-driven (evita avanzar deslogueado y preserva ?step=) ---
  const getBaseUrl = () => {
    const meta = document.querySelector('meta[name="app-base-url"]');
    const base = meta ? String(meta.getAttribute('content') || '') : '/';
    return base.endsWith('/') ? base : (base + '/');
  };

  const buildStepUrl = (step) => {
    const s = Math.max(1, Math.min(4, parseInt(step, 10) || 1));
    // Canonical: step 1 sin query
    if (s === 1) return getBaseUrl() + 'checkout';
    return getBaseUrl() + 'checkout?step=' + encodeURIComponent(String(s));
  };

  const getUrlStep = () => {
    try {
      const u = new URL(window.location.href);
      const raw = u.searchParams.get('step');
      const n = raw ? parseInt(raw, 10) : NaN;
      if (Number.isFinite(n) && n >= 1 && n <= 4) return n;

      // Canonical: Step 1 no lleva ?step=, pero la URL /checkout debe mandar a 1
      const p = String(u.pathname || '').replace(/\/+$/, '');
      if (p.endsWith('/checkout')) return 1;
    } catch (_) {}
    return null;
  };

  const navigateToStep = (step) => {
    try { sessionStorage.setItem('checkout_step', String(step)); } catch (_) {}
    window.location.href = buildStepUrl(step);
  };

  const queueLoadingForStep = (step) => {
    const loading = window.checkoutLoading;
    if (!loading || typeof loading.queueNext !== 'function') return;

    if (step === 2) {
      loading.queueNext('shipping', {
        title: 'Preparando todo para tu compra',
        text: 'Estamos calculando las opciones de envio.',
        minMs: 2000,
      });
      return;
    }

    if (step === 4 && isCardMethod(getMethod())) {
      loading.queueNext('payment-brick', {
        title: 'Preparando todo para tu compra',
        text: 'Estamos cargando el formulario de pago.',
        minMs: 2000,
      });
    }
  };

const KEY_STEP = 'checkout_step';
  const KEY_ADDRESS = 'checkout_address_id';
  const KEY_METHOD = 'checkout_method';
  const KEY_CART_SIG = 'checkout_cart_sig';

// Hidrata mÃ©todo desde servidor (si existe) para mantener consistencia entre tabs/redirects
try {
  const root = document.querySelector('[data-checkout-selected-method]');
  const serverMethod = root ? String(root.getAttribute('data-checkout-selected-method') || '') : '';
  if (serverMethod) {
    sessionStorage.setItem(KEY_METHOD, serverMethod);
  } else {
    sessionStorage.removeItem(KEY_METHOD);
  }
} catch (_) {}



  // âœ… Reinicio del wizard cuando el carrito cambiÃ³ entre visitas.
  // Mantiene datos (direcciÃ³n / fiscal) pero vuelve visualmente al Paso 1.
  // TambiÃ©n limpia el mÃ©todo para forzar confirmaciÃ³n explÃ­cita.
  try {
    const top = document.querySelector('[data-checkout-top]');
    const currentSig = (top && top.getAttribute('data-checkout-cart-sig')) ? String(top.getAttribute('data-checkout-cart-sig')) : '';
    if (currentSig) {
      const prevSig = sessionStorage.getItem(KEY_CART_SIG) || '';
      if (prevSig !== currentSig) {
        sessionStorage.setItem(KEY_CART_SIG, currentSig);
        sessionStorage.setItem(KEY_STEP, '1');
        sessionStorage.removeItem(KEY_METHOD);
      }
    }
  } catch (_) {}

  // Auto-preparaciÃ³n del pago con tarjeta al entrar al Paso 4 (ConfirmaciÃ³n).
  // Evita el â€œbotÃ³n intermedioâ€ y deja al usuario directo en el formulario.
  let cardAutoPreparedOnce = false;
  const resetCardAutoPrep = () => { cardAutoPreparedOnce = false; };
  const autoPrepareCardIfNeeded = (step) => {
    if (step !== 4) return;
    if (!isCardMethod(getMethod())) return;
    if (!hasAddress()) return;

    const btn = document.getElementById('btn-mp-start');
    if (!btn) return;

    // Si ya estÃ¡ preparado (btn deshabilitado), no repetimos.
    if (btn.disabled) return;

    // Evitar doble click si el usuario navega ida/vuelta rÃ¡pidamente.
    if (cardAutoPreparedOnce) return;
    cardAutoPreparedOnce = true;

    try { btn.click(); } catch (_) {}
  };

  const getStep = () => {
    const fromUrl = getUrlStep();
    if (fromUrl) return fromUrl;
    const raw = sessionStorage.getItem(KEY_STEP);
    const n = raw ? parseInt(raw, 10) : 1;
    return Number.isFinite(n) && n >= 1 && n <= 4 ? n : 1;
  };

  const setStep = (step) => {
    stepSections.forEach((el) => {
      const s = parseInt(el.getAttribute('data-checkout-step') || '0', 10);
      el.classList.toggle('ui-is-hidden', s !== step);
    });

    // Stepper visual
    stepperBtns.forEach((b) => {
      const s = parseInt(b.getAttribute('data-stepper-go') || '0', 10);
      b.classList.toggle('is-active', s === step);
    });

    badges.forEach((bd) => {
      const s = parseInt(bd.getAttribute('data-stepper-badge') || '0', 10);
      // badge activo
      bd.classList.toggle('is-active', s === step);
      bd.classList.toggle('is-inactive', s !== step);
    });

    sessionStorage.setItem(KEY_STEP, String(step));
    clearInlineErrors();
    updatePrimaryCta();
    stepperBtns.forEach((btn) => {
      const s = parseInt(btn.getAttribute('data-stepper-go') || '0', 10);
      if (s > 0) setStepCardEnabled(s, s <= step);
    });

    // Si el usuario eligiÃ³ Tarjeta, al entrar a ConfirmaciÃ³n preparamos el Brick automÃ¡ticamente.
    try { autoPrepareCardIfNeeded(step); } catch (_) {}
    // Scroll al inicio del checkout
    try {
      // (UX) Sin autoscroll al abrir checkout
    } catch (_) {}
  };

  const clearInlineErrors = () => {
    if (methodErr) methodErr.classList.add('ui-is-hidden');
  };


  const getSelectedAddressMeta = () => {
    const el = getSelectedAddressInput();
    if (!el) return null;
    const meta = {
      id: String(el.value || '').trim(),
      phone: String(el.dataset.phone || '').trim(),
      city: String(el.dataset.city || '').trim(),
      state: String(el.dataset.state || '').trim(),
      zip: String(el.dataset.zip || '').trim(),
      editUrl: String(el.dataset.editUrl || '').trim(),
    };
    return meta;
  };

  const hasAddress = () => {
    // Si no hay radios de direccion, no bloqueamos (casos edge).
    const any = document.querySelector('input[name="address_id"]');
    if (!any) return true;

    const meta = getSelectedAddressMeta();
    if (!meta) return false;
    if (!meta.id || meta.id === '0') return false;

    return true;
  };


  const getMethod = () => {
    const m = sessionStorage.getItem(KEY_METHOD) || '';
    return m;
  };

  
const openAddressModal = () => {
  try {
    const meta = getSelectedAddressMeta();
    const hasMeta = !!(meta && meta.id && meta.id !== '0');
    const detail = hasMeta ? { mode: 'edit', meta } : { mode: 'create' };

    // Ruta feliz: API global del modal (siempre que el mÃ³dulo cargÃ³)
    if (typeof window !== 'undefined' && typeof window.checkoutOpenAddressModal === 'function') {
      window.checkoutOpenAddressModal(detail);
      return;
    }
    // Compat: evento
    document.dispatchEvent(new CustomEvent('checkout:openAddressModal', { detail }));
  } catch (_) {}
};

const showAddressError = (message) => {
    if (!addressErr) return;
    addressErr.textContent = String(message || 'Selecciona una direccion para continuar.');
    addressErr.classList.remove('ui-is-hidden');
  };

const clearPaymentSelection = () => {
    try { sessionStorage.removeItem(KEY_METHOD); } catch (_) {}
    if (selectedMethodInput) selectedMethodInput.value = '';
    methodCards.forEach((card) => {
      card.classList.remove('is-selected');
      card.setAttribute('aria-pressed', 'false');
    });
    if (methodErr) methodErr.classList.add('ui-is-hidden');
    updatePrimaryCta();
  };

const setMethod = (m) => {
    sessionStorage.setItem(KEY_METHOD, m);
    if (selectedMethodInput) selectedMethodInput.value = String(m || '');
    // Si vuelve a elegir Tarjeta, permitimos auto-preparaciÃ³n nuevamente.
    if (isCardMethod(m)) resetCardAutoPrep();
    // UI seleccion
    methodCards.forEach((card) => {
      const v = card.getAttribute('data-payment-method') || '';
      const active = v === m;
      card.classList.toggle('is-selected', active);
      card.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    if (methodErr) methodErr.classList.add('ui-is-hidden');
    updatePrimaryCta();

  };

  const requireAddressOrShow = () => {
    if (hasAddress()) return true;

    showAddressError('Selecciona una direccion para continuar.');

    // Checkout cerrado: volvemos al paso 1 y abrimos directamente el modal
    setStep(1);

    // Salimos del tick de render del setStep para evitar efectos de timing de DOM
    setTimeout(() => {
      openAddressModal();
    }, 0);

    return false;
  };

  const ensureAddressSelection = async () => {
    if (!requireAddressOrShow()) return false;

    const meta = getSelectedAddressMeta();
    if (!meta || !meta.id || meta.id === '0') {
      showAddressError('Selecciona una direccion valida.');
      return false;
    }

    const saved = await persistSelectedAddress(meta.id);
    if (!saved.ok) {
      showAddressError(saved.error || 'No se pudo actualizar la direccion.');
      return false;
    }

    if (addressErr) addressErr.classList.add('ui-is-hidden');
    return true;
  };


  const requireMethodOrShow = () => {
    if (getMethod()) return true;
    if (methodErr) methodErr.classList.remove('ui-is-hidden');
    setStep(2);
    return false;
  };

  const updatePrimaryCta = () => {
    // El CTA primario superior queda en modo "compatibilidad".
    // En el paso 3, cada mÃ©todo tiene su propio botÃ³n dentro de su secciÃ³n,
    // asÃ­ evitamos duplicados y mantenemos el checkout â€œcerradoâ€.
    if (!primaryCta || !primaryCtaLabel) return;

    const step = getStep();
    const method = getMethod();

    // Por defecto, oculto.
    primaryCta.classList.add('ui-is-hidden');
    if (primaryHint) primaryHint.classList.add('ui-is-hidden');
    primaryCta.onclick = null;

    if (step !== 3) return;

    // Si no hay mÃ©todo, no mostramos nada extra.
    if (!method) return;

    // (Opcional) PodrÃ­amos mostrar un hint arriba, pero hoy lo dejamos dentro del cuadro del mÃ©todo.
  };

  const applySelectedAddressUI = () => {
    Array.from(document.querySelectorAll('input[name="address_id"]')).forEach((radio) => {
      const card = radio.closest('.checkout-address-card');
      if (card) card.classList.toggle('is-selected', !!radio.checked);
    });
  };

  const persistAddressFromDOM = () => {
    const checked = document.querySelector('input[name="address_id"]:checked');
    if (checked && checked.value) {
      try { sessionStorage.setItem(KEY_ADDRESS, String(checked.value)); } catch (_) {}
    }
  };

  const handleAddressSelectionChange = async (radio) => {
    if (!radio) return;
    persistAddressFromDOM();
    applySelectedAddressUI();
    if (addressErr) addressErr.classList.add('ui-is-hidden');
    resetCardAutoPrep();
    clearPaymentSelection();
    try { sessionStorage.setItem(KEY_STEP, '1'); } catch (_) {}
    setStep(1);
    const saved = await persistSelectedAddress(radio.value || '');
    if (!saved.ok) {
      if (methodErr) {
        methodErr.textContent = saved.error || 'No se pudo actualizar la direccion.';
      }
      showAddressError(saved.error || 'No se pudo actualizar la direccion.');
    }
  };

  const handleNextAction = async (btn) => {
    const target = parseInt(btn.getAttribute('data-checkout-next') || '2', 10);
    if (target === 2) {
      if (await ensureAddressSelection()) {
        queueLoadingForStep(2);
        navigateToStep(2);
      }
      return;
    }
    if (target === 3) {
      if (await ensureAddressSelection() && requireMethodOrShow()) navigateToStep(3);
      return;
    }
    if (target === 4) {
      if (requireAddressOrShow() && requireMethodOrShow()) {
        const saved = await persistPaymentMethod(getMethod());
        if (!saved.ok) {
          if (methodErr) {
            methodErr.textContent = saved.error || 'No se pudo guardar el metodo de pago.';
            methodErr.classList.remove('ui-is-hidden');
          }
          return;
        }
        queueLoadingForStep(4);
        navigateToStep(4);
      }
      return;
    }

    navigateToStep(target);
  };

  const handleAddressCardClick = (card, ev) => {
    if (!card) return;
    const editBtn = ev && ev.target && typeof ev.target.closest === 'function'
      ? ev.target.closest('[data-action="checkout-edit-address"]')
      : null;
    if (editBtn) return;

    const radio = card.querySelector('input[name="address_id"]');
    if (!radio) return;
    if (!radio.checked) {
      radio.checked = true;
    }
    radio.dispatchEvent(new Event('change', { bubbles: true }));
  };

  if (!window.__checkoutStepsBound) {
    window.__checkoutStepsBound = true;

    // Wire stepper click
    stepperBtns.forEach((btn) => {
      btn.addEventListener('click', async () => {
        const target = parseInt(btn.getAttribute('data-stepper-go') || '1', 10);
        if (target === 1) { navigateToStep(1); return; }
        if (target === 2) { if (await ensureAddressSelection()) navigateToStep(2); return; }
        if (target === 3) { if (await ensureAddressSelection() && requireMethodOrShow()) navigateToStep(3); return; }
      });
    });

    btnPrev.forEach((btn) => {
      btn.addEventListener('click', async () => {
        const target = parseInt(btn.getAttribute('data-checkout-prev') || '1', 10);
        navigateToStep(target);
      });
    });

    // Payment method cards
    methodCards.forEach((card) => {
      card.addEventListener('click', async () => {
        const m = card.getAttribute('data-payment-method') || '';
        if (!m) return;

        setMethod(m);

        const saved = await persistPaymentMethod(m);
        if (!saved.ok && methodErr) {
          methodErr.textContent = saved.error || 'No se pudo guardar el metodo de pago.';
          methodErr.classList.remove('ui-is-hidden');
        }
      });
    });

    document.addEventListener('click', async (ev) => {
      const target = ev && ev.target && typeof ev.target.closest === 'function' ? ev.target.closest('[data-checkout-next]') : null;
      if (target) {
        ev.preventDefault();
        ev.stopPropagation();
        await handleNextAction(target);
        return;
      }

      const card = ev && ev.target && typeof ev.target.closest === 'function' ? ev.target.closest('.checkout-address-card') : null;
      if (!card) return;
      if (!card.querySelector('input[name="address_id"]')) return;
      const blocked = ev.target && typeof ev.target.closest === 'function'
        ? ev.target.closest('[data-action="checkout-edit-address"], [data-action="checkout-create-address"]')
        : null;
      if (blocked) return;
      ev.preventDefault();
      ev.stopPropagation();
      handleAddressCardClick(card, ev);
    }, true);

    document.addEventListener('change', (ev) => {
      const radio = ev && ev.target && ev.target.matches && ev.target.matches('input[name="address_id"]')
        ? ev.target
        : null;
      if (!radio) return;
      handleAddressSelectionChange(radio).catch(() => {});
    });
  }

  // Persistir dirección seleccionada (sirve cuando el paso 1 no está renderizado al volver)
  persistAddressFromDOM();
  applySelectedAddressUI();

  // Si hay query transferencia=1, forzamos paso 3 + mÃ©todo transferencia
  try {
    const qs = new URLSearchParams(window.location.search);
    const stepQs = parseInt(qs.get('step') || '', 10);
    if (stepQs >= 1 && stepQs <= 4) {
      try { sessionStorage.setItem(KEY_STEP, String(stepQs)); } catch (_) {}
    }
    if (qs.get('transferencia') === '1') {
      setMethod('transferencia');
      sessionStorage.setItem(KEY_STEP, '3');
    }
  } catch (_) {}

  // Init visual state
  if (!getMethod()) {
    const selectedCard = methodCards.find((card) => card.getAttribute('aria-pressed') === 'true');
    const selectedCardMethod = selectedCard ? (selectedCard.getAttribute('data-payment-method') || '') : '';
    if (selectedCardMethod) {
      setMethod(selectedCardMethod);
      persistPaymentMethod(selectedCardMethod).catch(() => {});
    }

    if (getMethod()) {
      // no-op: ya sincronizado desde selecciÃ³n visual.
    } else {
    // Si la vista marca un mÃ©todo por defecto, lo hacemos real (para que valide)
    const def = methodCards.find((c) => c.getAttribute('data-payment-default') === '1');
    const m = def?.getAttribute('data-payment-method') || '';
    if (m) {
      setMethod(m);
      // Si el mÃ©todo se auto-selecciona por default, lo persistimos para habilitar Step 4 por URL.
      persistPaymentMethod(m).catch(() => {});
    }
    }
  } else {
    setMethod(getMethod());
  }

  // Si el paso guardado no es vÃ¡lido, volvemos a 1
  const initial = getStep();
  if (initial === 2 && !hasAddress()) {
    sessionStorage.setItem(KEY_STEP, '1');
    setStep(1);
  } else if (initial === 3 && (!hasAddress() || !getMethod())) {
    persistAddressFromDOM();
    sessionStorage.setItem(KEY_STEP, hasAddress() ? '2' : '1');
    setStep(hasAddress() ? 2 : 1);
  } else if (initial === 4 && (!hasAddress() || !getMethod())) {
    persistAddressFromDOM();
    sessionStorage.setItem(KEY_STEP, hasAddress() ? '3' : '1');
    setStep(hasAddress() ? 3 : 1);
  } else {
    setStep(initial);
  }
}

