/**
 * Modal de confirmación (promesa) para acciones sensibles en Admin.
 *
 * Requiere el markup del partial: app/Views/partials/admin/confirm_modal.php
 */

let _els = null;

function getEls() {
  if (_els) return _els;
  const root = document.getElementById('confirm-modal');
  if (!root) return null;
  _els = {
    root,
    title: document.getElementById('confirm-modal-title'),
    subtitle: document.getElementById('confirm-modal-subtitle'),
    body: document.getElementById('confirm-modal-body'),
    ok: document.getElementById('confirm-modal-ok'),
    alt: document.getElementById('confirm-modal-alt'),
    cancel: document.getElementById('confirm-modal-cancel'),
    x: document.getElementById('confirm-modal-x'),
  };
  return _els;
}

function show() {
  const els = getEls();
  if (!els) return;
  els.root.classList.remove('av3-hidden');
  els.root.setAttribute('aria-hidden', 'false');
}

function hide() {
  const els = getEls();
  if (!els) return;
  els.root.classList.add('av3-hidden');
  els.root.setAttribute('aria-hidden', 'true');
}

/**
 * @param {Object} opts
 * @param {string} opts.title
 * @param {string} opts.subtitle
 * @param {string} opts.body
 * @param {string} [opts.okText]
 * @param {string} [opts.cancelText]
 * @param {string|null} [opts.altText]  si es null/undefined, se oculta
 * @returns {Promise<'ok'|'alt'|'cancel'>}
 */
export function confirmModal(opts) {
  const els = getEls();
  if (!els) {
    // fallback: confirm simple
    const ok = window.confirm(opts?.body || opts?.title || '¿Confirmar acción?');
    return Promise.resolve(ok ? 'ok' : 'cancel');
  }

  const title = String(opts?.title || 'Confirmar acción');
  const subtitle = String(opts?.subtitle || '');
  const body = String(opts?.body || '');
  const okText = String(opts?.okText || 'Confirmar');
  const cancelText = String(opts?.cancelText || 'Cancelar');
  const altText = (opts && Object.prototype.hasOwnProperty.call(opts, 'altText')) ? opts.altText : null;

  els.title.textContent = title;
  els.subtitle.textContent = subtitle;
  els.body.textContent = body;
  els.ok.textContent = okText;
  els.cancel.textContent = cancelText;

  if (altText) {
    els.alt.textContent = String(altText);
    els.alt.classList.remove('av3-hidden');
  } else {
    els.alt.classList.add('av3-hidden');
  }

  show();

  return new Promise((resolve) => {
    let settled = false;

    const cleanup = () => {
      if (!els) return;
      els.ok.removeEventListener('click', onOk);
      els.alt.removeEventListener('click', onAlt);
      els.cancel.removeEventListener('click', onCancel);
      els.x.removeEventListener('click', onCancel);
      els.root.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onKey);
    };

    const finish = (v) => {
      if (settled) return;
      settled = true;
      cleanup();
      hide();
      resolve(v);
    };

    const onOk = () => finish('ok');
    const onAlt = () => finish('alt');
    const onCancel = () => finish('cancel');

    const onBackdrop = (e) => {
      // click fuera del card
      if (e.target === els.root || (e.target && e.target.classList && e.target.classList.contains('av3-bg-black-40'))) {
        finish('cancel');
      }
    };

    const onKey = (e) => {
      if (e.key === 'Escape') finish('cancel');
    };

    els.ok.addEventListener('click', onOk);
    els.alt.addEventListener('click', onAlt);
    els.cancel.addEventListener('click', onCancel);
    els.x.addEventListener('click', onCancel);
    els.root.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onKey);
  });
}
