// Auth Register (progressive enhancement)

import { pushToast } from '../core/flash.js';

function clearFieldErrors() {
  const ids = ['name', 'email', 'password', 'password_confirm', 'terms'];
  for (const k of ids) {
    const el = document.getElementById('err-' + k);
    if (!el) continue;
    el.textContent = '';
    el.classList.add('ui-is-hidden');

    const field = fieldEl(k);
    if (field) field.classList.remove('input-error');
  }
}

function fieldEl(key) {
  const map = {
    name: 'reg-name',
    email: 'reg-email',
    password: 'reg-password',
    password_confirm: 'reg-password-confirm',
    terms: 'terms',
  };
  const id = map[key];
  return id ? document.getElementById(id) : null;
}

function showFieldError(key, message) {
  const el = document.getElementById('err-' + key);
  if (!el) return;
  el.textContent = message;
  el.classList.remove('ui-is-hidden');

  const field = fieldEl(key);
  if (field) field.classList.add('input-error');
}

function scrollToFirstError(errors) {
  const order = ['name', 'email', 'password', 'password_confirm', 'terms', 'general'];
  for (const key of order) {
    if (!errors || !errors[key]) continue;
    const field = fieldEl(key);
    const target = field || document.getElementById('register-errors');
    if (!target) return;

    try {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (_) {
      target.scrollIntoView(true);
    }

    if (field && typeof field.focus === 'function') {
      field.focus({ preventScroll: true });
    }
    return;
  }
}

function showTopErrors(errors) {
  const box = document.getElementById('register-errors');
  if (!box) return;
  const msgs = Object.values(errors || {}).filter(Boolean);
  if (!msgs.length) {
    box.innerHTML = '';
    box.classList.add('ui-is-hidden');
    return;
  }
  box.innerHTML = '<ul class="auth-register__error-list">' +
    msgs.map((m) => `<li>${escapeHtml(String(m))}</li>`).join('') +
    '</ul>';
  box.classList.remove('ui-is-hidden');
}

function escapeHtml(s) {
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

export function init() {
  const form = document.getElementById('register-form');
  if (!form) return;

  form.addEventListener('submit', async (ev) => {
    if (typeof fetch !== 'function') return;
    ev.preventDefault();

    clearFieldErrors();
    showTopErrors({});

    const fd = new FormData(form);

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const data = await res.json().catch(() => null);

      if (res.ok && data && data.ok) {
        const actionUrl = new URL(form.action, window.location.href);
        const baseDir = actionUrl.pathname.replace(/\/[A-Za-z0-9._-]+$/, '');
        const raw = data.redirect ? String(data.redirect) : '/cuenta';

        let targetPath = '';
        if (/^https?:\/\//i.test(raw)) {
          try {
            const u = new URL(raw);
            if (u.origin === actionUrl.origin) {
              targetPath = u.pathname || '/';
            } else {
              window.location.href = raw;
              return;
            }
          } catch (_) {
            targetPath = '/cuenta';
          }
        } else {
          targetPath = raw;
        }

        const p = targetPath.startsWith('/') ? targetPath : '/' + targetPath;
        const finalPath = (baseDir && baseDir !== '/' && !p.startsWith(baseDir + '/'))
          ? (baseDir + (p === '/' ? '' : p))
          : p;

        window.location.href = actionUrl.origin + finalPath;
        return;
      }

      const errs = (data && data.errors) ? data.errors : { general: 'No se pudo procesar el registro.' };
      const firstMsg = Object.values(errs || {}).find(Boolean);
      if (firstMsg) {
        try { pushToast({ type: 'error', text: String(firstMsg) }); } catch (_) {}
      }

      showTopErrors(errs);
      for (const [k, msg] of Object.entries(errs)) {
        if (!msg) continue;
        showFieldError(k, String(msg));
      }
      scrollToFirstError(errs);
    } catch (_) {
      form.submit();
    }
  });
}
