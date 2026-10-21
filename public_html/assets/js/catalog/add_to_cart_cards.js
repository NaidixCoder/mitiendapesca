// Catalogo: agregar al carrito desde card + Quick Add para variantes
import { flyToCart, showToast, buttonLoading, buttonSuccessFlash, setCartCount } from '../ui/cart_feedback.js?v=160';

function meta(name) {
  const el = document.querySelector(`meta[name="${name}"]`);
  return el ? (el.getAttribute('content') || '') : '';
}

function appBase() {
  const base = meta('app-base-url') || '/';
  return base.endsWith('/') ? base : `${base}/`;
}

function buildUrl(path) {
  const normalized = (path || '').replace(/^\//, '');
  return new URL(normalized, appBase()).toString();
}

async function postFormAjax(form, { fromEl = null, originImg = null } = {}) {
  const formData = new FormData(form);
  formData.set('_ajax', '1');

  const res = await fetch(form.action, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
    },
    credentials: 'same-origin',
  });

  const contentType = (res.headers.get('content-type') || '').toLowerCase();
  if (!contentType.includes('application/json')) {
    throw new Error('No se pudo agregar. Actualizá la página e intentá de nuevo.');
  }

  const data = await res.json().catch(() => null);
  if (!res.ok || !data || data.ok !== true) {
    const msg = (data && data.message) ? data.message : 'No se pudo agregar al carrito.';
    throw new Error(msg);
  }

  const totalQty = (data && data.cart && typeof data.cart.total_qty !== 'undefined') ? Number(data.cart.total_qty) : NaN;
  if (Number.isFinite(totalQty)) {
    setCartCount(totalQty);
  }

  if (fromEl || originImg) {
    flyToCart({
      fromEl: fromEl || originImg,
      imgSrc: originImg ? (originImg.currentSrc || originImg.src || '') : '',
    });
  }

  return data;
}

function modalEls() {
  return {
    modal: document.getElementById('quick-add-modal'),
    title: document.getElementById('quick-add-title'),
    body: document.getElementById('quick-add-body'),
  };
}

function openModal() {
  const { modal } = modalEls();
  if (!modal) return;
  modal.classList.remove('ui-is-hidden');
  modal.setAttribute('aria-hidden', 'false');
  document.documentElement.classList.add('site-overflow-hidden');
}

function closeModal() {
  const { modal } = modalEls();
  if (!modal) return;
  modal.classList.add('ui-is-hidden');
  modal.setAttribute('aria-hidden', 'true');
  document.documentElement.classList.remove('site-overflow-hidden');
}

function applyVariantState({ body, select, originImg }) {
  const thumbEl = body.querySelector('[data-quick-add-thumb]');
  const optionValueEl = body.querySelector('[data-quick-add-option-value]');
  const variantExtraEl = body.querySelector('[data-quick-add-variant-extra]');
  const financedPriceEl = body.querySelector('[data-quick-add-financed-price]');
  const installmentEl = body.querySelector('[data-quick-add-installment-inline]');
  const transferPriceEl = body.querySelector('[data-quick-add-transfer-price]');
  const discountBadgeEl = body.querySelector('[data-quick-add-discount-badge]');

  const opt = select.selectedOptions && select.selectedOptions[0];
  if (!opt) return;

  const thumb = opt.getAttribute('data-thumb') || '';
  const attributeValue = opt.getAttribute('data-attribute-value') || '';
  const variantExtra = opt.getAttribute('data-variant-extra') || '';
  const financedPrice = opt.getAttribute('data-financed-price') || '';
  const installmentInline = opt.getAttribute('data-installment-inline') || '';
  const transferPrice = opt.getAttribute('data-transfer-price') || '';
  const discountPct = parseInt(opt.getAttribute('data-discount-pct') || '0', 10);

  if (thumbEl) {
    if (thumb) {
      thumbEl.setAttribute('src', thumb);
    } else if (originImg) {
      const src = originImg.currentSrc || originImg.src;
      if (src) thumbEl.setAttribute('src', src);
    }
  }

  if (optionValueEl && attributeValue) {
    optionValueEl.textContent = attributeValue;
  }
  if (variantExtraEl) {
    const cleanOption = (attributeValue || '').trim().toLowerCase();
    const cleanExtra = (variantExtra || '').trim();
    if (cleanExtra !== '' && cleanExtra.toLowerCase() !== cleanOption) {
      variantExtraEl.textContent = cleanExtra;
      variantExtraEl.hidden = false;
    } else {
      variantExtraEl.textContent = '';
      variantExtraEl.hidden = true;
    }
  }

  if (financedPriceEl && financedPrice) {
    financedPriceEl.textContent = financedPrice;
  }

  if (transferPriceEl && transferPrice) {
    transferPriceEl.textContent = transferPrice;
  }

  if (installmentEl) {
    if (installmentInline) {
      installmentEl.textContent = installmentInline;
      installmentEl.hidden = false;
    } else {
      installmentEl.textContent = '';
      installmentEl.hidden = true;
    }
  }
  if (discountBadgeEl) {
    if (Number.isFinite(discountPct) && discountPct > 0) {
      discountBadgeEl.textContent = `-${discountPct}%`;
      discountBadgeEl.hidden = false;
    } else {
      discountBadgeEl.textContent = '';
      discountBadgeEl.hidden = true;
    }
  }
}

async function loadQuickAdd(productId, originImg) {
  const { title, body } = modalEls();
  if (!body) return;

  body.innerHTML = '<div class="catalog-quick-add__loading">Cargando...</div>';
  if (title) title.textContent = 'Producto';

  const url = buildUrl(`/productos/quick-add?product_id=${encodeURIComponent(productId)}`);
  const res = await fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });
  const html = await res.text();
  body.innerHTML = html;

  const select = body.querySelector('select[name="variant_id"]');
  if (select) {
    select.addEventListener('change', () => applyVariantState({ body, select, originImg }));
    applyVariantState({ body, select, originImg });
  }

  const titleNameEl = body.querySelector('[data-quick-add-name]');
  if (title && titleNameEl) {
    title.textContent = titleNameEl.textContent || 'Producto';
  }

  body.querySelectorAll('[data-quick-add-close]').forEach((btn) => {
    if (btn.__bound) return;
    btn.__bound = true;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      closeModal();
    });
  });

  const form = body.querySelector('form[data-quick-add-form]');
  if (form && !form.__bound) {
    form.__bound = true;

    const qtyInput = form.querySelector('input[name="qty"]');
    const dec = form.querySelector('[data-qty-dec]');
    const inc = form.querySelector('[data-qty-inc]');

    if (qtyInput) {
      const clamp = () => {
        let value = parseInt(qtyInput.value, 10);
        if (!Number.isFinite(value) || value < 1) value = 1;
        qtyInput.value = String(value);
      };

      qtyInput.addEventListener('change', clamp);

      if (dec) {
        dec.addEventListener('click', () => {
          const value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) - 1);
          qtyInput.value = String(value);
        });
      }

      if (inc) {
        inc.addEventListener('click', () => {
          const value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) + 1);
          qtyInput.value = String(value);
        });
      }
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const feedback = body.querySelector('[data-quick-add-feedback]');
      if (feedback) feedback.textContent = '';

      const submitBtn = form.querySelector('button[type="submit"]');
      const restoreBtn = submitBtn ? buttonLoading(submitBtn) : null;

      try {
        await postFormAjax(form, { fromEl: submitBtn, originImg });
        if (restoreBtn) restoreBtn();
        if (submitBtn) buttonSuccessFlash(submitBtn, 'Agregado');
        closeModal();
      } catch (err) {
        if (restoreBtn) restoreBtn();
        if (feedback) feedback.textContent = err?.message || 'No se pudo agregar.';
        showToast('No se pudo agregar', err?.message || null);
      }
    });
  }
}

function bind() {
  if (!document.__tpCatalogAddBound) {
    document.__tpCatalogAddBound = true;

    document.addEventListener('submit', async (e) => {
      const form = e.target && e.target.matches ? (e.target.matches('form[data-cart-add-card]') ? e.target : null) : null;
      if (!form) return;
      e.preventDefault();

      const card = form.closest('.js-card') || form.closest('article');
      const img = card ? card.querySelector('img') : null;
      const submitBtn = form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
      const restoreBtn = submitBtn ? buttonLoading(submitBtn) : null;

      try {
        await postFormAjax(form, { fromEl: submitBtn, originImg: img });
        if (restoreBtn) restoreBtn();
        if (submitBtn) buttonSuccessFlash(submitBtn, 'Agregado');
      } catch (err) {
        if (restoreBtn) restoreBtn();
        const msg = err?.message || 'No se pudo agregar al carrito.';
        showToast('No se pudo agregar', msg);
      }
    }, true);

    document.addEventListener('click', async (e) => {
      const btn = e.target && e.target.closest ? e.target.closest('[data-quick-add-trigger]') : null;
      if (!btn) return;
      e.preventDefault();

      const productId = btn.getAttribute('data-product-id') || '';
      if (!productId) return;

      const card = btn.closest('.js-card') || btn.closest('article');
      const img = card ? card.querySelector('img') : null;

      openModal();
      await loadQuickAdd(productId, img);
    });
  }

  const { modal } = modalEls();
  if (modal && !modal.__closeBound) {
    modal.__closeBound = true;
    modal.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target instanceof Element)) return;
      if (target === modal || target.closest('[data-quick-add-close]')) {
        closeModal();
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
  }
}

export function init() {
  bind();
  const grid = document.getElementById('catalog-grid');
  if (grid && !grid.__mo) {
    grid.__mo = new MutationObserver(() => bind());
    grid.__mo.observe(grid, { childList: true, subtree: true });
  }
}
