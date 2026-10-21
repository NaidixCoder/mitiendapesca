// Utilidades específicas para Admin > Productos > Variantes

export function appUrl(path) {
  // Respeta el base path (subcarpeta) del proyecto cuando existe.
  // Si el path ya es absoluto, se deja como está.
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const base = (window.__APP_BASE_URL || '').replace(/\/+$/, '');
  if (!base) return path;
  if (path.startsWith('/')) return base + path;
  return base + '/' + path;
}

export async function fetchJson(url, opts = {}) {
  const resp = await fetch(appUrl(url), {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      ...(opts.headers || {}),
    },
    ...opts,
  });

  const ct = (resp.headers.get('content-type') || '').toLowerCase();
  const data = ct.includes('application/json') ? await resp.json() : await resp.text();

  if (!resp.ok) {
    const err = new Error(`HTTP ${resp.status}`);
    err.status = resp.status;
    err.data = data;
    throw err;
  }

  return data;
}

export async function postForm(url, data = {}) {
  const fd = new FormData();

  const csrf = getCsrfToken();
  // Compat con helpers.php: verify_csrf_or_abort() espera _csrf (o csrf) o header X-CSRF-Token.
  if (csrf) fd.append('_csrf', csrf);

  Object.entries(data).forEach(([k, v]) => {
    if (v === undefined || v === null) return;
    fd.append(k, String(v));
  });

  const resp = await fetch(appUrl(url), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrf ? { 'X-CSRF-Token': csrf } : {}),
    },
    body: fd,
  });

  const ct = (resp.headers.get('content-type') || '').toLowerCase();
  const body = ct.includes('application/json') ? await resp.json() : await resp.text();

  if (!resp.ok) {
    const err = new Error(`HTTP ${resp.status}`);
    err.status = resp.status;
    err.data = body;
    throw err;
  }

  // Avisar al wizard para actualizar badges (sin recargar).
  try { document.dispatchEvent(new CustomEvent('tp:wizard:refresh')); } catch (_) {}

  // Avisar que cambiaron las variantes (para refrescar selects en Imágenes sin F5).
  // Tomamos product_id si viene en el payload.
  try {
    const pid = data.product_id ?? data.productId ?? null;
    document.dispatchEvent(new CustomEvent('tp:variants:changed', { detail: { productId: pid } }));
  } catch (_) {}

  return body;
}

export function setConfigCollapsed(productId, collapsed) {
  try {
    localStorage.setItem(`pv:config:collapsed:${productId}`, collapsed ? '1' : '0');
  } catch (_) {}
}

export function withId(url, id) {
  return url + (url.includes('?') ? '&' : '?') + 'id=' + encodeURIComponent(String(id));
}

export function flashEl(el, className = 'pv-flash') {
  try {
    if (!(el instanceof HTMLElement)) return;
    el.classList.add(className);
    window.setTimeout(() => el.classList.remove(className), 650);
  } catch (_) {}
}

export function cssEscape(s) {
  try { return CSS.escape(String(s)); } catch { return String(s).replace(/[^a-z0-9_-]/ig, ''); }
}

function getCsrfToken() {
  // Prioridad: variable global seteada por el layout.
  if (window.__CSRF_TOKEN) return window.__CSRF_TOKEN;

  // Fallback: meta tag.
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}
