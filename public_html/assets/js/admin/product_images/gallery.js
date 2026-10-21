// admin/product_images/gallery.js
import { FOCUS_AFTER_ACTION } from './config.js';

export const getGallery = () => document.getElementById('img-gallery');

export function insertFigure(fig) {
  const g = getGallery();
  if (g && fig) g.prepend(fig);
}

export function replaceGalleryFromHtml(html) {
  try {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const newGal = doc.getElementById('img-gallery');
    const cur = getGallery();
    if (cur && newGal) {
      cur.replaceWith(newGal);
      try {
        document.dispatchEvent(new CustomEvent('img-gallery-replaced'));
      } catch (_) {}
      return true;
    }
  } catch {}
  return false;
}

export async function softRefreshGallery() {
  try {
    const r = await fetch(location.href, { credentials: 'same-origin' });
    const html = await r.text();
    return replaceGalleryFromHtml(html);
  } catch { return false; }
}

export function unmarkAllCovers(container) {
  const g = container || getGallery();
  if (!g) return;
  g.querySelectorAll('figure.is-cover').forEach(el => {
    el.classList.remove('is-cover','ring-2','ring-brand');
    el.querySelector('.cover-badge')?.classList.add('av3-hidden');
    const b = el.querySelector('.js-cover');
    if (b) {
      b.disabled = false;
      b.classList.remove('av3-opacity-60','av3-pointer-events-none');
      b.textContent = 'Hacer portada';
    }
  });
}


export function markAsCover(fig) {
  if (!fig) return;
  fig.classList.add('is-cover', 'ring-2', 'ring-brand');
  // asegurar badge
  let badge = fig.querySelector('.cover-badge');
  if (!badge) {
    badge = document.createElement('span');
    badge.className = 'cover-badge av3-absolute top-2 left-2 av3-text--10px av3-weight-strong av3-px-1-5 av3-py-0-5 av3-rounded av3-bg-brand av3-text-white';
    badge.textContent = 'Portada';
    fig.prepend(badge);
  }
  badge.classList.remove('av3-hidden');
  // botón
  const btn = fig.querySelector('.js-cover');
  if (btn) {
    btn.classList.add('av3-opacity-60', 'av3-pointer-events-none');
    btn.disabled = true;
    btn.textContent = 'Portada';
  }
}



export function focusAfterRemove(removedFig) {
  if (!FOCUS_AFTER_ACTION) return;
  const g = getGallery();
  if (!g) return;
  // Intentar siguiente figura, sino anterior
  const all = Array.from(g.querySelectorAll('figure'));
  const idx = all.indexOf(removedFig);
  let target = all[idx] || all[idx-1] || g.querySelector('button, a, [tabindex]');
  // Focus sobre botón principal si existe
  const btn = target?.querySelector?.('button, a, [tabindex]');
  (btn || target)?.focus?.();
}
