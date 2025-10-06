// Admin product images module
export function init() {
  if (!(document.body && document.body.dataset.page === 'admin-product-form')) return;
  (function(){
// --- original product_images.js start ---
// Subida AJAX + acciones de galería (portada/eliminar) con fallback
(function () {
  const msg = (el, t, ok = true) => {
    if (!el) return;
    el.textContent = t || '';
    el.style.color = ok ? 'var(--muted)' : 'var(--danger)';
  };

  // --- Subida ---
  const upForm = document.getElementById('img-form');
  const upMsg = document.getElementById('img-msg');

  if (upForm) {
    upForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      msg(upMsg, 'Subiendo...');
      const fd = new FormData(upForm);
      try {
        const res = await fetch(upForm.action, { method: 'POST', body: fd });
        const ct = (res.headers.get('content-type') || '');
        const isJson = ct.includes('application/json');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = isJson ? await res.json() : null;
        if (data && data.ok) {
          msg(upMsg, 'Imagen subida.');
          location.reload();
        } else if (data && data.error) {
          msg(upMsg, data.error, false);
        } else {
          location.reload();
        }
      } catch (err) {
        msg(upMsg, 'Error al subir: ' + (err.message || 'desconocido'), false);
      }
    });
  }

  // --- Acciones de galería ---
  document.addEventListener('submit', async (e) => {
    const f = e.target;
    if (!f.matches('.img-action')) return;
    e.preventDefault();
    try {
      const fd = new FormData(f);
      const res = await fetch(f.action, { method: 'POST', body: fd });
      const ct = (res.headers.get('content-type') || '');
      const isJson = ct.includes('application/json');
      if (!res.ok) throw new Error('HTTP ' + res.status);
      if (isJson) {
        const data = await res.json();
        if (data.ok) return location.reload();
        if (data.error) return alert(data.error);
      }
      location.reload();
    } catch (err) {
      alert('Error: ' + (err.message || 'desconocido'));
    }
  });
})();

// --- original product_images.js end ---
  })();
}
