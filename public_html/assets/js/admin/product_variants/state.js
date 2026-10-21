let overlayEl = null;

export function showOverlay(title = 'Procesando...', text = '') {
  try {
    if (!overlayEl) {
      const el = document.createElement('div');
      el.setAttribute('data-role', 'vs-overlay');
      el.className = 'av3-variant-wait-overlay is-hidden';
      el.innerHTML = `
        <div class="av3-absolute inset-0 av3-bg-black-30"></div>
        <div class="av3-absolute inset-0 av3-flex av3-items-center av3-justify-center av3-pad-4">
          <div class="av3-w-full av3-max-w-md av3-radius-xl av3-surface-white av3-shadow-xl av3-border av3-border-slate-200 av3-p-5">
            <div class="av3-type-base av3-weight-strong av3-text-slate-900" data-role="vs-overlay-title"></div>
            <div class="av3-mt-1 av3-type-body av3-text-slate-600" data-role="vs-overlay-text"></div>
            <div class="av3-mt-4 av3-h-1-5 av3-w-full av3-overflow-hidden av3-radius-full av3-bg-slate-100">
              <div class="av3-h-full av3-width-two-thirds animate-pulse av3-bg-slate-300"></div>
            </div>
          </div>
        </div>`;
      document.body.appendChild(el);
      overlayEl = el;
    }
    const t = overlayEl.querySelector('[data-role="vs-overlay-title"]');
    const x = overlayEl.querySelector('[data-role="vs-overlay-text"]');
    if (t) t.textContent = String(title || 'Procesando...');
    if (x) x.textContent = String(text || '');
    overlayEl.classList.remove('is-hidden');
    document.body.classList.add('av3-overflow-hidden');
  } catch (_) {}
}

export function hideOverlay() {
  try {
    if (overlayEl) overlayEl.classList.add('is-hidden');
    document.body.classList.remove('av3-overflow-hidden');
  } catch (_) {}
}
