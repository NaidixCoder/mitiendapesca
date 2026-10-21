// Simple confirm handler (no inline JS)
// Usage: add data-confirm="..." to <button> or <a>
export function init() {
  document.addEventListener('click', (ev) => {
    const el = ev.target && ev.target.closest ? ev.target.closest('[data-confirm]') : null;
    if (!el) return;

    const msg = el.getAttribute('data-confirm') || '¿Confirmás la acción?';
    // eslint-disable-next-line no-alert
    const ok = window.confirm(msg);
    if (!ok) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  }, true);
}
