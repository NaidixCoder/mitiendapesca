/**
 * Overlay de proceso (Admin) para operaciones que demoran.
 * Requiere el markup: app/Views/partials/admin/processing_overlay.php
 */

let _els = null;
let _okCb = null;

function getEls() {
  if (_els) return _els;
  const root = document.getElementById('admin-processing-overlay');
  if (!root) return null;
  _els = {
    root,
    title: document.getElementById('admin-processing-title'),
    text: document.getElementById('admin-processing-text'),
    hint: document.getElementById('admin-processing-hint'),
    spinner: document.getElementById('admin-processing-spinner'),
    icon: document.getElementById('admin-processing-icon'),
    actions: document.getElementById('admin-processing-actions'),
    ok: document.getElementById('admin-processing-ok'),
  };

  // Bind OK una sola vez
  if (_els.ok) {
    _els.ok.addEventListener('click', () => {
      const cb = _okCb;
      _okCb = null;
      hideProcessing();
      if (typeof cb === 'function') cb();
    });
  }
  return _els;
}

export function showProcessing(opts = {}) {
  const els = getEls();
  if (!els) return;

  // reset UI (modo loading)
  if (els.spinner) els.spinner.classList.remove('av3-hidden');
  if (els.icon) els.icon.classList.add('av3-hidden');
  if (els.actions) els.actions.classList.add('av3-hidden');
  if (els.ok) els.ok.textContent = 'OK';
  _okCb = null;

  const title = String(opts.title || 'Procesando...');
  const text  = String(opts.text || 'No cierres esta ventana.');
  const hint  = String(opts.hint || 'Esto puede demorar unos segundos, dependiendo de ARCA.');
  els.title.textContent = title;
  els.text.textContent = text;
  if (els.hint) els.hint.textContent = hint;

  els.root.classList.remove('av3-hidden');
  els.root.setAttribute('aria-hidden', 'false');
}

/**
 * Cambia el overlay a modo "resultado" (sin spinner) y muestra botón OK.
 * type: ok | warn | error
 */
export function showProcessingResult(opts = {}) {
  const els = getEls();
  if (!els) return;

  const type  = String(opts.type || 'ok');
  const title = String(opts.title || 'Listo');
  const text  = String(opts.text || 'Operación completada.');
  const hint  = String(opts.hint || '');
  const okText = String(opts.okText || 'OK');

  if (els.spinner) els.spinner.classList.add('av3-hidden');

  if (els.icon) {
    els.icon.classList.remove('av3-hidden');
    els.icon.classList.remove('av3-border-emerald-300','av3-text-emerald-700','av3-bg-emerald-50','av3-border-amber-300','av3-text-amber-700','av3-bg-amber-50','av3-border-rose-300','av3-text-rose-700','av3-bg-rose-50','av3-border-slate-300','av3-text-slate-700','av3-bg-slate-50');
    if (type === 'error') {
      els.icon.textContent = '!';
      els.icon.classList.add('av3-border-rose-300','av3-text-rose-700','av3-bg-rose-50');
    } else if (type === 'warn') {
      els.icon.textContent = '!';
      els.icon.classList.add('av3-border-amber-300','av3-text-amber-700','av3-bg-amber-50');
    } else {
      els.icon.textContent = '✓';
      els.icon.classList.add('av3-border-emerald-300','av3-text-emerald-700','av3-bg-emerald-50');
    }
  }

  els.title.textContent = title;
  els.text.textContent = text;
  if (els.hint) els.hint.textContent = hint;

  if (els.actions) els.actions.classList.remove('av3-hidden');
  if (els.ok) els.ok.textContent = okText;

  if (typeof opts.onOk === 'function') {
    _okCb = opts.onOk;
  } else {
    _okCb = null;
  }

  els.root.classList.remove('av3-hidden');
  els.root.setAttribute('aria-hidden', 'false');
}

export function hideProcessing() {
  const els = getEls();
  if (!els) return;
  els.root.classList.add('av3-hidden');
  els.root.setAttribute('aria-hidden', 'true');
}
