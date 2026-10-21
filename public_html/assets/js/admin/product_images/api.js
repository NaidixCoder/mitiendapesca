// admin/product_images/api.js
import { ACCEPT_HEADER } from './config.js';

/**
 * Normaliza respuestas del backend para upload.
 * @returns {Promise<{ok:boolean,status:number,html?:string,kind:'json'|'figure'|'page'|'unknown', item?:any, figureEl?:Element}>}
 */
export async function requestUpload(url, fields, file, { signal, onBeforeFetch } = {}) {
  const fd = new FormData();
  for (const k in (fields||{})) fd.append(k, fields[k]);
  fd.append('file', file);

  onBeforeFetch?.(url, fd);

  const res = await fetch(url, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin',
    headers: { 'Accept': ACCEPT_HEADER },
    signal
  });

  const text = await res.text();
  const out = { ok: res.ok, status: res.status, html: text, kind: 'unknown' };

  if (text) {
    const t = text.trim();
    // Preferir JSON
    if (t.startsWith('{') || t.startsWith('[')) {
      try {
        const j = JSON.parse(t);
        out.json = j;
        out.item = j?.item || j?.data || j;
        out.kind = 'json';
        return out;
      } catch {}
    }
    // <figure> suelto
    const tpl = document.createElement('template');
    tpl.innerHTML = t;
    const fig = tpl.content.querySelector('figure');
    if (fig) {
      out.figureEl = fig;
      out.kind = 'figure';
      return out;
    }
    // Página completa con #img-gallery
    try {
      const doc = new DOMParser().parseFromString(t, 'text/html');
      if (doc.getElementById('img-gallery')) {
        out.kind = 'page';
        return out;
      }
      // Heurística de error renderizado
      if (doc.querySelector('.alert, .error, .error-msg')) {
        out.ok = false;
      }
    } catch {}
  }
  return out;
}

/**
 * Normaliza respuestas para acciones (delete/cover)
 */
export async function requestAction(url, formData, { signal, onBeforeFetch } = {}) {
  onBeforeFetch?.(url, formData);
  const res = await fetch(url, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin',
    headers: { 'Accept': ACCEPT_HEADER },
    signal
  });
  const text = await res.text();
  let ok = res.ok;
  let kind = 'unknown';
  let json = null;
  try {
    const j = JSON.parse(text);
    json = j;
    if (j && j.ok === false) ok = false;
    kind = 'json';
  } catch {
    try {
      const doc = new DOMParser().parseFromString(text, 'text/html');
      if (doc.getElementById('img-gallery')) kind = 'page';
      if (doc.querySelector('.alert, .error, .error-msg')) ok = false;
    } catch {}
  }
  return { ok, status: res.status, html: text, kind, json };
}
