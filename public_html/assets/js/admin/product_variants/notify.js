let seq = 0;
let latestStarted = 0;

try {
  if (typeof window !== 'undefined') {
    window.__pv_notify_impl = 'v3-inline-feedback';
  }
} catch (_) {}

export function startAction(label = 'action') {
  const id = ++seq;
  latestStarted = id;
  debug('start', { id, label });
  return { id, label, startedAt: Date.now() };
}

function isStale(action) {
  if (!action || typeof action.id !== 'number') return false;
  return action.id < latestStarted;
}

function emit(type, message, action = null) {
  const text = String(message ?? '').trim();
  if (!text) return;
  if (isStale(action)) {
    debug('drop-stale', { id: action.id, latestStarted, type, message: text });
    return;
  }

  debug('emit', { id: action?.id || 0, latestStarted, type, message: text });
  showInlineFeedback(type, text);
}

function showInlineFeedback(type, text) {
  const root = document.getElementById('variants-sale');
  if (!root) return;
  const box = root.querySelector('[data-role="vs-feedback"]');
  const title = root.querySelector('[data-role="vs-feedback-title"]');
  const body = root.querySelector('[data-role="vs-feedback-text"]');
  if (!box || !title || !body) return;

  const tone = normalizeTone(type);
  title.textContent = tone.title;
  body.textContent = text;

  box.classList.remove('av3-hidden');
  box.style.border = `1px solid ${tone.border}`;
  box.style.background = tone.bg;
  box.style.color = tone.color;

  try { box.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch (_) {}
}

function normalizeTone(type) {
  const t = String(type || 'ok').toLowerCase();
  if (t === 'error') {
    return {
      title: 'Error',
      bg: 'rgba(239,68,68,.08)',
      border: 'rgba(239,68,68,.25)',
      color: '#7f1d1d',
    };
  }
  if (t === 'warn' || t === 'warning' || t === 'info') {
    return {
      title: 'Atencion',
      bg: 'rgba(245,158,11,.10)',
      border: 'rgba(245,158,11,.28)',
      color: '#78350f',
    };
  }
  return {
    title: 'Listo',
    bg: 'rgba(16,185,129,.10)',
    border: 'rgba(16,185,129,.28)',
    color: '#065f46',
  };
}

function debug(event, data) {
  try {
    if (typeof window !== 'undefined' && window.__DEBUG_TOAST === false) return;
    console.log('[pv-toast]', event, data || {});
  } catch (_) {}
}

export function success(message) {
  emit('ok', message);
}

export function warn(message) {
  emit('warn', message);
}

export function error(message) {
  emit('error', message);
}

export function successScoped(action, message) {
  emit('ok', message, action);
}

export function warnScoped(action, message) {
  emit('warn', message, action);
}

export function errorScoped(action, message) {
  emit('error', message, action);
}
