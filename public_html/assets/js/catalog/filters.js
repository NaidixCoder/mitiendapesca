// public_html/assets/js/catalog/filters.js
// Catalogo: offcanvas mobile + limpiar precio.

export function init() {
  const panel = document.getElementById('catalog-filters');
  if (panel && panel.dataset.ocBound === '1') return;
  if (panel) panel.dataset.ocBound = '1';

  bindOffcanvas();
  bindPriceSlider();
  bindDesktopPriceCollapsible();
  bindBrandSelection();
  bindFacetOverflow();
  bindClearPrice();
}

function bindClearPrice() {
  document.addEventListener('click', (e) => {
    const link = e.target && e.target.closest ? e.target.closest('[data-clear-price]') : null;
    if (!link) return;

    e.preventDefault();
    const form = link.closest('form');
    if (!form) return;

    const min = form.querySelector('input[name="min"]');
    const max = form.querySelector('input[name="max"]');
    const minRange = form.querySelector('[data-price-min-range]');
    const maxRange = form.querySelector('[data-price-max-range]');
    if (min) min.value = '';
    if (max) max.value = '';
    if (minRange) minRange.value = minRange.min || '0';
    if (maxRange) maxRange.value = maxRange.max || maxRange.value;
    if (min) min.removeAttribute('name');
    if (max) max.removeAttribute('name');

    form.submit();
  }, { passive: false });
}

function bindPriceSlider() {
  const forms = document.querySelectorAll('[data-price-slider]');
  if (!forms.length) return;

  forms.forEach((form) => {
    if (form.dataset.sliderBound === '1') return;
    form.dataset.sliderBound = '1';

    const minInput = form.querySelector('[data-price-min-input]');
    const maxInput = form.querySelector('[data-price-max-input]');
    const minHidden = form.querySelector('[data-price-min-hidden]');
    const maxHidden = form.querySelector('[data-price-max-hidden]');
    const minHandle = form.querySelector('[data-price-min-handle]');
    const maxHandle = form.querySelector('[data-price-max-handle]');
    const trackWrap = form.querySelector('.catalog-price-slider__track-wrap');
    const fill = form.querySelector('[data-price-range-fill]');

    if (!minInput || !maxInput || !minHidden || !maxHidden || !minHandle || !maxHandle || !trackWrap || !fill) return;

    const sliderMin = Number(form.dataset.sliderMin || minHidden.min || 0);
    const sliderMax = Number(form.dataset.sliderMax || maxHidden.max || 50000);
    const sliderStep = Number(form.dataset.sliderStep || 1);
    let currentMin = Number(minHidden.value || sliderMin);
    let currentMax = Number(maxHidden.value || sliderMax);

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
    const snap = (value) => {
      if (sliderStep <= 1) return value;
      return Math.round((value - sliderMin) / sliderStep) * sliderStep + sliderMin;
    };
    const formatValue = (value) => new Intl.NumberFormat('es-AR', { maximumFractionDigits: 0 }).format(value);
    const parseValue = (value) => {
      const digits = String(value || '').replace(/[^\d]/g, '');
      return digits === '' ? NaN : Number(digits);
    };
    const percentFromValue = (value) => ((value - sliderMin) / (sliderMax - sliderMin || 1)) * 100;
    const valueFromClientX = (clientX) => {
      const rect = trackWrap.getBoundingClientRect();
      const styles = window.getComputedStyle(trackWrap);
      const paddingLeft = parseFloat(styles.paddingLeft || '0');
      const paddingRight = parseFloat(styles.paddingRight || '0');
      const usableLeft = rect.left + paddingLeft;
      const usableWidth = Math.max(1, rect.width - paddingLeft - paddingRight);
      const ratio = clamp((clientX - usableLeft) / usableWidth, 0, 1);
      return clamp(snap(Math.round(sliderMin + ratio * (sliderMax - sliderMin))), sliderMin, sliderMax);
    };

    const render = () => {
      const start = percentFromValue(currentMin);
      const end = percentFromValue(currentMax);
      const styles = window.getComputedStyle(trackWrap);
      const paddingLeft = parseFloat(styles.paddingLeft || '0');
      const paddingRight = parseFloat(styles.paddingRight || '0');
      const usableWidth = Math.max(1, trackWrap.clientWidth - paddingLeft - paddingRight);
      const startPx = (start / 100) * usableWidth;
      const endPx = (end / 100) * usableWidth;

      minInput.value = formatValue(currentMin);
      maxInput.value = formatValue(currentMax);
      minHidden.value = String(currentMin);
      maxHidden.value = String(currentMax);
      fill.style.left = `${paddingLeft + startPx}px`;
      fill.style.width = `${Math.max(0, endPx - startPx)}px`;
      minHandle.style.left = `${paddingLeft + startPx}px`;
      maxHandle.style.left = `${paddingLeft + endPx}px`;
    };

    const syncHiddenNames = () => {
      const minIsDefault = Number(minHidden.value || sliderMin) <= sliderMin;
      const maxIsDefault = Number(maxHidden.value || sliderMax) >= sliderMax;

      if (minIsDefault) {
        minHidden.removeAttribute('name');
      } else {
        minHidden.name = 'min';
      }

      if (maxIsDefault) {
        maxHidden.removeAttribute('name');
      } else {
        maxHidden.name = 'max';
      }
    };

    const commitMinInput = () => {
      const rawValue = parseValue(minInput.value);
      if (Number.isNaN(rawValue)) {
        minInput.value = formatValue(currentMin);
        return;
      }
      currentMin = clamp(snap(rawValue), sliderMin, currentMax);
      render();
      syncHiddenNames();
    };

    const commitMaxInput = () => {
      const rawValue = parseValue(maxInput.value);
      if (Number.isNaN(rawValue)) {
        maxInput.value = formatValue(currentMax);
        return;
      }
      currentMax = clamp(snap(rawValue), currentMin, sliderMax);
      render();
      syncHiddenNames();
    };

    minInput.addEventListener('change', commitMinInput);
    maxInput.addEventListener('change', commitMaxInput);
    minInput.addEventListener('blur', commitMinInput);
    maxInput.addEventListener('blur', commitMaxInput);

    const bindHandleDrag = (handle, which) => {
      handle.addEventListener('pointerdown', (event) => {
        event.preventDefault();

        const move = (clientX) => {
          const value = valueFromClientX(clientX);
          if (which === 'min') {
            currentMin = clamp(value, sliderMin, currentMax);
            render();
            syncHiddenNames();
            return;
          }
          currentMax = clamp(value, currentMin, sliderMax);
          render();
          syncHiddenNames();
        };

        const onPointerMove = (ev) => move(ev.clientX);
        const onPointerUp = () => {
          document.removeEventListener('pointermove', onPointerMove);
          document.removeEventListener('pointerup', onPointerUp);
          document.removeEventListener('pointercancel', onPointerUp);
        };

        move(event.clientX);
        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);
        document.addEventListener('pointercancel', onPointerUp);
      });
    };

    bindHandleDrag(minHandle, 'min');
    bindHandleDrag(maxHandle, 'max');

    trackWrap.addEventListener('pointerdown', (event) => {
      if (event.target === minHandle || event.target === maxHandle) return;
      const value = valueFromClientX(event.clientX);
      const nearest = Math.abs(value - currentMin) <= Math.abs(value - currentMax) ? 'min' : 'max';

      if (nearest === 'min') {
        currentMin = clamp(value, sliderMin, currentMax);
        render();
        syncHiddenNames();
        return;
      }

      currentMax = clamp(value, currentMin, sliderMax);
      render();
      syncHiddenNames();
    });

    currentMin = clamp(currentMin, sliderMin, sliderMax);
    currentMax = clamp(currentMax, currentMin, sliderMax);
    render();
    syncHiddenNames();

    form.addEventListener('submit', () => {
      syncHiddenNames();
    });
  });
}

function bindBrandSelection() {
  const forms = document.querySelectorAll('.catalog-price-form');
  if (!forms.length) return;

  forms.forEach((form) => {
    if (form.dataset.brandBound === '1') return;
    form.dataset.brandBound = '1';

    const checkboxes = Array.from(form.querySelectorAll('.catalog-brand-option__input[type="checkbox"]'));
    const clearBtn = form.querySelector('[data-clear-brand-selection]');
    if (!checkboxes.length || !clearBtn) return;

    const sync = () => {
      const hasChecked = checkboxes.some((input) => input.checked);
      checkboxes.forEach((input) => {
        const option = input.closest('.catalog-brand-option');
        if (!option) return;
        option.classList.toggle('is-active', input.checked);
      });
      clearBtn.classList.toggle('is-active', !hasChecked);
    };

    checkboxes.forEach((input) => {
      input.addEventListener('change', sync);
    });

    clearBtn.addEventListener('click', (event) => {
      event.preventDefault();
      checkboxes.forEach((input) => {
        input.checked = false;
      });
      sync();
    });

    sync();
  });
}

function bindDesktopPriceCollapsible() {
  const detailsList = document.querySelectorAll('.catalog-price-collapsible');
  if (!detailsList.length) return;

  const mqDesktop = window.matchMedia('(min-width: 1200px)');

  detailsList.forEach((details) => {
    if (details.dataset.desktopBound === '1') return;
    details.dataset.desktopBound = '1';
    details.dataset.initialOpen = details.hasAttribute('open') ? '1' : '0';

    const summary = details.querySelector('.catalog-price-collapsible__summary');
    if (summary) {
      summary.addEventListener('click', (event) => {
        if (!mqDesktop.matches) return;
        event.preventDefault();
        details.open = true;
      });
    }
  });

  const sync = () => {
    detailsList.forEach((details) => {
      if (mqDesktop.matches) {
        details.open = true;
        return;
      }

      details.open = details.dataset.initialOpen === '1';
    });
  };

  mqDesktop.addEventListener?.('change', sync);
  sync();
}

function bindFacetOverflow() {
  document.addEventListener('click', (event) => {
    const button = event.target && event.target.closest ? event.target.closest('[data-filter-toggle-options]') : null;
    if (!button) return;

    event.preventDefault();
    const subfacet = button.closest('[data-filter-subfacet]');
    if (!subfacet) return;

    const overflow = subfacet.querySelector('[data-filter-overflow]');
    if (!overflow) return;

    const isHidden = overflow.hasAttribute('hidden');
    if (isHidden) {
      overflow.removeAttribute('hidden');
      button.textContent = button.getAttribute('data-label-less') || 'Ver menos';
      return;
    }

    overflow.setAttribute('hidden', '');
    button.textContent = button.getAttribute('data-label-more') || 'Ver mas';
  }, { passive: false });
}

function bindOffcanvas() {
  const panel = document.getElementById('catalog-filters');
  const toggles = document.querySelectorAll('[data-filter-toggle], [aria-controls="catalog-filters"]');
  if (!panel || !toggles.length) return;

  let backdrop = document.querySelector('.catalog-sidebar-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'catalog-sidebar-backdrop ui-is-hidden';
    document.body.appendChild(backdrop);
  }

  let isOpen = false;
  const mqDesktop = window.matchMedia('(min-width: 1200px)');

  const setAria = (open) => {
    toggles.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
  };

  const lockScroll = (on) => {
    document.documentElement.style.overflow = on ? 'hidden' : '';
    document.body.style.overflow = on ? 'hidden' : '';
  };

  const open = () => {
    if (mqDesktop.matches) return;
    panel.classList.add('is-open');
    backdrop.classList.remove('ui-is-hidden');
    backdrop.classList.add('is-open');
    lockScroll(true);
    setAria(true);
    isOpen = true;
  };

  const close = () => {
    if (mqDesktop.matches) return;
    panel.classList.remove('is-open');
    backdrop.classList.add('ui-is-hidden');
    backdrop.classList.remove('is-open');
    lockScroll(false);
    setAria(false);
    isOpen = false;
  };

  toggles.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      isOpen ? close() : open();
    });
  });

  backdrop.addEventListener('click', close);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isOpen) close();
  });

  const sync = () => {
    if (mqDesktop.matches) {
      panel.classList.remove('is-open');
      backdrop.classList.add('ui-is-hidden');
      backdrop.classList.remove('is-open');
      lockScroll(false);
      setAria(false);
      isOpen = false;
      return;
    }

    setAria(false);
    isOpen = false;
  };

  mqDesktop.addEventListener?.('change', sync);
  sync();
}
