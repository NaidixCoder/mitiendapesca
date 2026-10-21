// assets/js/ui/loading_overlay.js
// Overlay modal simple (bloqueante) para operaciones async.
// Uso:
//   const ov = createLoadingOverlay();
//   ov.showLoading({ title, text });
//   ov.showResult({ type: 'success'|'error'|'info', title, text, onClose });
//   ov.hide();

function iconFor(type) {
  // Simple SVGs inline (sin depender de libs)
  if (type === 'success') {
    return `
      <div class="site-loading__icon site-loading__icon--success">
        <svg class="site-loading__svg site-loading__svg--success" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.415l-7.25 7.25a1 1 0 01-1.415 0l-3.25-3.25A1 1 0 016.204 9.29l2.543 2.543 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
      </div>`;
  }
  if (type === 'error') {
    return `
      <div class="site-loading__icon site-loading__icon--error">
        <svg class="site-loading__svg site-loading__svg--error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.707-10.707a1 1 0 00-1.414-1.414L10 7.586 8.707 6.293a1 1 0 00-1.414 1.414L8.586 9l-1.293 1.293a1 1 0 101.414 1.414L10 10.414l1.293 1.293a1 1 0 001.414-1.414L11.414 9l1.293-1.293z" clip-rule="evenodd"/>
        </svg>
      </div>`;
  }
  // info
  return `
    <div class="site-loading__icon site-loading__icon--info">
      <svg class="site-loading__svg site-loading__svg--info" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-8-4a1 1 0 00-1 1v3a1 1 0 102 0V7a1 1 0 00-1-1zm0 8a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
      </svg>
    </div>`;
}

export function createLoadingOverlay() {
  let el = document.getElementById('tpLoadingOverlay');
  if (!el) {
    el = document.createElement('div');
    el.id = 'tpLoadingOverlay';
    el.className = 'site-loading is-hidden';
    el.innerHTML = `
      <div class="site-loading__backdrop"></div>
      <div class="site-loading__dialog">
        <div class="site-loading__row">
          <div class="site-loading__icon-wrap" id="tpLoadingOverlayIcon">
            <div class="site-loading__icon">
              <svg class="site-loading__svg site-loading__spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="site-loading__faint" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="site-loading__strong" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
            </div>
          </div>
          <div class="site-loading__content">
            <div class="site-loading__title" id="tpLoadingOverlayTitle">Procesando…</div>
            <div class="site-loading__text" id="tpLoadingOverlayText">Un momento, por favor.</div>
          </div>
        </div>
        <div class="site-loading__actions">
          <button type="button" id="tpLoadingOverlayClose"
            class="site-loading__close is-hidden">
            Cerrar
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(el);
  }

  const iconWrap = el.querySelector('#tpLoadingOverlayIcon');
  const titleEl = el.querySelector('#tpLoadingOverlayTitle');
  const textEl = el.querySelector('#tpLoadingOverlayText');
  const closeBtn = el.querySelector('#tpLoadingOverlayClose');

  let onCloseCb = null;

  function showLoading({ text = 'Un momento, por favor.', title = 'Procesando…' } = {}) {
    if (iconWrap) {
      iconWrap.innerHTML = `
        <div class="site-loading__icon">
          <svg class="site-loading__svg site-loading__spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="site-loading__faint" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="site-loading__strong" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
        </div>`;
    }
    if (titleEl) titleEl.textContent = String(title);
    if (textEl) textEl.textContent = String(text);
    if (closeBtn) closeBtn.classList.add('is-hidden');
    onCloseCb = null;
    el.classList.remove('is-hidden');
    el.classList.add('is-visible');
  }

  function showResult({ type = 'info', title = 'Resultado', text = '', onClose = null } = {}) {
    if (iconWrap) iconWrap.innerHTML = iconFor(type);
    if (titleEl) titleEl.textContent = String(title);
    if (textEl) textEl.textContent = String(text);
    onCloseCb = (typeof onClose === 'function') ? onClose : null;
    if (closeBtn) closeBtn.classList.remove('is-hidden');
    el.classList.remove('is-hidden');
    el.classList.add('is-visible');
  }

  function hide() {
    el.classList.add('is-hidden');
    el.classList.remove('is-visible');
    if (closeBtn) closeBtn.classList.add('is-hidden');
    onCloseCb = null;
  }

  if (closeBtn && !closeBtn.dataset.bound) {
    closeBtn.dataset.bound = '1';
    closeBtn.addEventListener('click', () => {
      const cb = onCloseCb;
      hide();
      if (cb) cb();
    });
  }

  return { showLoading, showResult, hide };
}
