// Mercado Pago Bricks (Checkout embebido)
// Reglas del proyecto: sin JS inline, todo por módulos.

function qs(sel, root = document) { return root.querySelector(sel); }

function getMeta(name) {
  const el = document.querySelector(`meta[name="${name}"]`);
  return el ? (el.getAttribute('content') || '') : '';
}

function setStatus(el, msg, type = 'info') {
  if (!el) return;
  el.textContent = msg || '';
  el.className = 'site-checkout-feedback' + (
    type === 'error' ? 'site-checkout-feedback--error' :
    type === 'ok' ? 'site-checkout-feedback--success' :
    'site-checkout-feedback--neutral'
  );
}

async function postJson(url, payload, csrf) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(csrf ? { 'X-CSRF-Token': csrf } : {}),
    },
    credentials: 'same-origin',
    body: JSON.stringify(payload || {}),
  });

  let data = null;
  try { data = await res.json(); } catch { data = null; }

  if (!res.ok) {
    const err = (data && (data.error || data.message)) ? (data.error || data.message) : 'Error inesperado.';
    const e = new Error(err);
    e.status = res.status;
    e.data = data;
    throw e;
  }
  return data;
}

export function init() {
  const form = qs('#checkout-payment');
  if (!form) return;

  const btn = qs('#btn-mp-start', form);
  const statusEl = qs('#mp-status', form);
  const brickContainer = qs('#mp-brick-container', form);
  const fallbackWrap = qs('#mp-fallback', form);

  const preferenceUrl = form.dataset.preferenceUrl || '';
  const mpPublicKey = getMeta('mp-public-key');
  const csrf = getMeta('csrf-token');

  if (!preferenceUrl) {
    setStatus(statusEl, 'No se configuró el endpoint de pago.', 'error');
    fallbackWrap?.classList.remove('ui-is-hidden');
    return;
  }

  if (!mpPublicKey) {
    setStatus(statusEl, 'Falta configurar MP_PUBLIC_KEY.', 'error');
    fallbackWrap?.classList.remove('ui-is-hidden');
    return;
  }

  if (typeof window.MercadoPago !== 'function') {
    // El SDK se inyecta desde el controller vía extraHead.
    setStatus(statusEl, 'Cargando Mercado Pago… Si no aparece, recargá la página.', 'info');
    // Si en unos segundos no está, mostramos fallback.
    window.setTimeout(() => {
      if (typeof window.MercadoPago !== 'function') {
        setStatus(statusEl, 'No se pudo cargar Mercado Pago. Podés pagar en ventana nueva.', 'error');
        fallbackWrap?.classList.remove('ui-is-hidden');
      }
    }, 2500);
  }

  let brickMounted = false;

  btn?.addEventListener('click', async () => {
    try {
      setStatus(statusEl, '', 'info');
      fallbackWrap?.classList.add('ui-is-hidden');

      const checked = form.querySelector('input[name="address_id"]:checked');
      const addressId = checked ? parseInt(checked.value, 10) : 0;
      if (!addressId) {
        setStatus(statusEl, 'Seleccioná una dirección para continuar.', 'error');
        return;
      }

      btn.disabled = true;
      btn.classList.add('site-opacity-70');
      setStatus(statusEl, 'Iniciando pago…', 'info');

      const resp = await postJson(preferenceUrl, { address_id: addressId }, csrf);
      const preferenceId = resp && resp.preferenceId ? String(resp.preferenceId) : '';
      if (!preferenceId) {
        throw new Error('No se pudo iniciar el pago.');
      }

      if (typeof window.MercadoPago !== 'function') {
        throw new Error('No se pudo cargar Mercado Pago.');
      }

      // Re-render seguro
      if (brickContainer) brickContainer.innerHTML = '';

      const mp = new window.MercadoPago(mpPublicKey, { locale: 'es-AR' });
      const bricksBuilder = mp.bricks();

      // Wallet Brick (Opción A)
      await bricksBuilder.create('wallet', 'mp-brick-container', {
        initialization: { preferenceId },
        callbacks: {
          onReady: () => {
            brickMounted = true;
            setStatus(statusEl, 'Completá el pago para finalizar. (El pedido se crea cuando Mercado Pago lo aprueba)', 'info');
          },
          onError: (err) => {
            console.error('MP Brick error', err);
            setStatus(statusEl, 'Ocurrió un error al cargar el pago. Podés pagar en ventana nueva.', 'error');
            fallbackWrap?.classList.remove('ui-is-hidden');
          },
        },
      });

    } catch (e) {
      console.error(e);
      const msg = (e && e.message) ? e.message : 'No se pudo iniciar el pago. Probá nuevamente.';
      setStatus(statusEl, msg, 'error');
      fallbackWrap?.classList.remove('ui-is-hidden');
    } finally {
      btn.disabled = false;
      btn.classList.remove('site-opacity-70');
    }
  });

  // Si el usuario recarga y el SDK falla, que tenga salida
  if (!brickMounted) {
    // No mostramos fallback de entrada (solo si hay error)
  }
}
