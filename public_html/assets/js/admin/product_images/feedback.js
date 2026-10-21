// assets/js/admin/product_images/feedback.js
// Adapter: delega toasts a core/flash.js y mantiene utilidades accesibles.

import { pushToast } from '../../core/flash.js';

export const toast = {
  // Mapear a los tipos del servidor para que tomen las mismas paletas
  info:    (text, opts={}) => pushToast({ text, type: 'warn', ...(opts||{}) }),
  success: (text, opts={}) => pushToast({ text, type: 'ok',   ...(opts||{}) }),
  error:   (text, opts={}) => pushToast({ text, type: 'error', ...(opts||{}) }),
};

export const ariaLive = {
  say: (text, mode='polite') => {
    let n = document.getElementById('aria-live-region');
    if (!n) {
      n = document.createElement('div');
      n.id = 'aria-live-region';
      n.setAttribute('aria-live', 'polite');
      n.className = 'sr-only';
      document.body.appendChild(n);
    }
    n.setAttribute('aria-live', mode === 'assertive' ? 'assertive' : 'polite');
    n.textContent = text || '';
  }
};

export const btnBusy = (btn, on=true, label) => {
  if (!btn) return;
  if (on) {
    btn._prevText = btn.textContent;
    if (label) btn.textContent = label;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
  } else {
    btn.disabled = false;
    btn.removeAttribute('aria-busy');
    if (btn._prevText != null) btn.textContent = btn._prevText;
    delete btn._prevText;
  }
};
