// Mercado Pago - Card Payment Brick (pago embebido)
// Reglas del proyecto: sin JS inline, todo por modulos.
console.log('MP_BRICK_LOADED v999');

function qs(sel, root = document) { return root.querySelector(sel); }

function getMeta(name) {
  const el = document.querySelector(`meta[name="${name}"]`);
  return el ? (el.getAttribute('content') || '') : '';
}

function setStatus(el, msg, type = 'info') {
  if (!el) return;
  el.textContent = msg || '';
  el.className = 'checkout-card__status';
  el.classList.add(type === 'error' ? 'is-error' : (type === 'ok' ? 'is-ok' : 'is-info'));
}

function clearStatus(el) {
  if (!el) return;
  el.textContent = '';
  el.className = 'checkout-card__status ui-is-hidden';
}

function showToast(type, message) {
  if (!message) return;
  try {
    if (typeof window !== 'undefined' && typeof window.toast === 'function') {
      window.toast(type, message);
    }
  } catch (_) {}
}


function setBusy(btn, busy) {
  if (!btn) return;
  if (busy) {
    btn.disabled = true;
    btn.dataset._prevText = btn.textContent;
    btn.textContent = 'Procesando...';
    btn.classList.add('is-busy');
  } else {
    btn.disabled = false;
    btn.textContent = btn.dataset._prevText || btn.textContent;
    btn.classList.remove('is-busy');
  }
}

function showOverlay(title, text) {
  const ov = document.getElementById('mp-overlay');
  if (!ov) return;
  const t = document.getElementById('mp-overlay-title');
  const p = document.getElementById('mp-overlay-text');
  if (t && title) t.textContent = title;
  if (p && text) p.textContent = text;
  ov.classList.remove('ui-is-hidden');
}

function hideOverlay() {
  const ov = document.getElementById('mp-overlay');
  if (!ov) return;
  ov.classList.add('ui-is-hidden');
}

// ---------- JSON SAFE (evita "primer submit fantasma") ----------
function isPlainObject(v) {
  return Object.prototype.toString.call(v) === '[object Object]';
}

// Convierte cualquier valor a algo serializable (sin circular refs / DOM / funciones)
function safeJsonValue(value, seen, depth) {
  if (value == null) return null;

  const t = typeof value;
  if (t === 'string' || t === 'number' || t === 'boolean') return value;

  // Evitar BigInt (revienta JSON.stringify)
  if (t === 'bigint') return Number(value);

  // Ignorar funciones/symbols
  if (t === 'function' || t === 'symbol' || t === 'undefined') return undefined;

  // Evitar nodos DOM / window
  try {
    if (typeof Node !== 'undefined' && value instanceof Node) return undefined;
    if (typeof Window !== 'undefined' && value instanceof Window) return undefined;
  } catch (_) {}

  // Control de profundidad
  if (depth <= 0) return undefined;

  // Circular refs
  if (seen.has(value)) return undefined;
  seen.add(value);

  // Arrays
  if (Array.isArray(value)) {
    const out = [];
    for (const item of value) {
      const v = safeJsonValue(item, seen, depth - 1);
      if (v !== undefined) out.push(v);
    }
    return out;
  }

  // Objetos
  if (isPlainObject(value)) {
    const out = {};
    for (const k of Object.keys(value)) {
      const v = safeJsonValue(value[k], seen, depth - 1);
      if (v !== undefined) out[k] = v;
    }
    return out;
  }

  // Otros objetos (Date, etc.)
  if (value instanceof Date) return value.toISOString();

  // Fallback: intentar toString si existe
  try {
    const s = String(value);
    return s;
  } catch (_) {
    return undefined;
  }
}

function safeStringify(obj, maxDepth = 6) {
  const seen = new WeakSet();
  const safe = safeJsonValue(obj, seen, maxDepth);
  return JSON.stringify(safe ?? {});
}

// ---------- Overlay UX PRO (timing/ownership) ----------
const OVERLAY_MIN_VISIBLE_MS = 1400;
const OVERLAY_MIN_AFTER_OK_MS = 450;

function createOverlayController({ statusEl, btnStart }) {
  let startedAt = 0;
  let started = false;

  function start() {
    if (started) return;
    started = true;
    startedAt = Date.now();
    try {
      try { window.checkoutSetProcessing && window.checkoutSetProcessing(true); } catch (_) {}
      showOverlay('Procesando pago', 'Estamos validando el pago. No cierres esta ventana...');
      setStatus(statusEl, 'Procesando pago...', 'info');
      setBusy(btnStart, true);
    } catch (_) {}
  }

  function approved() {
    try {
      showOverlay('Pago aprobado', 'Confirmando tu pedido. Esto puede tardar unos segundos...');
      setStatus(statusEl, 'Pago aprobado. Confirmando tu pedido...', 'ok');
      showToast('ok', 'Pago aprobado. Estamos confirmando tu pedido...');
    } catch (_) {}
  }

  function fail() {
    try { window.checkoutSetProcessing && window.checkoutSetProcessing(false); } catch (_) {}
    try { hideOverlay(); setBusy(btnStart, false); } catch (_) {}
  }

  function msSinceStart() { return startedAt ? (Date.now() - startedAt) : 0; }

  function delayForRedirect() {
    const totalRemain = Math.max(0, OVERLAY_MIN_VISIBLE_MS - msSinceStart());
    return Math.max(totalRemain, OVERLAY_MIN_AFTER_OK_MS);
  }

  return { start, approved, fail, delayForRedirect };
}

async function postJson(url, payload, csrf) {
  // Importante: permitir body pre-stringified si viene como string
  const body = (typeof payload === 'string') ? payload : JSON.stringify(payload || {});

  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(csrf ? { 'X-CSRF-Token': csrf } : {}),
    },
    credentials: 'same-origin',
    body,
  });

  let data = null;
  try { data = await res.json(); } catch (_) { /* ignore */ }

  if (res.status === 401 && data && (data.code === 'auth_required' || data.error === 'unauthorized') && data.login_url) {
    try { showToast('info', data.message || 'Tu sesion expiro. Ingresa de nuevo para continuar.'); } catch (_) {}
    window.location.href = data.login_url;
    return new Promise(() => {});
  }

  if (!res.ok) {
    const msg = (data && (data.error || data.message)) ? (data.error || data.message) : 'Error al procesar la solicitud.';
    const err = new Error(msg);
    err.responseStatus = res.status;
    err.responseData = data || {};
    throw err;
  }
  return data || {};
}

let brickController = null;
let brickPrepared = false;
let brickFailureReported = false;

// Estado para reintentos de montaje (evita intermitencias por BFCache / SDK tardio)
let mpState = null;
let mountTimer = null;
let mountInFlight = false;
let mountAttempts = 0;
let paymentUiLocked = false;

function finishCheckoutLoading(kind) {
  try {
    const loading = window.checkoutLoading;
    if (loading && typeof loading.finish === 'function') {
      loading.finish(kind);
    }
  } catch (_) {}
}

function failCheckoutLoading(kind) {
  try {
    const loading = window.checkoutLoading;
    if (loading && typeof loading.fail === 'function') {
      loading.fail(kind);
    }
  } catch (_) {}
}

async function reportBrickFailure(configEl, csrf, reason) {
  if (brickFailureReported || !configEl) return;
  const url = String(configEl.dataset.mpBrickFailedUrl || '').trim();
  if (!url) return;

  brickFailureReported = true;

  try {
    await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-Token': csrf } : {}),
      },
      body: JSON.stringify({
        reason: String(reason || 'brick_failed'),
        step: getCheckoutStep(),
        method: getCheckoutMethod(),
        address_id: Number(getSelectedAddressIdSafe() || 0),
        url: window.location.href,
      }),
      keepalive: true,
    });
  } catch (_) {}
}

function normalizeBrickContainer(container) {
  if (!container) return;

  try {
    container.style.width = '100%';
    container.style.maxWidth = '100%';
    container.style.minWidth = '0';
    container.style.boxSizing = 'border-box';
  } catch (_) {}

  const first = container.firstElementChild;
  if (!first) return;

  try {
    first.style.width = '100%';
    first.style.maxWidth = '100%';
    first.style.minWidth = '0';
    first.style.boxSizing = 'border-box';
  } catch (_) {}

  const second = first.firstElementChild;
  if (!second) return;

  try {
    second.style.width = '100%';
    second.style.maxWidth = '100%';
    second.style.minWidth = '0';
    second.style.boxSizing = 'border-box';
  } catch (_) {}
}

function getCheckoutStep() {
  try {
    const u = new URL(window.location.href);
    const raw = u.searchParams.get('step');
    const n = raw ? parseInt(raw, 10) : NaN;
    // Checkout ahora es de 4 pasos (Direccion -> Envio -> Pago -> Confirmacion)
    if (Number.isFinite(n) && n >= 1 && n <= 4) return n;
  } catch (_) {}
  try { return parseInt(sessionStorage.getItem('checkout_step') || '1', 10) || 1; } catch (_) { return 1; }
}
function getCheckoutMethod() {
  try { return (sessionStorage.getItem('checkout_method') || '').trim(); } catch (_) { return ''; }
}

function isCardCheckoutMethod(method) {
  const value = String(method || '').trim();
  return value === 'tarjeta_credito' || value === 'tarjeta_debito';
}

function getSelectedAddressIdSafe() {
  // 1) Radio seleccionado (si el Paso 1 esta renderizado)
  try {
    const r = document.querySelector('input[name="address_id"]:checked');
    if (r && r.value) return parseInt(r.value, 10) || null;
  } catch (_) {}

  // 2) Persistido por el wizard
  try {
    const v = sessionStorage.getItem('checkout_address_id') || '';
    const id = parseInt(v, 10);
    return Number.isFinite(id) && id > 0 ? id : null;
  } catch (_) {}

  return null;
}

function resolveCheckoutSelectedMethod(submitData, safeFormData) {
  const byBrick = (submitData && submitData.selectedPaymentMethod) ? String(submitData.selectedPaymentMethod).trim() : '';
  if (byBrick) return byBrick;

  const byCardForm = (safeFormData && safeFormData.payment_method_id) ? String(safeFormData.payment_method_id).trim() : '';
  if (byCardForm) return byCardForm;

  try {
    const fromInput = document.querySelector('input[name="selectedPaymentMethod"]');
    const val = fromInput ? String(fromInput.value || '').trim() : '';
    if (val) return val;
  } catch (_) {}

  try {
    const pressed = document.querySelector('[data-payment-method][aria-pressed="true"]');
    const val = pressed ? String(pressed.getAttribute('data-payment-method') || '').trim() : '';
    if (val) return val;
  } catch (_) {}

  try {
    const val = String(sessionStorage.getItem('checkout_method') || '').trim();
    if (val) return val;
  } catch (_) {}

  try {
    const root = document.querySelector('[data-checkout-selected-method]');
    const val = root ? String(root.getAttribute('data-checkout-selected-method') || '').trim() : '';
    if (val) return val;
  } catch (_) {}

  return '';
}

function shouldBeMounted() {
  const step = getCheckoutStep();
  const method = getCheckoutMethod();
  // El Brick de tarjeta vive en el Paso 4 (Confirmacion)
  if (step !== 4) return false;
  if (!isCardCheckoutMethod(method)) return false;
  return !!getSelectedAddressIdSafe();
}

function isBrickVisiblyMounted() {
  const wrap = mpState && mpState.brickWrap ? mpState.brickWrap : document.getElementById('mp-card-brick');
  if (!wrap) return false;
  return (wrap.children && wrap.children.length > 0);
}

function replaceBrickContainer() {
  const wrap = mpState && mpState.brickWrap ? mpState.brickWrap : document.getElementById('mp-card-brick');
  if (!wrap) return null;
  const fresh = wrap.cloneNode(false);
  wrap.replaceWith(fresh);
  if (mpState) mpState.brickWrap = fresh;
  return fresh;
}

function clearMountTimer() {
  if (mountTimer) {
    clearTimeout(mountTimer);
    mountTimer = null;
  }
}

function scheduleMount(reason, opts = {}) {
  if (!mpState) return;

  clearMountTimer();
  mountAttempts = 0;

  const manual = !!opts.manual;
  const forceReset = !!opts.forceReset;

  const tick = async () => {
    if (!mpState) return;
    if (!shouldBeMounted()) return;

    // Si ya esta montado, terminamos.
    if (brickPrepared && brickController && isBrickVisiblyMounted()) return;

    if (mountInFlight) {
      // Reintento suave si hay un intento en curso.
      mountTimer = setTimeout(tick, 250);
      return;
    }

    mountAttempts += 1;
    mountInFlight = true;

    try {
      // Si el SDK todavia no esta, esperamos (esto explica el "Alt+Tab y aparece").
      if (!window.MercadoPago) {
        throw new Error('__SDK_NOT_READY__');
      }

      // Para evitar iframes "zombie" (BFCache), recreamos el contenedor antes del mount.
      if (forceReset || mountAttempts <= 2) {
        try { await resetBrick(); } catch (_) {}
        try { replaceBrickContainer(); } catch (_) {}
      }

      const addressId = getSelectedAddressIdSafe();
      if (!addressId) return;

      // Hacemos el mount directo (no dependemos de clicks ni timing de listeners).
      await mountBrick({
        amount: mpState.amount,
        payerEmail: mpState.payerEmail,
        csrf: mpState.csrf,
        paymentUrl: mpState.paymentUrl,
        stockPreflightUrl: mpState.stockPreflightUrl,
        addressId
      });

      brickPrepared = true;

      // Si existe boton, lo deshabilitamos (evita double-mount manual).
      if (mpState.btn) mpState.btn.disabled = true;

      // Ocultamos fallback.
      if (mpState.fallback) mpState.fallback.classList.add('ui-is-hidden');

      // No mostramos toasts ni "Actualizado": aca solo queremos que aparezca el formulario.
      return;
    } catch (e) {
      const msg = (e && e.message) ? e.message : '';

      // SDK todavia no esta listo: reintentar sin mostrar error al usuario.
      if (msg === '__SDK_NOT_READY__' || /sdk de mercado pago/i.test(msg) || /No se cargo el SDK/i.test(msg)) {
        // seguir
      } else {
        // Error real: mostramos texto y fallback si el usuario lo disparo manualmente o si agotamos reintentos.
        if (manual || mountAttempts >= 10) {
          setStatus(mpState.statusEl, 'No se pudo cargar el pago con tarjeta. Proba recargar la pagina o elegi Transferencia.', 'error');
          if (mpState.fallback) mpState.fallback.classList.remove('ui-is-hidden');
          reportBrickFailure(mpState.configEl, mpState.csrf, 'brick_mount_failed');
          failCheckoutLoading('payment-brick');
        }
      }
    } finally {
      mountInFlight = false;
    }

    if (mountAttempts < 40) {
      mountTimer = setTimeout(tick, 250);
    } else {
      // Luego de varios segundos, dejamos un fallback visible (sin bloquear checkout).
      if (mpState.fallback) mpState.fallback.classList.remove('ui-is-hidden');
      reportBrickFailure(mpState.configEl, mpState.csrf, 'brick_mount_timeout');
      failCheckoutLoading('payment-brick');
    }
  };

  // Disparar ya
  mountTimer = setTimeout(tick, 0);
}

async function mountBrick({ amount, payerEmail, csrf, paymentUrl, stockPreflightUrl, addressId }) {
  if (!window.MercadoPago) {
    throw new Error('No se cargo el SDK de Mercado Pago.');
  }

  const publicKey = getMeta('mp-public-key');
  if (!publicKey) {
    throw new Error('MP_PUBLIC_KEY no configurada.');
  }

  const containerId = 'mp-card-brick';
  const container = document.getElementById(containerId);
  if (!container) {
    throw new Error('No se encontro el contenedor del pago.');
  }

  // Paso 2 visible recien cuando el usuario prepara el pago
  try { container.classList.remove('ui-is-hidden'); } catch (_) {}
  try { container.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (_) {}

  const statusEl = qs('#mp-status');
  const btnStart = qs('#btn-mp-start');
  const overlayCtrl = createOverlayController({ statusEl, btnStart });

  if (brickController && typeof brickController.unmount === 'function') {
    try { await brickController.unmount(); } catch (_) {}
  }
  container.innerHTML = '';

  const mp = new window.MercadoPago(publicKey, { locale: 'es-AR' });
  const bricks = mp.bricks();

  const checkoutMethod = getCheckoutMethod();
  const excludedTypes = checkoutMethod === 'tarjeta_debito'
    ? ['credit_card', 'prepaid_card']
    : ['debit_card'];
  const formTitle = checkoutMethod === 'tarjeta_debito'
    ? 'Tarjeta de debito'
    : 'Tarjeta de credito';

  const settings = {
    initialization: {
      amount: Number(amount || 0),
      payer: { email: payerEmail || '' },
    },
    customization: {
      visual: {
        style: { theme: 'default' },
        texts: { formTitle },
      },
      paymentMethods: {
        types: {
          excluded: excludedTypes,
        },
      },
    },
    callbacks: {
      onReady: () => {
        finishCheckoutLoading('payment-brick');
      },
      onError: (error) => {
        console.error('MP Brick error', error);
        failCheckoutLoading('payment-brick');
      },

      onSubmit: (submitData) => {
        const rawFormData = (submitData && submitData.formData) ? submitData.formData : (submitData || {});

        // Normalizacion JSON-safe (evita que el 1er submit falle antes del fetch)
        let safeFormData = {};
        try {
          // Convertimos a algo serializable (sin refs raras)
          safeFormData = JSON.parse(safeStringify(rawFormData, 7));
        } catch (e) {
          // Si incluso esto falla, lo tratamos como error de submit
          console.error('No se pudo serializar formData (Brick):', e, rawFormData);
          try { setStatus(statusEl, 'No se pudo preparar el pago. Reintenta.', 'error'); } catch (_) {}
          try { showToast('error', 'No se pudo preparar el pago. Reintenta.'); } catch (_) {}
          return Promise.reject(e);
        }

        return new Promise(async (resolve, reject) => {
          try {
            if (paymentUiLocked) {
              setStatus(statusEl, 'Ya registramos un intento de pago. Usá las acciones visibles para continuar.', 'info');
              reject(new Error('payment_locked'));
              return;
            }

            const selectedPaymentMethod = resolveCheckoutSelectedMethod(submitData, safeFormData);
            const payloadObj = {
              address_id: Number(addressId || 0),
              cardFormData: safeFormData || {},
              selectedPaymentMethod: selectedPaymentMethod !== '' ? selectedPaymentMethod : null,
            };

            // CLAVE: garantizamos que esto NO rompe antes del POST
            let payloadJson = '';
            try {
              payloadJson = safeStringify(payloadObj, 8);
            } catch (e) {
              console.error('Payload no serializable (antes del POST):', e, payloadObj);
              overlayCtrl.fail();
              try { setStatus(statusEl, 'Error al preparar el pago. Reintenta.', 'error'); } catch (_) {}
              try { showToast('error', 'Error al preparar el pago. Reintenta.'); } catch (_) {}
              reject(e);
              return;
            }

            if (stockPreflightUrl) {
              await postJson(stockPreflightUrl, {
                address_id: Number(addressId || 0),
                selectedPaymentMethod: selectedPaymentMethod !== '' ? selectedPaymentMethod : null,
              }, csrf);
            }

            // Ahora si: overlay SOLO cuando sabemos que el POST va a salir
            overlayCtrl.start();

            const res = await postJson(paymentUrl, payloadJson, csrf);
            // HTTP 202 es 2xx, pero "ok:false" NO significa compra exitosa.
            // Un cobro aprobado sin pedido nunca debe activar el camino approved.
            if (res && res.ok === false) {
              const code = String(res.code || '');
              const review = res.manual_reconciliation_required === true
                || ['approved_without_order', 'approved_payment_requires_review',
                  'duplicate_payment_for_intent'].includes(code);
              const unresolved = review || code === 'existing_payment_for_intent';
              if (unresolved) {
                paymentUiLocked = true;
                try { window.checkoutSetProcessing && window.checkoutSetProcessing(false); } catch (_) {}
                try { hideOverlay(); } catch (_) {}
                try { setBusy(btnStart, false); } catch (_) {}
                const base = getMeta('app-base-url') || '/';
                showPaymentResult({
                  state: 'manual_reconciliation',
                  tone: 'warning',
                  icon: 'clock',
                  badge: review ? 'RevisiÃ³n necesaria' : 'Pago en proceso',
                  title: review ? 'Pago pendiente de conciliaciÃ³n' : 'Ya existe un pago en curso',
                  lines: [
                    String(res.error || 'No pudimos confirmar tu pedido.'),
                    'No vuelvas a pagar hasta que se verifique el estado de esta operaciÃ³n.',
                  ],
                  actions: {
                    primary: { kind: 'link', label: 'Ver mis pedidos', href: base + 'cuenta/pedidos', variant: 'brand' },
                    secondary: { kind: 'link', label: 'Contactar soporte', href: base + 'ayuda#contacto', variant: 'ghost' },
                  },
                  flags: { clear_cart: false, lock_submit: true, allow_retry: false },
                });
                resolve({ ok: false, code, payment_id: res.payment_id || '', manual_reconciliation_required: review });
                return;
              }
              const err = new Error(String(res.error || 'No se pudo confirmar el pago.'));
              err.responseStatus = 202;
              err.responseData = res;
              throw err;
            }            const status = (res.status || '').toLowerCase();
            const detail = String(res.status_detail || '').toLowerCase().replace(/[\s-]+/g, '_');
            const paymentId = res.payment_id ? String(res.payment_id) : '';
            const orderId = Number(res.order_id || 0) || 0;
            const ui = (res.ui && typeof res.ui === 'object' && !Array.isArray(res.ui))
              ? res.ui
              : buildFallbackPaymentUi(status, detail, paymentId, orderId);
            const finalState = String(ui.state || status || 'unknown').toLowerCase();

            if (status === 'approved') {
              overlayCtrl.approved();

              const pid = paymentId;
              const q = pid ? ('?pago=' + encodeURIComponent(pid)) : '';
              const base = getMeta('app-base-url');
              const redirectTo = (base ? (base + 'cuenta/pedidos') : '/cuenta/pedidos') + q;

              resolve();

              (async () => {
                try {
                  const clearUrl = (container && container.dataset && container.dataset.cartClearUrl)
                    ? container.dataset.cartClearUrl
                    : '/carrito/vaciar';
                  await Promise.race([
                    postJson(clearUrl, {}, csrf),
                    new Promise((_, rej) => setTimeout(() => rej(new Error('timeout vaciar carrito')), 2500)),
                  ]);
                } catch (e) {
                  console.warn('No se pudo vaciar carrito (no bloqueante):', e);
                }
              })();

              const go = () => {
                try { window.location.assign(redirectTo); }
                catch (_) { try { window.location.href = redirectTo; } catch (_) {} }
              };

              try {
                showOverlay(
                  'Pago realizado correctamente.',
                  'Tu pedido fue confirmado y ya se encuentra en preparacion.'
                );
              } catch (_) {}

              setTimeout(() => {
                try {
                    showOverlay('Redirigiendo...', 'Te estamos llevando a tus pedidos.');
                } catch (_) {}
              }, 3700);

              setTimeout(go, 2700);
              setTimeout(go, 3200);

              return;
            }

            if ((status === 'pending' || status === 'in_process' || status === 'authorized') && orderId > 0) {
              const base = getMeta('app-base-url');
              const redirectTo = String(res.order_url || (base ? (base + 'cuenta/pedidos/ver?id=' + encodeURIComponent(String(orderId)) + '&ok=1&metodo=' + encodeURIComponent(String(res.payment_method || ''))) : ''));
              resolve({
                ok: true,
                status,
                status_detail: detail,
                payment_id: paymentId,
                order_id: orderId,
                ui,
              });

              try {
                showOverlay(
                  'Pago pendiente',
                  'Tu pedido ya fue creado y quedó a la espera de confirmación.'
                );
              } catch (_) {}

              setTimeout(() => {
                try { window.location.assign(redirectTo); }
                catch (_) { try { window.location.href = redirectTo; } catch (_) {} }
              }, 900);
              return;
            }

            try {
              paymentUiLocked = true;
              try { window.checkoutSetProcessing && window.checkoutSetProcessing(false); } catch (_) {}
              try { hideOverlay(); } catch (_) {}
              try { setBusy(btnStart, false); } catch (_) {}
              showPaymentResult({
                ...ui,
                state: finalState,
              });
            } catch (_) {}

            resolve({
              ok: true,
              status,
              status_detail: detail,
              payment_id: paymentId,
              order_id: orderId,
              ui,
            });
            return;
        } catch (e) {
            overlayCtrl.fail();
            try {
              const responseStatus = Number(e && e.responseStatus ? e.responseStatus : 0) || 0;
              const responseData = e && e.responseData && typeof e.responseData === 'object' ? e.responseData : {};
              const mpInternalError = responseData && String(responseData.code || '') === 'mp_internal_error';
              if (mpInternalError || responseStatus >= 500) {
                paymentUiLocked = true;
                try { window.checkoutSetProcessing && window.checkoutSetProcessing(false); } catch (_) {}
                try { hideOverlay(); } catch (_) {}
                try { setBusy(btnStart, false); } catch (_) {}

                showPaymentResult({
                  state: 'mp_error',
                  tone: 'warning',
                  icon: 'clock',
                  badge: 'Mercado Pago',
                  title: mpInternalError ? 'Mercado Pago tuvo un error temporal' : 'No se pudo procesar el pago',
                  lines: mpInternalError
                    ? ['Mercado Pago respondió con un error temporal.', 'Probá nuevamente en unos minutos.']
                    : ['No pudimos completar el pago en este momento.', 'Probá nuevamente en unos minutos.'],
                  actions: {
                    primary: { kind: 'button', action: 'retry', label: 'Intentar nuevamente', variant: 'brand' },
                    secondary: { kind: 'link', label: 'Volver al checkout', href: (getMeta('app-base-url') || '/') + 'checkout?step=4', variant: 'ghost' },
                  },
                });
                try {
                  setStatus(statusEl, mpInternalError
                    ? 'Mercado Pago tuvo un error temporal. Probá nuevamente en unos minutos.'
                    : 'No se pudo procesar el pago. Probá nuevamente en unos minutos.', 'error');
                } catch (_) {}
                return;
              }

              const redirectUrl = e && e.responseData ? String(e.responseData.redirect_url || '') : '';
              if (redirectUrl) {
                const message = e.message || 'Actualizamos tu carrito segun el stock disponible. Revisa el resumen para continuar.';
                setStatus(statusEl, message, 'error');
                try {
                  const loading = window.checkoutLoading;
                  if (loading && typeof loading.begin === 'function') {
                    loading.begin('payment-stock-preflight', {
                      title: 'Stock actualizado',
                      text: message,
                      minMs: 6000,
                      persist: false,
                    });
                  } else {
                    showOverlay('Stock actualizado', message);
                  }
                } catch (_) {}
                setTimeout(() => {
                  try { window.location.assign(redirectUrl); } catch (_) { window.location.href = redirectUrl; }
                }, 6000);
              }
            } catch (_) {}
            reject(e);
          }
        });
      },
    },
  };

  brickController = await bricks.create('cardPayment', containerId, settings);
  normalizeBrickContainer(container);
  window.requestAnimationFrame(() => normalizeBrickContainer(container));
  window.setTimeout(() => normalizeBrickContainer(container), 120);
  window.setTimeout(() => normalizeBrickContainer(container), 420);
  brickPrepared = true;
}

async function resetBrick() {
  const container = document.getElementById('mp-card-brick');
  brickPrepared = false;

  if (brickController && typeof brickController.unmount === 'function') {
    try { await brickController.unmount(); } catch (_) {}
  }
  brickController = null;

  if (container) {
    try { container.innerHTML = ''; } catch (_) {}
    try { container.classList.add('ui-is-hidden'); } catch (_) {}
  }
}

function getSelectedAddressId() {
  const checked = document.querySelector('input[name="address_id"]:checked');
  if (checked && checked.value) return String(checked.value);

  // Fallback: cuando el paso de direccion no esta renderizado, usamos sessionStorage
  try {
    const v = sessionStorage.getItem('checkout_address_id') || '';
    if (v) return String(v);
  } catch (_) {}

  // Fallback opcional por data-attribute
  const cfg = document.getElementById('mp-config');
  if (cfg && cfg.dataset && cfg.dataset.addressId) return String(cfg.dataset.addressId);
  return '';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function resultIconSvg(name) {
  const icon = String(name || 'info-circle');
  const common = 'class="ui-icon ui-icon--' + escapeHtml(icon) + '" aria-hidden="true"';

  switch (icon) {
    case 'shield-solid':
      return `<svg ${common} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.75L19 5.75V11.25C19 15.75 16.2 19.45 12 21.25C7.8 19.45 5 15.75 5 11.25V5.75L12 2.75Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12.5L11.1 14.6L15.4 10.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    case 'clock':
      return `<svg ${common} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 7V12L13.5 14.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    case 'circle-xmark-solid':
      return `<svg ${common} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.75C6.89 2.75 2.75 6.89 2.75 12S6.89 21.25 12 21.25 21.25 17.11 21.25 12 17.11 2.75 12 2.75ZM9.08 8.4 8.4 9.08 10.92 11.6 8.4 14.12 9.08 14.8 11.6 12.28 14.12 14.8 14.8 14.12 12.28 11.6 14.8 9.08 14.12 8.4 11.6 10.92 9.08 8.4Z" fill="currentColor"/></svg>`;
    case 'credit-card':
      return `<svg ${common} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.5 7.5H19.5V16.5H4.5V7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4.5 10.5H19.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 14.25H10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`;
    case 'warning-triangle':
      return `<svg ${common} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L21 19H3L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9V13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 17.2H12.01" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>`;
    case 'info-circle':
    default:
      return `<svg ${common} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.75C6.89 2.75 2.75 6.89 2.75 12S6.89 21.25 12 21.25 21.25 17.11 21.25 12 17.11 2.75 12 2.75Z" stroke="currentColor" stroke-width="1.8"/><path d="M12 10.75V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 7.4H12.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>`;
  }
}

function buildFallbackPaymentUi(status, detail, paymentId, orderId) {
  const st = String(status || '').toLowerCase();
  const dt = String(detail || '').toLowerCase().replace(/[\s-]+/g, '_');
  const orderQuery = orderId ? ('?id=' + encodeURIComponent(String(orderId))) : '';
  const paymentQuery = paymentId ? ('?pago=' + encodeURIComponent(String(paymentId))) : '';
  const ordersBase = (getMeta('app-base-url') || '/') + 'cuenta/pedidos';
  const checkoutBase = (getMeta('app-base-url') || '/') + 'checkout?step=4';
  const ordersUrl = ordersBase + paymentQuery;
  const orderUrl = orderId ? ((getMeta('app-base-url') || '/') + 'cuenta/pedidos/ver' + orderQuery) : ordersUrl;

  const ui = {
    state: st || 'unknown',
    tone: 'info',
    icon: 'info-circle',
    badge: 'Estado',
    title: 'Resultado del pago',
    lines: ['Estamos procesando tu solicitud.'],
    actions: { primary: null, secondary: null },
    flags: { clear_cart: false, lock_submit: true, allow_retry: false, keep_brick_visible: false },
  };

  if (st === 'approved') {
    ui.state = 'approved';
    ui.tone = 'success';
    ui.icon = 'shield-solid';
    ui.badge = 'Acreditado';
    ui.title = 'Pago acreditado';
    ui.lines = ['Recibimos y validamos tu pago.', 'Ya estamos preparando tu pedido.'];
    ui.actions.primary = { kind: 'link', label: 'Ver pedido', href: orderUrl, variant: 'brand' };
    ui.actions.secondary = { kind: 'link', label: 'Ir a pedidos', href: ordersBase, variant: 'ghost' };
    ui.flags.clear_cart = true;
    return ui;
  }

  if (st === 'pending' || st === 'in_process' || st === 'authorized') {
    ui.tone = 'warning';
    ui.icon = st === 'authorized' ? 'credit-card' : 'clock';
    ui.badge = st === 'authorized' ? 'Autorizado' : (st === 'pending' ? 'Pendiente' : 'En revisión');
    ui.title = st === 'authorized' ? 'Pago autorizado' : (st === 'pending' ? 'Pago pendiente' : 'Pago en proceso');
    ui.lines = [
      st === 'authorized'
        ? 'La operación fue autorizada y espera confirmación final.'
        : 'Mercado Pago está validando tu pago.',
      'No vuelvas a enviarlo mientras esperamos la confirmación.',
    ];
    ui.actions.primary = { kind: 'link', label: 'Ver estado del pago', href: ordersUrl, variant: 'brand' };
    ui.actions.secondary = { kind: 'link', label: 'Volver al checkout', href: checkoutBase, variant: 'ghost' };
    return ui;
  }

  if (st === 'cancelled') {
    ui.tone = 'neutral';
    ui.icon = 'warning-triangle';
    ui.badge = 'Cancelado';
    ui.title = 'Pago cancelado';
    ui.lines = ['La operación fue cancelada o expiró.', 'Podés volver a intentarlo cuando quieras.'];
    ui.actions.primary = { kind: 'button', action: 'retry', label: 'Intentar nuevamente', variant: 'brand' };
    ui.actions.secondary = { kind: 'link', label: 'Volver al carrito', href: (getMeta('app-base-url') || '/') + 'carrito', variant: 'ghost' };
    ui.flags.allow_retry = true;
    return ui;
  }

  if (st === 'rejected') {
    const recoverable = [
      'cc_rejected_bad_filled_card_number',
      'cc_rejected_bad_filled_date',
      'cc_rejected_bad_filled_security_code',
      'cc_rejected_bad_filled_other',
      'cc_rejected_call_for_authorize',
      'cc_rejected_insufficient_amount',
      'cc_rejected_invalid_installments',
      'cc_rejected_card_disabled',
      'cc_rejected_other_reason',
      'cc_rejected_time_out',
      'cc_rejected_duplicated_payment',
    ].includes(dt);

    ui.tone = 'error';
    ui.icon = 'circle-xmark-solid';
    ui.badge = recoverable ? 'Reintentar' : 'Rechazado';
    ui.title = recoverable ? 'No pudimos aprobar el pago' : 'El pago fue rechazado';
    ui.lines = recoverable
      ? ['Revisá los datos de la tarjeta o probá con otra tarjeta.', 'No envíes un nuevo pago hasta revisar el estado actual.']
      : ['Mercado Pago no pudo aprobar la operación.', 'Probá con otro medio de pago o volvé al carrito.'];
    ui.actions.primary = { kind: 'button', action: 'retry', label: 'Intentar nuevamente', variant: 'brand' };
    ui.actions.secondary = recoverable
      ? { kind: 'link', label: 'Elegir otro medio de pago', href: (getMeta('app-base-url') || '/') + 'checkout?step=3', variant: 'ghost' }
      : { kind: 'link', label: 'Volver al carrito', href: (getMeta('app-base-url') || '/') + 'carrito', variant: 'ghost' };
    ui.flags.allow_retry = true;
    return ui;
  }

  if (['refunded', 'charged_back', 'in_mediation'].includes(st)) {
    ui.tone = 'info';
    ui.icon = 'info-circle';
    ui.badge = st.replace(/_/g, ' ');
    ui.title = 'Estado del pago actualizado';
    ui.lines = ['El pago cambió de estado después de la confirmación inicial.', 'Revisá el detalle de tu pedido para ver el historial completo.'];
    ui.actions.primary = { kind: 'link', label: 'Ver pedido', href: orderUrl, variant: 'brand' };
    ui.actions.secondary = { kind: 'link', label: 'Ir a pedidos', href: ordersBase, variant: 'ghost' };
    return ui;
  }

  ui.tone = 'info';
  ui.icon = 'info-circle';
  ui.badge = 'Desconocido';
  ui.title = 'No pudimos interpretar el resultado';
  ui.lines = ['Recibimos una respuesta inesperada de Mercado Pago.', 'No vuelvas a pagar hasta revisar el estado del pedido.'];
  ui.actions.primary = { kind: 'link', label: 'Ver estado del pago', href: ordersUrl, variant: 'brand' };
  ui.actions.secondary = { kind: 'link', label: 'Ir a pedidos', href: ordersBase, variant: 'ghost' };
  return ui;
}

function getMpResultNodes() {
  return {
    panel: mpState && mpState.resultPanel ? mpState.resultPanel : document.getElementById('mp-result'),
    badge: mpState && mpState.resultBadge ? mpState.resultBadge : document.getElementById('mp-result-badge'),
    title: mpState && mpState.resultTitle ? mpState.resultTitle : document.getElementById('mp-result-title'),
    text: mpState && mpState.resultText ? mpState.resultText : document.getElementById('mp-result-text'),
    body: mpState && mpState.resultBody ? mpState.resultBody : document.getElementById('mp-result-body'),
    icon: mpState && mpState.resultIcon ? mpState.resultIcon : document.querySelector('[data-role="mp-result-icon"]'),
    primaryLink: mpState && mpState.resultPrimaryLink ? mpState.resultPrimaryLink : document.getElementById('mp-result-primary-link'),
    primaryButton: mpState && mpState.resultPrimaryButton ? mpState.resultPrimaryButton : document.getElementById('mp-result-primary-button'),
    secondaryLink: mpState && mpState.resultSecondaryLink ? mpState.resultSecondaryLink : document.getElementById('mp-result-secondary-link'),
    secondaryButton: mpState && mpState.resultSecondaryButton ? mpState.resultSecondaryButton : document.getElementById('mp-result-secondary-button'),
    brickWrap: mpState && mpState.brickWrap ? mpState.brickWrap : document.getElementById('mp-card-brick'),
    introBlock: document.getElementById('mp-card-intro'),
    statusEl: mpState && mpState.statusEl ? mpState.statusEl : document.getElementById('mp-status'),
  };
}

function hidePaymentResult() {
  const nodes = getMpResultNodes();
  if (nodes.panel) nodes.panel.classList.add('ui-is-hidden');
  if (nodes.brickWrap) nodes.brickWrap.classList.remove('ui-is-hidden');
}

function showPaymentResult(ui) {
  const nodes = getMpResultNodes();
  if (!nodes.panel) return;

  const state = String(ui && ui.state ? ui.state : 'unknown');
  const tone = String(ui && ui.tone ? ui.tone : 'info');
  const lines = Array.isArray(ui && ui.lines ? ui.lines : []) ? ui.lines : [];
  const actions = ui && ui.actions ? ui.actions : {};
  const primary = actions.primary || null;
  const secondary = actions.secondary || null;

  nodes.panel.dataset.state = state;
  nodes.panel.classList.remove('ui-is-hidden');
  if (nodes.brickWrap) nodes.brickWrap.classList.add('ui-is-hidden');

  if (nodes.badge) nodes.badge.textContent = String(ui && ui.badge ? ui.badge : '').trim();
  if (nodes.title) nodes.title.textContent = String(ui && ui.title ? ui.title : '').trim();
  if (nodes.text) {
    nodes.text.innerHTML = lines.map((line) => `<p>${escapeHtml(line)}</p>`).join('');
  }
  if (nodes.body) {
    nodes.body.innerHTML = '';
  }
  if (nodes.icon) {
    nodes.icon.innerHTML = resultIconSvg(ui && ui.icon ? ui.icon : 'info-circle');
  }
  if (nodes.statusEl) {
    const statusText = [ui && ui.title ? ui.title : '', lines[0] || ''].filter(Boolean).join('. ');
    setStatus(nodes.statusEl, statusText, tone === 'error' ? 'error' : (tone === 'success' ? 'ok' : 'info'));
  }

  const clearAction = (linkEl, btnEl) => {
    if (linkEl) {
      linkEl.classList.add('ui-is-hidden');
      linkEl.removeAttribute('href');
      linkEl.textContent = '';
    }
    if (btnEl) {
      btnEl.classList.add('ui-is-hidden');
      btnEl.textContent = '';
      btnEl.onclick = null;
    }
  };

  clearAction(nodes.primaryLink, nodes.primaryButton);
  clearAction(nodes.secondaryLink, nodes.secondaryButton);

  const bindAction = (action, linkEl, btnEl) => {
    if (!action) return;
    const kind = String(action.kind || '').toLowerCase();
    const label = String(action.label || '').trim();

    if (kind === 'button') {
      if (!btnEl) return;
      btnEl.textContent = label;
      btnEl.classList.remove('ui-is-hidden');
      btnEl.onclick = async (ev) => {
        ev.preventDefault();
        if (String(action.action || '') === 'retry') {
          paymentUiLocked = false;
          try { await resetBrick(); } catch (_) {}
          try { clearStatus(mpState && mpState.statusEl ? mpState.statusEl : document.getElementById('mp-status')); } catch (_) {}
          hidePaymentResult();
          try { setBusy(mpState && mpState.btn ? mpState.btn : document.getElementById('btn-mp-start'), false); } catch (_) {}
          scheduleMount('payment-retry', { manual: true, forceReset: true });
        }
      };
      return;
    }

    if (!linkEl) return;
    const href = String(action.href || '').trim();
    if (!href) return;
    linkEl.textContent = label;
    linkEl.href = href;
    linkEl.classList.remove('ui-is-hidden');
  };

  bindAction(primary, nodes.primaryLink, nodes.primaryButton);
  bindAction(secondary, nodes.secondaryLink, nodes.secondaryButton);
}

export function init() {
  const btn = qs('#btn-mp-start');
  const btnReload = qs('#btn-mp-reload');
  const introBlock = qs('#mp-card-intro');
  const statusEl = qs('#mp-status');
  const resultPanel = qs('#mp-result');
  const resultBadge = qs('#mp-result-badge');
  const resultTitle = qs('#mp-result-title');
  const resultText = qs('#mp-result-text');
  const resultBody = qs('#mp-result-body');
  const resultIcon = document.querySelector('[data-role="mp-result-icon"]');
  const resultPrimaryLink = qs('#mp-result-primary-link');
  const resultPrimaryButton = qs('#mp-result-primary-button');
  const resultSecondaryLink = qs('#mp-result-secondary-link');
  const resultSecondaryButton = qs('#mp-result-secondary-button');
  const fallback = qs('#mp-fallback');
  const configEl = qs('#mp-config');
  let brickWrap = qs('#mp-card-brick');

  // El contenedor del Brick debe existir siempre (lo dejamos fijo en el HTML).
  if (!configEl || !brickWrap) return;

  const paymentUrl = configEl.dataset.mpPaymentUrl || '';
  const stockPreflightUrl = configEl.dataset.mpStockPreflightUrl || '';
  const amount = configEl.dataset.amount || '0';
  const csrf = getMeta('csrf-token') || getMeta('csrf') || '';
  const payerEmail = getMeta('mp-payer-email') || '';

  // Estado compartido
  paymentUiLocked = false;
  mpState = {
    btn,
    statusEl,
    fallback,
    configEl,
    resultPanel,
    resultBadge,
    resultTitle,
    resultText,
    resultBody,
    resultIcon,
    resultPrimaryLink,
    resultPrimaryButton,
    resultSecondaryLink,
    resultSecondaryButton,
    get brickWrap() { return brickWrap; },
    set brickWrap(v) { brickWrap = v; },
    paymentUrl,
    stockPreflightUrl,
    amount,
    csrf,
    payerEmail,
  };

  hidePaymentResult();

  // Si cambia la direccion, invalidamos el Brick preparado (para forzar re-mount con payer/address correcto).
  const addressInputs = Array.from(document.querySelectorAll('input[name="address_id"]'));
  for (const r of addressInputs) {
    r.addEventListener('change', () => {
      paymentUiLocked = false;
      hidePaymentResult();
      try { brickPrepared = false; } catch (_) {}
      try { resetBrick(); } catch (_) {}
      // Si el usuario esta en tarjeta/step3, re-intentamos.
      scheduleMount('address-change');
    });
  }

  if (btn) {
    btn.addEventListener('click', (ev) => {
      ev.preventDefault();
      paymentUiLocked = false;
      hidePaymentResult();
      scheduleMount('manual', { manual: true, forceReset: true });
    });
  }

  // Observamos cambios de visibilidad de Step 3 / seccion tarjeta (wizard).
  try {
    const obs = new MutationObserver(() => {
      // Si el usuario aterriza o vuelve y el DOM cambia (stepper), re-sincronizamos.
      if (shouldBeMounted()) scheduleMount('dom-mutation');
    });
    obs.observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class', 'hidden', 'style'] });
  } catch (_) {}

  // BFCache / volver a la pestana: intentamos de forma determinista.
  if (!window.__mpCardBfCacheHooksAttached) {
    window.__mpCardBfCacheHooksAttached = true;
    window.addEventListener('pageshow', () => scheduleMount('pageshow'));
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') scheduleMount('visibility');
    });
    window.addEventListener('focus', () => scheduleMount('focus'));
  }

  // Intento inicial (si ya esta todo listo y el usuario esta en Paso 3 con tarjeta).
  scheduleMount('init');
}

