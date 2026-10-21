function getHost() {
  return document.getElementById('tp-toast-host');
}

function getTemplate() {
  return document.getElementById('tp-toast-template');
}

function normalizeType(type) {
  const t = String(type || 'ok').toLowerCase();
  if (t === 'success') return 'ok';
  if (t === 'info') return 'warn';
  if (t === 'warning') return 'warn';
  if (t === 'danger') return 'error';
  if (t === 'error') return 'error';
  if (t === 'warn') return 'warn';
  return 'ok';
}

function parseInitialFlashes() {
  const el = document.getElementById('tp-flash-data');
  if (!el) return [];
  try {
    const raw = el.textContent || '[]';
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  } finally {
    try { el.remove(); } catch (_) {}
  }
}

function mountToast({
  type = 'ok',
  message = '',
  detail = '',
  actionHref = '',
  actionText = '',
  duration = 5000,
  autohide = true,
  ariaLive = 'polite',
} = {}) {
  const host = getHost();
  const template = getTemplate();
  if (!host || !(template instanceof HTMLTemplateElement)) return null;

  const tone = normalizeType(type);
  const fragment = template.content.cloneNode(true);
  const toast = fragment.querySelector('.tp-toast');
  if (!toast) return null;

  toast.classList.add('tp-toast--in');
  if (tone === 'error') {
    toast.classList.add('tp-toast--error');
  }
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', ariaLive === 'assertive' ? 'assertive' : 'polite');

  const successIcon = fragment.querySelector('[data-toast-icon-success]');
  const errorIcon = fragment.querySelector('[data-toast-icon-error]');
  if (successIcon) successIcon.classList.toggle('ui-is-hidden', tone === 'error');
  if (errorIcon) errorIcon.classList.toggle('ui-is-hidden', tone !== 'error');

  const msg = fragment.querySelector('[data-toast-message]');
  if (msg) msg.textContent = String(message || '').trim();

  const detailEl = fragment.querySelector('[data-toast-detail]');
  if (detailEl) {
    const d = String(detail || '').trim();
    detailEl.textContent = d;
    detailEl.classList.toggle('ui-is-hidden', d === '');
  }

  const action = fragment.querySelector('[data-toast-action]');
  if (action instanceof HTMLAnchorElement) {
    const href = String(actionHref || '').trim();
    if (href !== '') {
      action.href = href;
      action.textContent = String(actionText || 'Ver');
      action.classList.remove('ui-is-hidden');
    } else {
      action.removeAttribute('href');
      action.textContent = '';
      action.classList.add('ui-is-hidden');
    }
  }

  const close = fragment.querySelector('[data-toast-close]');
  let timer = null;

  const removeToast = () => {
    try { toast.remove(); } catch (_) {}
  };

  if (close instanceof HTMLButtonElement) {
    close.addEventListener('click', () => {
      if (timer) clearTimeout(timer);
      removeToast();
    });
  }

  host.appendChild(toast);

  if (autohide) {
    timer = setTimeout(removeToast, Math.max(1200, Number(duration) || 5000));
  }

  return toast;
}

export function pushToast({
  text,
  type = 'ok',
  duration = 5000,
  autohide = true,
  ariaLive = 'polite',
  detail = '',
  actionHref = '',
  actionText = '',
} = {}) {
  const msg = String(text ?? '').trim();
  if (!msg) return;
  mountToast({
    type,
    message: msg,
    detail,
    actionHref,
    actionText,
    duration,
    autohide,
    ariaLive,
  });
}

export function init() {
  const host = getHost();
  if (!host) return;

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const last = host.querySelector('.tp-toast:last-of-type [data-toast-close]');
    if (last instanceof HTMLButtonElement) last.click();
  });

  const initial = parseInitialFlashes();
  initial.forEach((item) => {
    const type = normalizeType(item?.type || 'ok');
    const text = String(item?.text || '').trim();
    if (!text) return;
    mountToast({
      type,
      message: text,
      duration: type === 'error' ? 8000 : (type === 'warn' ? 6500 : 4500),
      autohide: type !== 'error',
      ariaLive: type === 'error' ? 'assertive' : 'polite',
    });
  });

  if (typeof window !== 'undefined') {
    const toastFn = (kind, message, opts = {}) => {
      const text = String(message ?? '').trim();
      if (!text) return;
      const type = normalizeType(kind || 'ok');
      const isError = type === 'error';
      const duration = Number.isFinite(+opts.duration)
        ? +opts.duration
        : (isError ? 8000 : (type === 'warn' ? 6500 : 4500));
      const autohide = (typeof opts.autohide === 'boolean') ? opts.autohide : !isError;
      pushToast({
        type,
        text,
        duration,
        autohide,
        ariaLive: isError ? 'assertive' : 'polite',
        detail: opts.detail || '',
        actionHref: opts.actionHref || '',
        actionText: opts.actionText || '',
      });
    };

    toastFn.success = (msg, opts = {}) => toastFn('success', msg, opts);
    toastFn.ok = (msg, opts = {}) => toastFn('ok', msg, opts);
    toastFn.warn = (msg, opts = {}) => toastFn('warn', msg, opts);
    toastFn.info = (msg, opts = {}) => toastFn('info', msg, opts);
    toastFn.error = (msg, opts = {}) => toastFn('error', msg, opts);
    toastFn.__impl = 'tp-toast-v2';
    window.toast = toastFn;
    window.__toast_impl = 'tp-toast-v2';
    window.__pushToast = pushToast;
  }
}
