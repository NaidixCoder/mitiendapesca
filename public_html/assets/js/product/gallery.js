// Public product gallery
export function init() {
  if (!(document.body && document.body.dataset.page === 'product')) return;
  const AUTH_FAVORITES_TOAST = 'Para guardar en favoritos debes iniciar sesion';
  const showAuthToastOnce = () => {
    const host = document.getElementById('tp-toast-host');
    if (host) {
      const alreadyVisible = Array.from(host.querySelectorAll('[data-toast-message]'))
        .some((el) => (el.textContent || '').trim() === AUTH_FAVORITES_TOAST);
      if (alreadyVisible) return;
    }
    if (typeof window.toast === 'function') {
      window.toast('error', AUTH_FAVORITES_TOAST, {
        autohide: true,
        duration: 3200,
      });
    }
  };

  const mainViewport = document.getElementById('pg-main-viewport');
  const mainTrack = document.getElementById('pg-main-track');
  const thumbsWrap = document.getElementById('pg-thumbs');
  const prevButtons = Array.from(document.querySelectorAll('[data-gallery-prev]'));
  const nextButtons = Array.from(document.querySelectorAll('[data-gallery-next]'));
  const dotsRoot = document.getElementById('pg-dots');
  const counter = document.getElementById('pg-counter');
  const modal = document.getElementById('pg-modal');
  const modalViewport = document.getElementById('pg-modal-viewport');
  const modalTrack = document.getElementById('pg-modal-track');
  const modalCounter = document.getElementById('pg-modal-counter');
  const modalDotsRoot = document.getElementById('pg-modal-dots');
  const modalThumbsRoot = document.getElementById('pg-modal-thumbs');
  const modalClose = Array.from(document.querySelectorAll('[data-gallery-modal-close]'));
  const modalPrevButtons = Array.from(document.querySelectorAll('[data-gallery-modal-prev]'));
  const modalNextButtons = Array.from(document.querySelectorAll('[data-gallery-modal-next]'));
  const favoriteButton = document.querySelector('[data-gallery-favorite]');
  let lockedScrollY = 0;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const syncFavorite = (active = null) => {
    if (!favoriteButton) return;
    const resolved = active === null
      ? favoriteButton.getAttribute('aria-pressed') === 'true'
      : !!active;
    favoriteButton.classList.toggle('is-active', resolved);
    favoriteButton.setAttribute('aria-pressed', resolved ? 'true' : 'false');
    favoriteButton.setAttribute('aria-label', resolved ? 'Quitar de favoritos' : 'Agregar a favoritos');
  };

  if (favoriteButton) {
    syncFavorite();
    favoriteButton.addEventListener('click', async () => {
      const endpoint = favoriteButton.getAttribute('data-favorite-endpoint') || '';
      const next = window.location.pathname + window.location.search;
      const productId = favoriteButton.getAttribute('data-product-id') || '';
      if (!endpoint || !productId) return;

      favoriteButton.disabled = true;
      try {
        const fd = new FormData();
        fd.append('_csrf', csrfToken);
        fd.append('product_id', productId);
        fd.append('next', next);

        const res = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
          },
          body: fd,
          credentials: 'same-origin',
        });

        const ct = res.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await res.json() : null;
        if (res.status === 401 || (data && data.auth === false)) {
          showAuthToastOnce();
          return;
        }
        if (!res.ok || !data || data.ok !== true) {
          if (typeof window.toast === 'function') {
            window.toast('error', 'No se pudo actualizar favoritos');
          }
          return;
        }

        syncFavorite(!!data.active);
      } catch {
        if (typeof window.toast === 'function') {
          window.toast('error', 'Error de red al actualizar favoritos');
        }
      } finally {
        favoriteButton.disabled = false;
      }
    });
  }

  if (!mainViewport || !mainTrack) return;

  const fallbackImages = (() => {
    const raw = mainViewport.getAttribute('data-gallery-images') || '[]';
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
    } catch {
      return [];
    }
  })();

  const getGroup = (variantId) => {
    if (!thumbsWrap) return null;
    const id = variantId === null || variantId === undefined || variantId === '' ? 'general' : String(variantId);
    return thumbsWrap.querySelector(`[data-gallery-group][data-variant-id="${CSS.escape(id)}"]`);
  };

  let group = getGroup('general');
  let thumbs = group ? Array.from(group.querySelectorAll('[data-gallery-thumb]')) : [];
  let images = thumbs.map((btn) => btn.querySelector('img')?.getAttribute('src') || '').filter(Boolean);
  if (!images.length) images = fallbackImages.slice();
  let idx = 0;
  let dots = [];
  let modalDots = [];
  let modalThumbs = [];
  let modalZoom = 1;
  let modalPanX = 0;
  let modalPanY = 0;
  let pinchStartDist = 0;
  let pinchStartZoom = 1;
  let panStartX = 0;
  let panStartY = 0;
  let panOriginX = 0;
  let panOriginY = 0;
  let isPinching = false;
  let isPanning = false;
  let lastTapAt = 0;
  let isMousePanning = false;
  let mousePanStartX = 0;
  let mousePanStartY = 0;
  let mousePanOriginX = 0;
  let mousePanOriginY = 0;
  let mousePanMoved = false;
  let suppressNextClick = false;
  const collectImages = (groupEl) => {
    if (!groupEl) return [];
    return Array.from(groupEl.querySelectorAll('[data-gallery-thumb]'))
      .map((btn) => btn.querySelector('img')?.getAttribute('src') || '')
      .filter(Boolean);
  };

  const syncCounter = () => {
    const total = Math.max(images.length, 1);
    const text = `${idx + 1}/${total}`;
    if (counter) counter.textContent = text;
    if (modalCounter) modalCounter.textContent = text;
  };

  const syncThumbs = () => {
    thumbs.forEach((button, i) => button.classList.toggle('is-active', i === idx));
  };

  const syncDots = () => {
    dots.forEach((button, i) => {
      const active = i === idx;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-current', active ? 'true' : 'false');
    });
    modalDots.forEach((button, i) => {
      const active = i === idx;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-current', active ? 'true' : 'false');
    });
  };

  const syncModalThumbs = () => {
    modalThumbs.forEach((button, i) => {
      const active = i === idx;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-current', active ? 'true' : 'false');
      if (active && modalThumbsRoot) {
        button.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest',
          inline: 'center',
        });
      }
    });
  };

  const getActiveModalImage = () => {
    if (!modalTrack) return null;
    return modalTrack.querySelectorAll('.product-gallery-modal__slide-image')[idx] || null;
  };

  const applyModalImageTransform = () => {
    const img = getActiveModalImage();
    if (!img) return;
    img.style.transform = `translate(${modalPanX}px, ${modalPanY}px) scale(${modalZoom})`;
    img.style.transformOrigin = 'center center';
    img.style.transition = 'transform 80ms ease-out';
    if (modalViewport) {
      modalViewport.classList.toggle('is-zoomed', modalZoom > 1);
    }
  };

  const resetModalZoom = () => {
    modalZoom = 1;
    modalPanX = 0;
    modalPanY = 0;
    const img = getActiveModalImage();
    if (!img) return;
    img.style.transform = '';
    img.style.transformOrigin = '';
    img.style.transition = '';
    if (modalViewport) {
      modalViewport.classList.remove('is-zoomed');
      modalViewport.classList.remove('is-panning');
    }
  };

  const clampPan = () => {
    const viewportW = modalViewport?.clientWidth || 1;
    const viewportH = modalViewport?.clientHeight || 1;
    const maxX = ((modalZoom - 1) * viewportW) / 2;
    const maxY = ((modalZoom - 1) * viewportH) / 2;
    modalPanX = Math.max(-maxX, Math.min(maxX, modalPanX));
    modalPanY = Math.max(-maxY, Math.min(maxY, modalPanY));
  };

  const renderTrack = (trackEl) => {
    if (!trackEl) return;
    trackEl.innerHTML = '';
    images.forEach((src, i) => {
      const slide = document.createElement('div');
      slide.className = trackEl === modalTrack ? 'product-gallery-modal__slide' : 'product-gallery__slide';
      const img = document.createElement('img');
      img.className = trackEl === modalTrack ? 'product-gallery-modal__slide-image' : 'product-gallery__slide-image';
      if (trackEl === mainTrack) {
        img.setAttribute('data-product-main-img', '1');
      }
      img.src = src;
      img.alt = `Imagen ${i + 1}`;
      slide.appendChild(img);
      trackEl.appendChild(slide);
    });
  };

  const moveTrack = (trackEl, viewportEl, offsetPx = 0, animate = true) => {
    if (!trackEl || !viewportEl) return;
    const width = viewportEl.clientWidth || 1;
    trackEl.style.transition = animate ? 'transform 240ms ease' : 'none';
    trackEl.style.transform = `translateX(${(-idx * width) + offsetPx}px)`;
  };

  const isModalOpen = () => !!(modal && !modal.classList.contains('ui-is-hidden'));

  const applyIndex = (nextIndex, animate = true, scope = 'both') => {
    if (!images.length) return;
    idx = (nextIndex + images.length) % images.length;
    if (scope === 'both' || scope === 'main') {
      moveTrack(mainTrack, mainViewport, 0, animate);
    }
    if ((scope === 'both' || scope === 'modal') && modalTrack) {
      moveTrack(modalTrack, modalViewport, 0, animate);
    }
    syncThumbs();
    syncDots();
    syncModalThumbs();
    syncCounter();
    if (scope === 'modal' || scope === 'both') resetModalZoom();
  };

  const renderDots = () => {
    if (!dotsRoot) return;
    dotsRoot.innerHTML = '';
    dots = [];
    if (images.length <= 1) {
      dotsRoot.classList.add('ui-is-hidden');
      return;
    }
    dotsRoot.classList.remove('ui-is-hidden');
    images.forEach((_, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'product-gallery__dot' + (i === idx ? ' is-active' : '');
      btn.setAttribute('aria-label', `Ver imagen ${i + 1}`);
      btn.setAttribute('aria-current', i === idx ? 'true' : 'false');
      btn.addEventListener('click', () => applyIndex(i, true, 'both'));
      dotsRoot.appendChild(btn);
      dots.push(btn);
    });
  };

  const renderModalDots = () => {
    if (!modalDotsRoot) return;
    modalDotsRoot.innerHTML = '';
    modalDots = [];
    if (images.length <= 1) {
      modalDotsRoot.classList.add('ui-is-hidden');
      return;
    }
    modalDotsRoot.classList.remove('ui-is-hidden');
    images.forEach((_, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'product-gallery-modal__dot' + (i === idx ? ' is-active' : '');
      btn.setAttribute('aria-label', `Ver imagen ampliada ${i + 1}`);
      btn.setAttribute('aria-current', i === idx ? 'true' : 'false');
      btn.addEventListener('click', () => applyIndex(i, true, 'modal'));
      modalDotsRoot.appendChild(btn);
      modalDots.push(btn);
    });
  };

  const renderModalThumbs = () => {
    if (!modalThumbsRoot) return;
    modalThumbsRoot.innerHTML = '';
    modalThumbs = [];
    if (images.length <= 1) {
      modalThumbsRoot.classList.add('ui-is-hidden');
      return;
    }
    modalThumbsRoot.classList.remove('ui-is-hidden');
    images.forEach((src, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'product-gallery-modal__thumb' + (i === idx ? ' is-active' : '');
      btn.setAttribute('aria-label', `Ver miniatura ${i + 1}`);
      btn.setAttribute('aria-current', i === idx ? 'true' : 'false');
      const img = document.createElement('img');
      img.className = 'product-gallery-modal__thumb-image';
      img.src = src;
      img.alt = `Miniatura ${i + 1}`;
      btn.appendChild(img);
      btn.addEventListener('click', () => applyIndex(i, true, 'modal'));
      modalThumbsRoot.appendChild(btn);
      modalThumbs.push(btn);
    });
  };

  const bindThumbs = () => {
    thumbs.forEach((btn, i) => {
      btn.onclick = null;
      btn.addEventListener('click', () => applyIndex(i, true, 'both'), { once: true });
    });
  };

  const openModal = () => {
    if (!modal) return;
    // Evita que el mismo click de apertura dispare el zoom del viewport modal.
    suppressNextClick = true;
    lockedScrollY = window.scrollY || window.pageYOffset || 0;
    document.documentElement.classList.add('is-gallery-open');
    document.body.classList.add('is-gallery-open');
    document.body.style.top = `-${lockedScrollY}px`;
    modal.classList.remove('ui-is-hidden');
    modal.setAttribute('aria-hidden', 'false');
    renderTrack(modalTrack);
    renderModalThumbs();
    resetModalZoom();
    applyIndex(idx, false, 'modal');
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.add('ui-is-hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('is-gallery-open');
    document.body.classList.remove('is-gallery-open');
    document.body.style.top = '';
    isMousePanning = false;
    resetModalZoom();
    applyIndex(idx, false, 'main');
    window.scrollTo(0, lockedScrollY);
  };

  const bindSwipe = (viewportEl, trackEl, onTap = null) => {
    if (!viewportEl || !trackEl) return;
    let startX = 0;
    let startY = 0;
    let dragging = false;
    let offsetPx = 0;

    viewportEl.addEventListener('touchstart', (event) => {
      if (viewportEl === modalViewport && event.touches.length === 2) {
        isPinching = true;
        isPanning = false;
        const a = event.touches[0];
        const b = event.touches[1];
        pinchStartDist = Math.hypot(b.clientX - a.clientX, b.clientY - a.clientY);
        pinchStartZoom = modalZoom;
        return;
      }

      if (viewportEl === modalViewport && modalZoom > 1 && event.touches.length === 1) {
        isPanning = true;
        isPinching = false;
        const t = event.touches[0];
        panStartX = t.clientX;
        panStartY = t.clientY;
        panOriginX = modalPanX;
        panOriginY = modalPanY;
        return;
      }

      if (images.length <= 1) return;
      const t = event.changedTouches[0];
      startX = t.screenX;
      startY = t.screenY;
      dragging = false;
      offsetPx = 0;
      trackEl.style.transition = 'none';
    }, { passive: true });

    viewportEl.addEventListener('touchmove', (event) => {
      if (viewportEl === modalViewport && isPinching && event.touches.length === 2) {
        const a = event.touches[0];
        const b = event.touches[1];
        const dist = Math.hypot(b.clientX - a.clientX, b.clientY - a.clientY);
        if (pinchStartDist > 0) {
          modalZoom = Math.max(1, Math.min(4, pinchStartZoom * (dist / pinchStartDist)));
          clampPan();
          applyModalImageTransform();
        }
        event.preventDefault();
        return;
      }

      if (viewportEl === modalViewport && isPanning && event.touches.length === 1) {
        const t = event.touches[0];
        modalPanX = panOriginX + (t.clientX - panStartX);
        modalPanY = panOriginY + (t.clientY - panStartY);
        clampPan();
        applyModalImageTransform();
        event.preventDefault();
        return;
      }

      if (viewportEl === modalViewport && modalZoom > 1) {
        event.preventDefault();
        return;
      }

      if (images.length <= 1) return;
      const t = event.changedTouches[0];
      const dx = t.screenX - startX;
      const dy = t.screenY - startY;
      if (!dragging) {
        if (Math.abs(dx) <= Math.abs(dy) || Math.abs(dx) < 8) return;
        dragging = true;
      }
      offsetPx = dx;
      moveTrack(trackEl, viewportEl, dx, false);
      event.preventDefault();
    }, { passive: false });

    viewportEl.addEventListener('touchend', (event) => {
      if (viewportEl === modalViewport && isPinching) {
        isPinching = false;
        clampPan();
        applyModalImageTransform();
        return;
      }

      if (viewportEl === modalViewport && isPanning) {
        isPanning = false;
        clampPan();
        applyModalImageTransform();
        return;
      }

      if (viewportEl === modalViewport && event.changedTouches.length === 1) {
        const now = Date.now();
        if (now - lastTapAt < 280) {
          if (modalZoom > 1) {
            resetModalZoom();
          } else {
            modalZoom = 2;
            modalPanX = 0;
            modalPanY = 0;
            applyModalImageTransform();
          }
          lastTapAt = 0;
          return;
        }
        lastTapAt = now;
      }

      if (viewportEl === modalViewport && modalZoom > 1) {
        return;
      }

      if (images.length <= 1) {
        if (onTap) onTap();
        return;
      }
      const t = event.changedTouches[0];
      const dx = t.screenX - startX;
      const dy = t.screenY - startY;
      if (!dragging) {
        if (Math.abs(dx) < 10 && Math.abs(dy) < 10 && onTap) onTap();
        return;
      }
      const threshold = Math.max(55, (viewportEl.clientWidth || 0) * 0.16);
      if (Math.abs(offsetPx) > threshold) {
        applyIndex(idx + (offsetPx < 0 ? 1 : -1), true, isModalOpen() ? 'modal' : 'both');
      } else {
        applyIndex(idx, true, isModalOpen() ? 'modal' : 'both');
      }
    }, { passive: true });
  };

  const useGroup = (variantId) => {
    const wanted = variantId === null || variantId === undefined || variantId === '' ? 'general' : String(variantId);
    const general = getGroup('general');
    const variant = wanted !== 'general' ? getGroup(wanted) : null;
    const activeGroup = variant || general;
    if (!activeGroup) return;

    if (thumbsWrap) {
      thumbsWrap.querySelectorAll('[data-gallery-group]').forEach((el) => {
        el.classList.toggle('ui-is-hidden', el !== activeGroup);
      });
    }

    group = activeGroup;
    thumbs = Array.from(group.querySelectorAll('[data-gallery-thumb]'));
    images = collectImages(group);
    if (!images.length && wanted === 'general') images = fallbackImages.slice();
    idx = 0;

    renderTrack(mainTrack);
    if (modalTrack) renderTrack(modalTrack);
    bindThumbs();
    renderDots();
    renderModalDots();
    renderModalThumbs();
    applyIndex(0, false, isModalOpen() ? 'modal' : 'both');
  };

  window.__pgUseGroup = useGroup;

  renderTrack(mainTrack);
  bindThumbs();
  renderDots();
  renderModalDots();
  renderModalThumbs();
  applyIndex(0, false);

  prevButtons.forEach((button) => button.addEventListener('click', () => applyIndex(idx - 1, true, 'both')));
  nextButtons.forEach((button) => button.addEventListener('click', () => applyIndex(idx + 1, true, 'both')));
  modalPrevButtons.forEach((button) => button.addEventListener('click', () => applyIndex(idx - 1, true, 'modal')));
  modalNextButtons.forEach((button) => button.addEventListener('click', () => applyIndex(idx + 1, true, 'modal')));

  mainViewport.addEventListener('click', openModal);
  bindSwipe(mainViewport, mainTrack, openModal);
  bindSwipe(modalViewport, modalTrack, null);

  if (modalViewport) {
    modalViewport.addEventListener('click', (event) => {
      if (suppressNextClick) {
        suppressNextClick = false;
        event.preventDefault();
        return;
      }
      if (modalZoom > 1) {
        resetModalZoom();
        return;
      }
      modalZoom = 2;
      modalPanX = 0;
      modalPanY = 0;
      applyModalImageTransform();
      event.preventDefault();
    });

    modalViewport.addEventListener('wheel', (event) => {
      if (!isModalOpen()) return;
      const delta = event.deltaY;
      const nextZoom = Math.max(1, Math.min(4, modalZoom + (delta < 0 ? 0.2 : -0.2)));
      if (nextZoom === modalZoom) return;
      modalZoom = nextZoom;
      if (modalZoom <= 1) {
        resetModalZoom();
      } else {
        clampPan();
        applyModalImageTransform();
      }
      event.preventDefault();
    }, { passive: false });

    modalViewport.addEventListener('mousedown', (event) => {
      if (modalZoom <= 1 || event.button !== 0) return;
      isMousePanning = true;
      mousePanMoved = false;
      mousePanStartX = event.clientX;
      mousePanStartY = event.clientY;
      mousePanOriginX = modalPanX;
      mousePanOriginY = modalPanY;
      modalViewport.classList.add('is-panning');
      event.preventDefault();
    });
  }

  window.addEventListener('mousemove', (event) => {
    if (!isMousePanning) return;
    const movedX = event.clientX - mousePanStartX;
    const movedY = event.clientY - mousePanStartY;
    if (!mousePanMoved && (Math.abs(movedX) > 3 || Math.abs(movedY) > 3)) {
      mousePanMoved = true;
    }
    modalPanX = mousePanOriginX + (event.clientX - mousePanStartX);
    modalPanY = mousePanOriginY + (event.clientY - mousePanStartY);
    clampPan();
    applyModalImageTransform();
  });

  window.addEventListener('mouseup', () => {
    if (!isMousePanning) return;
    isMousePanning = false;
    if (mousePanMoved) {
      suppressNextClick = true;
    }
    if (modalViewport) modalViewport.classList.remove('is-panning');
  });

  modalClose.forEach((button) => button.addEventListener('click', closeModal));

  window.addEventListener('resize', () => applyIndex(idx, false));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') applyIndex(idx - 1);
    if (event.key === 'ArrowRight') applyIndex(idx + 1);
    if (event.key === 'Escape') closeModal();
  });
}
