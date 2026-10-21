// Checkout - Transferencia bancaria (submit UX)
// Reglas: sin JS inline, todo por modulos.

function qs(sel, root = document) { return root.querySelector(sel); }

function showOverlay(title, text) {
  const ov = document.getElementById('mp-overlay');
  if (!ov) return;
  const t = document.getElementById('mp-overlay-title');
  const p = document.getElementById('mp-overlay-text');
  if (t && title) t.textContent = title;
  if (p && text) p.textContent = text;
  ov.classList.remove('ui-is-hidden');
}

function getCsrfToken() {
  const el = document.querySelector('meta[name="csrf-token"]');
  return el ? String(el.getAttribute('content') || '') : '';
}

async function resendVerificationEmail(btn) {
  const csrf = getCsrfToken();
  if (!csrf) return;
  const resendUrl = String(btn.getAttribute('data-resend-url') || '').trim();
  if (!resendUrl) return;

  const original = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Enviando...';

  try {
    const body = new URLSearchParams();
    body.set('_csrf', csrf);

    const res = await fetch(resendUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrf,
      },
      body: body.toString(),
    });

    const data = await res.json().catch(() => null);
    if (res.status === 429) {
      const retry = data && data.retry_after ? Number(data.retry_after) : 0;
      const minutes = retry > 0 ? Math.max(1, Math.ceil(retry / 60)) : 30;
      btn.disabled = false;
      btn.textContent = `Espera ${minutes} min`;
      return;
    }

    if (!res.ok || !(data && data.ok)) {
      throw new Error((data && data.error) ? data.error : 'request_failed');
    }

    btn.textContent = 'Email reenviado';
  } catch (_) {
    btn.disabled = false;
    btn.textContent = original;
  }
}

export function init() {
  const form = qs('#checkout-payment');
  const btn = qs('#btn-transfer-confirm');
  const resendBtn = qs('[data-transfer-resend-email]');
  const refreshBtn = qs('[data-transfer-refresh-email]');

  if (!form || !btn) return;

  if (resendBtn) {
    resendBtn.addEventListener('click', () => {
      resendVerificationEmail(resendBtn);
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => {
      const refreshUrl = String(refreshBtn.getAttribute('data-refresh-url') || '').trim();
      if (!refreshUrl) return;
      window.location.href = refreshUrl;
    });
  }

  btn.addEventListener('click', (e) => {
    if (btn.disabled) {
      e.preventDefault();
      return;
    }

    try { window.checkoutSetProcessing && window.checkoutSetProcessing(true); } catch (_) {}

    try {
      showOverlay('Confirmando pedido', 'Estamos generando tu pedido por transferencia. No cierres esta ventana...');
      btn.disabled = true;
      btn.classList.add('is-busy');
    } catch (_) {}

    e.preventDefault();
    requestAnimationFrame(() => {
      try {
        const target = (btn.getAttribute('formaction') || btn.formAction || '').trim();
        if (target) form.action = target;
      } catch (_) {}
      try { form.submit(); } catch (_) {}
    });
  });
}
