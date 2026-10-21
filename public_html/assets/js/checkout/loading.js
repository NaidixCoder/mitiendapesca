const KEY = 'checkout_loading_pending';
const DEFAULT_MIN_MS = 2000;

let active = null;
let finishTimer = null;

function getOverlay() {
  return document.getElementById('mp-overlay');
}

function getTitleEl() {
  return document.getElementById('mp-overlay-title');
}

function getTextEl() {
  return document.getElementById('mp-overlay-text');
}

function now() {
  return Date.now();
}

function normalizePayload(kind, opts = {}) {
  return {
    kind: String(kind || 'generic'),
    title: String(opts.title || 'Preparando todo para tu compra'),
    text: String(opts.text || 'Estamos preparando el siguiente paso.'),
    minMs: Math.max(0, parseInt(opts.minMs || DEFAULT_MIN_MS, 10) || DEFAULT_MIN_MS),
    startedAt: Math.max(0, parseInt(opts.startedAt || now(), 10) || now()),
  };
}

function paint(payload) {
  const overlay = getOverlay();
  if (!overlay) return;

  const titleEl = getTitleEl();
  const textEl = getTextEl();

  if (titleEl) titleEl.textContent = payload.title;
  if (textEl) textEl.textContent = payload.text;

  overlay.classList.remove('ui-is-hidden');
}

function hide() {
  const overlay = getOverlay();
  if (!overlay) return;
  overlay.classList.add('ui-is-hidden');
}

function save(payload) {
  try {
    sessionStorage.setItem(KEY, JSON.stringify(payload));
  } catch (_) {}
}

function clearSaved() {
  try {
    sessionStorage.removeItem(KEY);
  } catch (_) {}
}

function readSaved() {
  try {
    const raw = sessionStorage.getItem(KEY) || '';
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed !== 'object') return null;
    return normalizePayload(parsed.kind || 'generic', parsed);
  } catch (_) {
    return null;
  }
}

function begin(kind, opts = {}) {
  if (finishTimer) {
    clearTimeout(finishTimer);
    finishTimer = null;
  }

  const payload = normalizePayload(kind, opts);
  active = payload;
  paint(payload);

  if (opts.persist !== false) {
    save(payload);
  }

  return payload;
}

function queueNext(kind, opts = {}) {
  const payload = normalizePayload(kind, opts);
  save(payload);
  return payload;
}

function finish(kind = '') {
  const current = active || readSaved();
  if (!current) {
    hide();
    clearSaved();
    return Promise.resolve();
  }

  if (kind && current.kind && String(kind) !== String(current.kind)) {
    return Promise.resolve();
  }

  const remaining = Math.max(0, (current.startedAt + current.minMs) - now());
  clearSaved();

  return new Promise((resolve) => {
    finishTimer = window.setTimeout(() => {
      hide();
      active = null;
      finishTimer = null;
      resolve();
    }, remaining);
  });
}

function fail(kind = '') {
  return finish(kind);
}

function init() {
  const top = document.querySelector('[data-checkout-top]');
  const currentStep = top && top.dataset.checkoutCurrentStep ? parseInt(top.dataset.checkoutCurrentStep, 10) : null;

  if (currentStep === 1) {
    active = null;
    clearSaved();
    hide();
    return;
  }

  const pending = readSaved();
  if (!pending) return;
  active = pending;
  paint(pending);
}

window.checkoutLoading = {
  begin,
  queueNext,
  finish,
  fail,
  getActive() {
    return active;
  },
};

export { init, begin, queueNext, finish, fail };
