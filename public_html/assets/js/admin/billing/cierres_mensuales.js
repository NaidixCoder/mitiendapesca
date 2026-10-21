import { pushToast } from '../../core/flash.js';
import { confirmModal } from '../core/confirm_modal.js';

function csrfToken() {
  const el = document.querySelector('meta[name="csrf-token"]');
  return el ? (el.getAttribute('content') || '') : '';
}

async function postForm(url, data) {
  const fd = new FormData();
  Object.entries(data || {}).forEach(([k, v]) => fd.append(k, String(v ?? '')));

  const resp = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: fd,
  });

  const json = await resp.json().catch(() => ({}));
  return { ok: resp.ok, status: resp.status, json };
}

export function init() {
  const btn = document.getElementById('btn-close-period');
  const inp = document.getElementById('closure-period');

  if (!btn || !inp) return;

  btn.addEventListener('click', async () => {
    const period = (inp.value || '').trim(); // YYYY-MM
    if (!/^[0-9]{4}-[0-9]{2}$/.test(period)) {
      pushToast('Elegí un período válido (YYYY-MM).', 'error');
      return;
    }

    const ok = await confirmModal({
      title: 'Cerrar período',
      message: `Vas a cerrar el período ${period}. Luego los reportes leerán el snapshot y no se recalculará ese mes. ¿Continuar?`,
      confirmText: 'Sí, cerrar',
      cancelText: 'Cancelar',
    });

    if (!ok) return;

    btn.disabled = true;
    btn.classList.add('av3-opacity-70');

    try {
      const url = btn.getAttribute('data-close-url') || '';
      const { ok: httpOk, json } = await postForm(url, {
        _csrf: csrfToken(),
        period,
      });

      if (!httpOk || !json.ok) {
        pushToast((json && json.message) ? json.message : 'No se pudo cerrar el período.', 'error');
        return;
      }

      pushToast(json.message || 'Período cerrado.', 'success');
      window.location.reload();
    } catch (e) {
      console.warn(e);
      pushToast('Error inesperado al cerrar el período.', 'error');
    } finally {
      btn.disabled = false;
      btn.classList.remove('av3-opacity-70');
    }
  });
}

