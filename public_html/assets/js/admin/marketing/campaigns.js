import { showToast } from '../../ui/cart_feedback.js';

function copyText(text) {
  if (!text) return Promise.reject(new Error('No text'));
  if (navigator.clipboard && window.isSecureContext) {
    return navigator.clipboard.writeText(text);
  }
  // Fallback
  return new Promise((resolve, reject) => {
    try {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.top = '-1000px';
      ta.style.left = '-1000px';
      document.body.appendChild(ta);
      ta.select();
      const ok = document.execCommand('copy');
      document.body.removeChild(ta);
      if (!ok) return reject(new Error('copy failed'));
      resolve();
    } catch (e) {
      reject(e);
    }
  });
}

function bindCopyButtons(root = document) {
  const btns = root.querySelectorAll('[data-copy-text]');
  btns.forEach((btn) => {
    if (btn.dataset.boundCopy === '1') return;
    btn.dataset.boundCopy = '1';
    btn.addEventListener('click', async () => {
      const text = btn.getAttribute('data-copy-text') || '';
      try {
        await copyText(text);
        showToast({
          type: 'ok',
          message: 'Copiado',
          detail: 'Link copiado al portapapeles.',
        });
      } catch (e) {
        showToast({
          type: 'error',
          message: 'No se pudo copiar',
          detail: 'Copialo manualmente.',
        });
      }
    });
  });
}

function bindCampaignPreview(root = document) {
  const form = root.querySelector('[data-marketing-campaign-form]');
  if (!form) return;
  const slug = form.querySelector('[data-marketing-slug]');
  const link = form.querySelector('[data-marketing-preview-url]');
  const copy = form.querySelector('[data-marketing-preview-copy]');
  if (!slug || !link || !copy) return;

  const update = () => {
    const value = slug.value.trim();
    // Solo previsualizar el slug escrito; la generación definitiva sigue en PHP.
    const valid = /^[a-z0-9][a-z0-9-]*$/.test(value);
    const url = valid ? form.dataset.promoBase + encodeURIComponent(value) : '';
    link.textContent = valid ? url : 'Se mostrará al completar un enlace válido';
    link.href = valid ? url : '#';
    if (valid) {
      link.setAttribute('target', '_blank');
      link.setAttribute('rel', 'noopener noreferrer');
      link.removeAttribute('aria-disabled');
      link.removeAttribute('tabindex');
    } else {
      link.removeAttribute('target');
      link.removeAttribute('rel');
      link.setAttribute('aria-disabled', 'true');
      link.setAttribute('tabindex', '-1');
    }
    copy.disabled = !valid;
    copy.setAttribute('data-copy-text', url);
  };

  slug.addEventListener('input', update);
  update();
}

export function init() {
  bindCopyButtons(document);
  bindCampaignPreview(document);
}
