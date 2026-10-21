// Producto: agregar al carrito con feedback visual pro
// Respeta reglas: JS en archivos separados; vista tonta.
import { flyToCart, showToast, setCartCount, buttonLoading, buttonSuccessFlash } from '../ui/cart_feedback.js?v=160';

async function postFormAjax(form, { fromEl = null, originImg = null } = {}) {
  const fd = new FormData(form);
  fd.set('_ajax', '1');

  const res = await fetch(form.action, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    },
    body: fd,
    credentials: 'same-origin',
  });

  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) {
    throw new Error('No se pudo agregar. Actualiza la pagina e intenta de nuevo.');
  }

  const data = await res.json();
  if (!res.ok || !data || data.ok !== true) {
    const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'No se pudo agregar.';
    throw new Error(msg);
  }

  if (data.cart && typeof data.cart.total_qty !== 'undefined') {
    setCartCount(data.cart.total_qty);
  }

  if (fromEl || originImg) {
    flyToCart({
      fromEl: fromEl || originImg,
      imgSrc: originImg ? (originImg.currentSrc || originImg.src || '') : '',
    });
  }
}

function pickOriginImage() {
  return (
    document.querySelector('[data-product-main-img]') ||
    document.querySelector('.product-gallery__slide-image') ||
    document.querySelector('.c-gallery img') ||
    document.querySelector('main img')
  );
}

export function init() {
  const form = document.getElementById('buy-form');
  if (!form || form.__tpAddBound) return;
  form.__tpAddBound = true;

  form.addEventListener('submit', async (e) => {
    const submitter = e.submitter;
    if (submitter && submitter.name === 'buy_now') return;

    e.preventDefault();
    const btn = submitter instanceof HTMLButtonElement ? submitter : form.querySelector('button[type="submit"]');
    const originImg = pickOriginImage();
    const restoreBtn = btn ? buttonLoading(btn, 'Agregando...') : null;

    try {
      await postFormAjax(form, { fromEl: btn, originImg });
      if (btn) buttonSuccessFlash(btn, 'Agregado');
    } catch (err) {
      showToast('No se pudo agregar', err?.message || 'Proba de nuevo.', []);
    } finally {
      if (restoreBtn) restoreBtn();
    }
  }, true);
}
