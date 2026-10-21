import { showProcessing, showProcessingResult, hideProcessing } from '../core/processing_overlay.js';
import { pushToast } from '../../core/flash.js';

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

async function postJSON(url, payload) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': csrfToken(),
    },
    body: JSON.stringify(payload || {}),
    credentials: 'same-origin',
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data || data.ok !== true) {
    const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'No se pudo completar la acción.';
    throw new Error(msg);
  }
  return data;
}

export function init() {
  const btn = document.getElementById('correo-test-btn');
  if (!btn) return;

  const endpoint = btn.dataset.endpoint || '';
  if (!endpoint) return;

  btn.addEventListener('click', async () => {
    try {
      showProcessing({ title: 'Probando conexión', text: 'Validando credenciales y conectividadâ€¦' });
      const data = await postJSON(endpoint, {});

      hideProcessing();
      showProcessingResult({
        ok: true,
        title: 'Conexión OK',
        text: data.summary || 'La API respondió correctamente.',
      });
      pushToast({ type: 'ok', text: 'Conexión OK.' });
    } catch (e) {
      hideProcessing();
      showProcessingResult({
        ok: false,
        title: 'No se pudo conectar',
        text: e && e.message ? e.message : 'Revisá las credenciales y el modo (test/prod).',
      });
      pushToast({ type: 'error', text: 'Error de conexión.' });
    }
  });
}

