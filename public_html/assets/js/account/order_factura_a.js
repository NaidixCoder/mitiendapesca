// assets/js/account/order_factura_a.js
import { createLoadingOverlay } from '../ui/loading_overlay.js';

async function copyTextValue(value) {
  const text = String(value || '').trim();
  if (!text) return false;

  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text);
    return true;
  }

  const helper = document.createElement('textarea');
  helper.value = text;
  helper.setAttribute('readonly', 'readonly');
  helper.style.position = 'fixed';
  helper.style.opacity = '0';
  helper.style.pointerEvents = 'none';
  document.body.appendChild(helper);
  helper.select();

  let copied = false;
  try {
    copied = document.execCommand('copy');
  } finally {
    document.body.removeChild(helper);
  }

  return copied;
}

function initOrderCopyButtons() {
  const buttons = document.querySelectorAll('[data-order-copy]');
  if (!buttons.length) return;

  buttons.forEach((button) => {
    if (button.dataset.copyBound === '1') return;
    button.dataset.copyBound = '1';

    button.addEventListener('click', async () => {
      const originalLabel = button.getAttribute('aria-label') || 'Copiar';

      try {
        const ok = await copyTextValue(button.getAttribute('data-order-copy') || '');
        if (!ok) return;

        button.classList.add('is-copied');
        button.setAttribute('aria-label', 'Copiado');

        window.clearTimeout(button._copyResetTimer);
        button._copyResetTimer = window.setTimeout(() => {
          button.classList.remove('is-copied');
          button.setAttribute('aria-label', originalLabel);
        }, 1600);
      } catch (_) {
        button.setAttribute('aria-label', 'No se pudo copiar');
        window.clearTimeout(button._copyResetTimer);
        button._copyResetTimer = window.setTimeout(() => {
          button.setAttribute('aria-label', originalLabel);
        }, 1600);
      }
    });
  });
}

export function initAccountOrderFacturaA() {
  initOrderCopyButtons();

  const form = document.querySelector('form[data-factura-a-request]');
  if (!form) return;

  const overlay = createLoadingOverlay();


  const cuitInput = form.querySelector('input[name="cuit"], input[name="customer_cuit"], input[name="tax_id"], input[data-cuit]');
  const ivaSelect = form.querySelector('select[name="iva_condition"], select[name="condicion_iva"], select[name="condicion"], select[data-iva]');
  let inlineErrorEl = null;

  const clearFieldError = () => {
    if (cuitInput) {
      cuitInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200', 'focus:ring-red-200', 'focus:border-red-500');
      cuitInput.removeAttribute('aria-invalid');
    }
    if (inlineErrorEl) {
      inlineErrorEl.textContent = '';
      inlineErrorEl.classList.add('ui-is-hidden');
    }
  };

  const showFieldError = (message) => {
    if (!cuitInput) return;
    cuitInput.classList.add('border-red-500', 'ring-2', 'ring-red-200', 'focus:ring-red-200', 'focus:border-red-500');
    cuitInput.setAttribute('aria-invalid', 'true');
    // Create an inline error element right under the input (only once)
    if (!inlineErrorEl) {
      inlineErrorEl = document.createElement('div');
      inlineErrorEl.className = 'site-order-invoice-error';
      const parent = cuitInput.parentElement;
      if (parent) parent.appendChild(inlineErrorEl);
    }
    inlineErrorEl.textContent = message || 'Revisá el CUIT ingresado.';
    inlineErrorEl.classList.remove('ui-is-hidden');
    cuitInput.focus();
    cuitInput.select?.();
  };

  if (cuitInput) {
    cuitInput.addEventListener('input', clearFieldError);
    cuitInput.addEventListener('focus', clearFieldError);
  }
  if (ivaSelect) {
    ivaSelect.addEventListener('change', clearFieldError);
  }


  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const action = form.getAttribute('action') || '';
    if (!action) return;

    const btn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (btn) btn.disabled = true;

    overlay.showLoading({ title: 'Validando CUIT', text: 'Estamos validando la información…' });

    const fd = new FormData(form);
    const body = new URLSearchParams();
    for (const [k, v] of fd.entries()) body.append(k, String(v));

    // Timeout “decente” para cliente: no eterno, no instantáneo.
    const controller = new AbortController();
    const timeoutMs = 15000;
    const t = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const res = await fetch(action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
          'X-Requested-With': 'fetch'
        },
        body: body.toString(),
        credentials: 'same-origin',
        signal: controller.signal
      });

      const data = await res.json().catch(() => ({}));

      const redirect = (data && (data.redirect_url || data.redirect)) ? String(data.redirect_url || data.redirect) : '';
      const status = (data && data.status) ? String(data.status) : (res.ok ? 'pending' : 'invalid');
      const msg = (data && data.message) ? String(data.message) : (res.ok ? 'Solicitud registrada.' : 'No pudimos validar el CUIT.');

      if (!res.ok) {
        overlay.showResult({
          type: 'error',
          title: 'Validación inválida',
          text: msg,
          onClose: () => {
            // No persistimos solicitud: el usuario debe corregir y reintentar.
            overlay.hide();
            showFieldError(msg);
          }
        });
        return;
      }

      if (status === 'valid') {
        overlay.showResult({
          type: 'success',
          title: 'Validación OK',
          text: msg,
          onClose: () => {
            if (redirect) window.location.href = redirect;
            else window.location.reload();
          }
        });
        return;
      }

      // pending
      overlay.showResult({
        type: 'info',
        title: 'Validación pendiente',
        text: msg,
        onClose: () => {
          if (redirect) window.location.href = redirect;
          else window.location.reload();
        }
      });
    } catch (e) {
      const isAbort = (e && (e.name === 'AbortError'));
      overlay.showResult({
        type: 'info',
        title: 'Validación pendiente',
        text: isAbort ? 'ARCA está tardando en responder. La solicitud quedó registrada y será revisada.' : 'Ocurrió un error al validar. La solicitud quedó registrada y será revisada.',
        onClose: () => window.location.reload()
      });
    } finally {
      clearTimeout(t);
      if (btn) btn.disabled = false;
    }
  });
}
