// UI: feedback pro al agregar al carrito.
// El markup base vive en un partial + CSS; este archivo solo maneja comportamiento.

const TOAST_DEBUG_STICKY = false;

function getToastHost() {
  return document.getElementById('tp-toast-host');
}

function getToastTemplate() {
  return document.getElementById('tp-toast-template');
}

function isVisible(el) {
  if (!el || !(el instanceof Element)) return false;
  const style = window.getComputedStyle(el);
  if (style.display === 'none' || style.visibility === 'hidden') return false;
  // Para elementos fixed, offsetParent puede ser null; usamos rects.
  return el.getClientRects().length > 0;
}

function pickVisible(list) {
  for (const el of list) {
    if (isVisible(el)) return el;
  }
  return list[0] || null;
}

function getCartIcon() {
  const desktopQuery = '(min-width: 767px)';
  const preferredSelector = window.matchMedia(desktopQuery).matches
    ? '[data-cart-icon][data-cart-icon-context="desktop"]'
    : '[data-cart-icon][data-cart-icon-context="mobile"]';

  const preferredIcons = Array.from(document.querySelectorAll(preferredSelector));
  if (preferredIcons.length) {
    const preferredVisible = pickVisible(preferredIcons);
    if (preferredVisible) return preferredVisible;
  }

  const icons = Array.from(document.querySelectorAll('[data-cart-icon]'));
  if (icons.length) return pickVisible(icons);

  const links = Array.from(document.querySelectorAll('a[href*="/carrito"], a[href$="carrito"], a[href*="carrito"]'));
  return pickVisible(links);
}

function setCartCount(totalQty) {
  const badges = Array.from(document.querySelectorAll('[data-cart-count]'));
  if (!badges.length) return;

  const n = Math.max(0, parseInt(totalQty, 10) || 0);
  const txt = n > 99 ? '99+' : String(n);

  for (const badge of badges) {
    badge.textContent = txt;
    badge.classList.toggle('ui-is-hidden', n <= 0);
    badge.classList.toggle('ph-is-hidden', n <= 0);
  }
}

function pulseCart() {
  const icon = getCartIcon();
  if (!icon) return;
  const pulseTarget = icon.querySelector('.ph-icon') || icon;
  pulseTarget.classList.remove('tp-cart-pulse');
  // Force reflow
  void pulseTarget.offsetWidth;
  pulseTarget.classList.add('tp-cart-pulse');
  setTimeout(() => pulseTarget.classList.remove('tp-cart-pulse'), 420);
}

// Compat: algunos módulos antiguos llaman flyToCart({ originEl })
export function flyToCart({ fromEl, originEl, originImg, imgSrc } = {}) {
  try {
    const icon = getCartIcon();
    const sourceEl = fromEl || originEl || originImg;
    if (!sourceEl || !icon) return;

    const fromRect = sourceEl.getBoundingClientRect();
    const toRect = icon.getBoundingClientRect();
    if (!fromRect.width && !fromRect.height) return;

    const resolvedImg = imgSrc || (originImg ? (originImg.currentSrc || originImg.src) : '') || (sourceEl ? (sourceEl.currentSrc || sourceEl.src) : '') || '';

    const startX = fromRect.left + fromRect.width / 2;
    const startY = fromRect.top + fromRect.height / 2;
    const endX = toRect.left + toRect.width / 2;
    const endY = toRect.top + toRect.height / 2;

    const ghostSize = 64;
    const ghost = document.createElement('div');
    ghost.style.position = 'fixed';
    ghost.style.left = `${startX - (ghostSize / 2)}px`;
    ghost.style.top = `${startY - (ghostSize / 2)}px`;
    ghost.style.width = `${ghostSize}px`;
    ghost.style.height = `${ghostSize}px`;
    ghost.style.zIndex = '9999';
    ghost.style.pointerEvents = 'none';
    ghost.style.borderRadius = '9999px';
    ghost.style.overflow = 'hidden';
    ghost.style.boxShadow = '0 18px 36px rgba(0,0,0,.24)';
    ghost.style.background = '#fff';
    ghost.style.border = '1px solid rgba(255,255,255,.92)';
    ghost.style.transform = 'translate3d(0,0,0) scale(1)';
    ghost.style.opacity = '1';
    ghost.style.transition = 'transform 700ms cubic-bezier(0.2, 0.9, 0.2, 1), opacity 700ms ease';

    if (originImg instanceof HTMLImageElement) {
      const ghostImg = originImg.cloneNode(false);
      ghostImg.removeAttribute('id');
      ghostImg.style.width = '100%';
      ghostImg.style.height = '100%';
      ghostImg.style.display = 'block';
      ghostImg.style.objectFit = 'cover';
      ghostImg.style.pointerEvents = 'none';
      ghost.appendChild(ghostImg);
    } else if (sourceEl instanceof HTMLImageElement) {
      const ghostImg = sourceEl.cloneNode(false);
      ghostImg.removeAttribute('id');
      ghostImg.style.width = '100%';
      ghostImg.style.height = '100%';
      ghostImg.style.display = 'block';
      ghostImg.style.objectFit = 'cover';
      ghostImg.style.pointerEvents = 'none';
      ghost.appendChild(ghostImg);
    } else if (resolvedImg) {
      const ghostImg = document.createElement('img');
      ghostImg.src = resolvedImg;
      ghostImg.alt = '';
      ghostImg.style.width = '100%';
      ghostImg.style.height = '100%';
      ghostImg.style.display = 'block';
      ghostImg.style.objectFit = 'cover';
      ghostImg.style.pointerEvents = 'none';
      ghost.appendChild(ghostImg);
    } else {
      ghost.style.display = 'grid';
      ghost.style.placeItems = 'center';
      ghost.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
    }

    document.body.appendChild(ghost);

    const dx = endX - startX;
    const dy = endY - startY;

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        ghost.style.transform = `translate3d(${dx}px, ${dy}px, 0) scale(0.42)`;
        ghost.style.opacity = '0.8';
      });
    });

    setTimeout(() => {
      try { ghost.remove(); } catch {}
      pulseCart();
    }, 720);
  } catch {
    // noop
  }
}

function normalizeToastArgs(arg1, arg2, arg3) {
  // API nueva:
  // showToast({ message, detail, actionHref, actionText, type })
  // Compat legacy:
  // showToast(message, detail?, actions?)  donde actions = [{label, href}, ...]

  if (typeof arg1 === 'string') {
    const message = arg1;
    const detail = (typeof arg2 === 'string') ? arg2 : '';
    const actions = Array.isArray(arg3) ? arg3 : [];
    const a0 = actions && actions.length ? actions[0] : null;
    const inferredType = /no\s*se\s*pudo|sin\s*stock|error|fall(o|ó)/i.test(message) ? 'error' : 'success';

    return {
      message,
      detail,
      type: inferredType,
      actionHref: a0 ? a0.href : '',
      actionText: a0 ? a0.label : '',
    };
  }

  const o = (arg1 && typeof arg1 === 'object') ? arg1 : {};
  const message = o.message || '';
  const inferredType = /no\s*se\s*pudo|sin\s*stock|error|fall(o|ó)/i.test(message) ? 'error' : 'success';

  return {
    message,
    detail: o.detail || '',
    type: o.type || inferredType,
    actionHref: o.actionHref || '',
    actionText: o.actionText || '',
  };
}

export function showToast(arg1 = {}, arg2 = null, arg3 = null) {
  try {
    const opts = normalizeToastArgs(arg1, arg2, arg3);
    const host = getToastHost();
    const template = getToastTemplate();
    if (!host || !(template instanceof HTMLTemplateElement)) return;

    const isError = (opts.type === 'error');
    const fragment = template.content.cloneNode(true);
    const toast = fragment.querySelector('.tp-toast');
    if (!toast) return;

    toast.classList.add('tp-toast--in');
    if (isError) toast.classList.add('tp-toast--error');

    const successIcon = fragment.querySelector('[data-toast-icon-success]');
    const errorIcon = fragment.querySelector('[data-toast-icon-error]');
    if (successIcon) successIcon.classList.toggle('ui-is-hidden', isError);
    if (errorIcon) errorIcon.classList.toggle('ui-is-hidden', !isError);

    const msg = fragment.querySelector('[data-toast-message]');
    if (msg) msg.textContent = opts.message || (isError ? 'No se pudo agregar' : 'Agregado al carrito');

    const detail = fragment.querySelector('[data-toast-detail]');
    if (detail) {
      if (opts.detail) {
        detail.textContent = opts.detail;
        detail.classList.remove('ui-is-hidden');
      } else {
        detail.textContent = '';
        detail.classList.add('ui-is-hidden');
      }
    }

    const action = fragment.querySelector('[data-toast-action]');
    if (action instanceof HTMLAnchorElement) {
      if (opts.actionHref) {
        action.href = opts.actionHref;
        action.textContent = opts.actionText || 'Ver carrito';
        action.classList.remove('ui-is-hidden');
      } else {
        action.removeAttribute('href');
        action.textContent = '';
        action.classList.add('ui-is-hidden');
      }
    }

    const close = fragment.querySelector('[data-toast-close]');
    if (close instanceof HTMLButtonElement) {
      close.addEventListener('click', () => toast.remove());
    }

    host.appendChild(toast);
    if (!TOAST_DEBUG_STICKY) {
      setTimeout(() => { try { toast.remove(); } catch {} }, isError ? 3200 : 2600);
    }
  } catch {
    // noop
  }
}

export function buttonSuccessFlash(btn, okText = 'Agregado') {
  if (!btn) return;
  const old = btn.dataset.__oldText || btn.textContent || '';
  btn.disabled = true;
  btn.textContent = okText;
  btn.classList.add('site-opacity-90');
  setTimeout(() => {
    btn.disabled = false;
    btn.textContent = old;
    btn.classList.remove('site-opacity-90');
  }, 1200);
}

export function buttonLoading(btn, loadingText = 'Agregando…') {
  if (!btn) return () => {};
  btn.disabled = true;
  if (!btn.dataset.__oldText) btn.dataset.__oldText = btn.textContent || '';
  btn.textContent = loadingText;
  return () => {
    btn.disabled = false;
    btn.textContent = btn.dataset.__oldText || '';
  };
}

export { setCartCount };
