// Tabs + slug autogenerado (form de producto)
export function init() {
  // -----------------------------
  // Tabs (Admin → Productos → Form)
  // - Progresive enhancement: server-side fallback con ?tab=...
  // - Un solo mecanismo de visibilidad: atributo [hidden]
  // - Orden/labels derivados del DOM (no hardcodeados)
  // -----------------------------
  const root = document.getElementById('product-tabs-root');
  if (root) {
    const nav = root.querySelector('#tabs-nav') || root.querySelector('.product-tabs-nav');
    const btns = nav ? Array.from(nav.querySelectorAll('.tab-btn[data-tab]')) : [];
    const panels = Array.from(root.querySelectorAll('.tab-panel[data-tab]'));
    const mainForm = root.querySelector('#product-form');
    const tabsOrder = btns.length ? btns.map(b => (b.dataset.tab || '').trim()).filter(Boolean) : panels.map(p => (p.dataset.tab || '').trim()).filter(Boolean);

    const labelByTab = {};
    btns.forEach(b => {
      const key = (b.dataset.tab || '').trim();
      if (!key) return;
      labelByTab[key] = (b.textContent || '').trim();
      // safety: evitar submit accidental
      if (!b.getAttribute('type')) b.setAttribute('type', 'button');
      // a11y
      b.setAttribute('role', 'tab');
    });
    if (nav) nav.setAttribute('role', 'tablist');

    const tabHiddenInput = document.getElementById('product-active-tab');

    const normalizeTab = (t) => tabsOrder.includes(t) ? t : (tabsOrder[0] || 'basicos');

    const getTabFromQuery = () => {
      try {
        const u = new URL(window.location.href);
        const t = (u.searchParams.get('tab') || '').trim();
        return tabsOrder.includes(t) ? t : '';
      } catch (_) { return ''; }
    };

    const getTabFromHidden = () => {
      const t = (tabHiddenInput?.value || '').trim();
      return tabsOrder.includes(t) ? t : '';
    };

    // Habilita/deshabilita controles para que el submit incluya SOLO el tab activo.
    // Importante: respeta disabled originales (ej: SKU deshabilitado por sale_mode).
    const disableControlsForTab = (panel) => {
      if (!mainForm || !mainForm.contains(panel)) return;
      const controls = panel.querySelectorAll('input,select,textarea,button');
      controls.forEach(el => {
        if (el.disabled) return;
        el.disabled = true;
        el.dataset.tabDisabled = '1';
      });
    };
    const enableControlsForTab = (panel) => {
      if (!mainForm || !mainForm.contains(panel)) return;
      const controls = panel.querySelectorAll('input,select,textarea,button');
      controls.forEach(el => {
        if (el.dataset.tabDisabled === '1') {
          el.disabled = false;
          delete el.dataset.tabDisabled;
        }
      });
    };

    const setTabInQuery = (tab) => {
      try {
        const u = new URL(window.location.href);
        u.searchParams.set('tab', tab);
        window.history.replaceState({}, '', u.toString());
      } catch (_) {
        // no-op: si el navegador no soporta URL, igual funciona por DOM
      }
    };

    function updateWizard(activeTab) {
      const idx = Math.max(0, tabsOrder.indexOf(activeTab));
      const stepEl = document.getElementById('wizard-step');
      const barEl  = document.getElementById('wizard-progress');

      if (stepEl) {
        const label = labelByTab[activeTab] || '';
        stepEl.textContent = `Paso ${idx + 1} de ${tabsOrder.length} · ${label}`;
      }
      if (barEl) {
        const pct = tabsOrder.length ? Math.round(((idx + 1) / tabsOrder.length) * 100) : 0;
        barEl.style.width = `${pct}%`;
      }

      // Prev/Next
      const wizardNav = document.getElementById('wizard-nav');
      const prevBtn = wizardNav?.querySelector('[data-wizard="prev"]');
      const nextBtn = wizardNav?.querySelector('[data-wizard="next"]');

      if (prevBtn) {
        const hide = idx <= 0;
        prevBtn.toggleAttribute('disabled', hide);
        prevBtn.setAttribute('aria-disabled', hide ? 'true' : 'false');
      }
      if (nextBtn) {
        const isLast = idx >= (tabsOrder.length - 1);
        nextBtn.hidden = isLast;
        nextBtn.toggleAttribute('disabled', isLast);
        nextBtn.setAttribute('aria-disabled', isLast ? 'true' : 'false');
      }
    }

    function applyA11yState(activeTab) {
      // Panels: role + ids + aria-labelledby
      panels.forEach(p => {
        const tab = (p.dataset.tab || '').trim();
        if (!tab) return;
        p.setAttribute('role', 'tabpanel');
        if (!p.id) p.id = `tab-panel-${tab}`;
        const btn = btns.find(b => (b.dataset.tab || '').trim() === tab);
        if (btn) {
          if (!btn.id) btn.id = `tab-${tab}`;
          p.setAttribute('aria-labelledby', btn.id);
          btn.setAttribute('aria-controls', p.id);
        }
      });

      btns.forEach(b => {
        const isActive = (b.dataset.tab || '').trim() === activeTab;
        b.classList.toggle('is-active', isActive);
        b.classList.toggle('av3-bg-brand', isActive);
        b.setAttribute('aria-selected', isActive ? 'true' : 'false');
        b.tabIndex = isActive ? 0 : -1;
      });
    }

    function showTab(tab) {
      const activeTab = normalizeTab((tab || '').trim());

      panels.forEach(p => {
        const isActive = (p.dataset.tab || '').trim() === activeTab;
        // Usamos SOLO hidden (y dejamos CSS manejar display)
        p.hidden = !isActive;
        if (isActive) enableControlsForTab(p); else disableControlsForTab(p);
      });

      // Seguridad: si por HTML roto o refactors un panel quedó anidado en algo [hidden] dentro de root,
      // destapamos únicamente dentro de root (no tocamos layout global).
      try {
        const activePanel = panels.find(p => (p.dataset.tab || '').trim() === activeTab);
        if (activePanel) {
          // root nunca debería estar hidden
          if (root.hidden) root.hidden = false;
          root.classList?.remove?.('hidden');

          const h = activePanel.closest('[hidden], .hidden');
          if (h && root.contains(h) && h !== activePanel) {
            if (typeof h.hidden === 'boolean') h.hidden = false;
            h.classList?.remove?.('hidden');
          }
        }
      } catch (_) {}

      // Persistencia (backend + submits)
      if (tabHiddenInput) tabHiddenInput.value = activeTab;
      setTabInQuery(activeTab);

      applyA11yState(activeTab);
      updateWizard(activeTab);

      // Evento global: permite que otros módulos actualicen UI sin recargar (ej: Imágenes al crear variantes)
      try {
        document.dispatchEvent(new CustomEvent('product:wizard:tab-changed', { detail: { to: activeTab } }));
      } catch (_) {}
    }

    function requestTabChange(tab, reason = 'tab') {
      const from = normalizeTab(getTabFromQuery() || getTabFromHidden() || tabsOrder[0] || 'basicos');
      const to = normalizeTab((tab || '').trim());
      const ev = new CustomEvent('product:wizard:before-tab-change', {
        cancelable: true,
        detail: { from, to, reason }
      });
      root.dispatchEvent(ev);
      if (ev.defaultPrevented) return false;
      // Publicación: forzamos recarga para recalcular requisitos (variantes/imagen/venta) y evitar panel viejo en DOM.
      if (to === 'publicacion' && from !== to && reason !== 'init') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'publicacion');
        window.location.href = url.toString();
        return true;
      }
      showTab(to);
      return true;
    }

    // API simple para otros módulos (ej: confirmación cambios sin guardar)
    root.__showProductTab = showTab;
    root.__requestProductTabChange = requestTabChange;

    // Click tabs
    btns.forEach(b => b.addEventListener('click', () => requestTabChange(b.dataset.tab, 'tab')));

    // Saltos contextuales (ej: desde Venta -> Variantes)
    root.addEventListener('click', (e) => {
      const btn = e.target && e.target.closest ? e.target.closest('[data-go-tab]') : null;
      if (!btn) return;
      const targetTab = (btn.dataset.goTab || '').trim();
      if (!targetTab) return;
      e.preventDefault();
      requestTabChange(targetTab, 'go');
    });

    // Keyboard navigation (←/→)
    if (nav && btns.length) {
      nav.addEventListener('keydown', (e) => {
        const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
        if (!keys.includes(e.key)) return;

        const activeTab = normalizeTab(getTabFromQuery() || getTabFromHidden() || tabsOrder[0]);
        const idx = Math.max(0, tabsOrder.indexOf(activeTab));
        let nextIdx = idx;

        if (e.key === 'ArrowLeft') nextIdx = Math.max(0, idx - 1);
        if (e.key === 'ArrowRight') nextIdx = Math.min(tabsOrder.length - 1, idx + 1);
        if (e.key === 'Home') nextIdx = 0;
        if (e.key === 'End') nextIdx = tabsOrder.length - 1;

        if (nextIdx !== idx) {
          e.preventDefault();
          const t = tabsOrder[nextIdx];
          requestTabChange(t, 'keyboard');
          const nextBtn = btns.find(b => (b.dataset.tab || '').trim() === t);
          nextBtn?.focus?.();
        }
      });
    }

    // Wizard prev/next
    const wizardNav = document.getElementById('wizard-nav');
    if (wizardNav) {
      wizardNav.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('[data-wizard]') : null;
        if (!btn) return;
        const dir = btn.dataset.wizard;

        const activeTab = normalizeTab(getTabFromQuery() || getTabFromHidden() || tabsOrder[0]);
        const idx = Math.max(0, tabsOrder.indexOf(activeTab));

        // Caso especial: Venta → siguiente depende del tipo elegido (simple → Imágenes / variantes → Variantes).
        if (dir === 'next' && activeTab === 'inventario') {
          const mode = (document.querySelector('input[name="sale_mode"]:checked')?.value || 'simple');
          const target = (mode === 'variants') ? 'variantes' : 'imagenes';
          requestTabChange(target, 'wizard-next');
          return;
        }

        const nextIdx = dir === 'prev' ? (idx - 1) : (idx + 1);

        if (nextIdx < 0 || nextIdx >= tabsOrder.length) return;
        requestTabChange(tabsOrder[nextIdx], dir === 'prev' ? 'wizard-prev' : 'wizard-next');
      });
    }

    // Estado inicial: ?tab=... (backend) > hidden input > primer tab
    const initial = normalizeTab(getTabFromQuery() || getTabFromHidden() || tabsOrder[0] || 'basicos');
    showTab(initial);
  }


// Slug autogenerado si está vacío
  const $name = document.getElementById('product-name');
  const $slug = document.getElementById('product-slug');
  if ($name && $slug) {
    const slugify = s =>
      (s || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    $name.addEventListener('blur', () => {
      if (($slug.value || '').trim() === '') $slug.value = slugify($name.value);
    });
  }

  // Toggle venta: simple vs variantes
  const saleRadios = document.querySelectorAll('input[name="sale_mode"]');
  if (saleRadios && saleRadios.length) {
    const apply = () => {
      // Lock: si ya existen variantes, no dejamos volver a simple
      const hasVariants = (document.getElementById('product-has-variants')?.value === '1');

      const rSimple = document.querySelector('input[name="sale_mode"][value="simple"]');
      const rVariants = document.querySelector('input[name="sale_mode"][value="variants"]');

      if (hasVariants && rSimple) rSimple.disabled = true;

      // Si está en simple pero hay variantes, forzamos variants (consistencia sin recargar)
      if (hasVariants && rSimple && rSimple.checked && rVariants) {
        rVariants.checked = true;
      }

      const checked = document.querySelector('input[name="sale_mode"]:checked');
      const mode = checked ? checked.value : 'simple';

      // Mostrar/ocultar por data-sale-mode + deshabilitar inputs del bloque oculto
      document.querySelectorAll('[data-sale-mode]').forEach(el => {
        const isActive = (el.getAttribute('data-sale-mode') === mode);
        el.style.display = isActive ? '' : 'none';

        // Deshabilitamos inputs del bloque oculto para evitar submits “fantasma”
        el.querySelectorAll('input, select, textarea, button').forEach(ctrl => {
          // No tocamos radios globales (están fuera del bloque normalmente)
          if (ctrl && ctrl.name === 'sale_mode') return;
          ctrl.disabled = !isActive;
        });

        // Nota: required solo aplica a simple
        if (el.getAttribute('data-sale-mode') === 'simple') {
          el.querySelectorAll('[name="sku"]').forEach(i => {
            if (i instanceof HTMLInputElement) i.required = isActive;
          });
        }
      });
    };
    saleRadios.forEach(r => r.addEventListener('change', apply));
    apply();
  }

  // Marca: alta rápida desde el form (modal)
  try { initBrandQuickCreate(); } catch (e) { console.warn('[admin-product-form] brand quick', e); }
  try { initCategoryQuickCreate(); } catch (e) { console.warn('[admin-product-form] category quick', e); }


// IVA (Regla de Oro): recalcular en vivo sin recargar (producto simple)
try { initSaleIvaCalc(); } catch (_) {}
};



function initSaleIvaCalc() {
  const ivaSel = document.querySelector('[data-role="sale-iva-rate"]');
  const costInp = document.querySelector('[data-role="sale-cost"]');
  const priceInp = document.querySelector('[data-role="sale-price"]');
  const marginInp = document.querySelector('[data-role="sale-margin-pct"]');
  const calcBox = document.querySelector('[data-role="sale-iva-calc"]');
  if (!ivaSel || !costInp || !priceInp || !calcBox) return;

  const elPct = calcBox.querySelector('[data-role="sale-iva-pct"]');
  const elIvaAmt = calcBox.querySelector('[data-role="sale-iva-amount"]');
  const elFinal = calcBox.querySelector('[data-role="sale-cost-final"]');
  const elMarkup = calcBox.querySelector('[data-role="sale-markup-pct"]');

  const nfMoney = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
  const nfPct = (n) => {
    if (!isFinite(n)) return '—';
    const s = (Math.round(n * 10) / 10).toFixed(1);
    return s.replace(/\.0$/, '');
  };

  const nfPctWithSign = (n) => {
    const s = nfPct(n);
    if (s === "—") return s;
    return s + "%";
  };


  const isEditingMargin = () => {
    try { return !!marginInp && document.activeElement === marginInp; } catch (_) { return false; }
  };

  const parseNum = (v) => {
    if (v == null) return NaN;
    let s = String(v).trim();
    if (!s) return NaN;
    s = s.replace(/[^0-9,\.\-]/g, '');
    // Si hay coma y punto, asumimos punto miles y coma decimal: 1.234,56
    const hasComma = s.includes(',');
    const hasDot = s.includes('.');
    if (hasComma && hasDot) {
      s = s.replace(/\./g, '').replace(',', '.');
    } else if (hasComma && !hasDot) {
      s = s.replace(',', '.');
    }
    const n = parseFloat(s);
    return isFinite(n) ? n : NaN;
  };

  const getIvaRate = () => {
    const raw = (ivaSel.value || '').trim();
    if (raw === '') return NaN; // (usar default) → no forzamos cálculo
    const n = parseNum(raw);
    if (!isFinite(n)) return NaN;
    return n > 1 ? (n / 100) : n;
  };

  // Persistimos el IVA anterior para poder mantener markup cuando cambia la alícuota
  // (ej: cambiar 21% → 10,5% debería ajustar el precio para conservar el ratio sobre costo final).
  let prevIvaRate = (() => {
    const r = getIvaRate();
    return isFinite(r) ? r : NaN;
  })();

  const recompute = (opts = {}) => {
    const ivaRate = getIvaRate();
    const cost = parseNum(costInp.value);
    let price = parseNum(priceInp.value);

    if (!isFinite(cost) || cost <= 0 || !isFinite(ivaRate)) return; // dejamos el server-render por defecto

    // Si cambió el IVA, ajustamos el input de precio para mantener el mismo markup sobre costo final.
    // Solo lo hacemos cuando el precio actual es válido y cuando tenemos un IVA previo válido.
    if (opts.changedIva === true && isFinite(prevIvaRate) && isFinite(price) && price > 0) {
      const mul = (1 + ivaRate) / (1 + prevIvaRate);
      const nextPrice = Math.round(price * mul);
      priceInp.value = String(nextPrice);
      price = nextPrice;
    }
    const ivaAmt = cost * ivaRate;
    const costFinal = cost + ivaAmt;

    if (elPct) elPct.textContent = nfPct(ivaRate * 100);
    if (elIvaAmt) elIvaAmt.textContent = nfMoney.format(Math.round(ivaAmt));
    if (elFinal) elFinal.textContent = nfMoney.format(Math.round(costFinal));

    if (elMarkup) {
      if (!isFinite(price) || price <= 0 || costFinal <= 0) {
        elMarkup.textContent = '—';
      } else {
        const markup = (price / costFinal - 1) * 100;
        elMarkup.textContent = nfPct(markup);

        // Si existe input de ganancia, lo mantenemos sincronizado cuando el usuario edita el precio.
        if (marginInp && !isEditingMargin() && (opts.source === 'price' || (String(marginInp.value || '').trim() === ''))) {
          marginInp.value = isEditingMargin() ? nfPct(markup) : nfPctWithSign(markup);
        }
      }
    }

    // Update prev rate al final (solo si el actual es válido)
    if (isFinite(ivaRate)) prevIvaRate = ivaRate;
  };

  const getMarginFactor = () => {
    if (!marginInp) return NaN;
    const n = parseNum(marginInp.value);
    if (!isFinite(n)) return NaN;
    // Aceptamos 35 (=> 35%) o 0.35 (=> 35%)
    const factor = (n > 1) ? (n / 100) : n;
    return isFinite(factor) ? factor : NaN;
  };

  let syncing = false;
  const recomputeFromMargin = () => {
    if (syncing) return;
    const ivaRate = getIvaRate();
    const cost = parseNum(costInp.value);
    const marginFactor = getMarginFactor();
    if (!isFinite(cost) || cost <= 0 || !isFinite(ivaRate) || !isFinite(marginFactor)) {
      recompute();
      return;
    }
    const costFinal = cost * (1 + ivaRate);
    const nextPrice = Math.round(costFinal * (1 + marginFactor));
    syncing = true;
    priceInp.value = String(nextPrice);
    syncing = false;
    recompute();
  };

  ivaSel.addEventListener('change', () => {
    // Si hay ganancia explícita, mantenemos la ganancia y recalculamos el precio.
    const mf = getMarginFactor();
    if (isFinite(mf)) return recomputeFromMargin();
    return recompute({ changedIva: true });
  });

  costInp.addEventListener('input', () => {
    const mf = getMarginFactor();
    if (isFinite(mf)) return recomputeFromMargin();
    return recompute();
  });

  if (marginInp) {
    const stripPct = (v) => String(v ?? '').replace('%', '').trim();

    const fmtDisplay = (raw) => {
      const n = parseNum(stripPct(raw));
      if (!isFinite(n)) return '';
      return nfPctWithSign(n);
    };

    // Mostrar % al cargar si hay valor
    if (stripPct(marginInp.value) !== '') {
      const disp = fmtDisplay(marginInp.value);
      if (disp) marginInp.value = disp;
    }

    // Al entrar: quitar % para editar
    marginInp.addEventListener('focus', () => {
      marginInp.value = stripPct(marginInp.value);
    });

    // Mientras tipea: recalcular KPIs (parseNum ya ignora el % si lo hubiera)
    marginInp.addEventListener('input', () => recomputeFromMargin());

    // Al salir: agregar % (solo visual)
    marginInp.addEventListener('blur', () => {
      const disp = fmtDisplay(marginInp.value);
      if (disp) marginInp.value = disp;
    });

    // Al guardar: NO enviar el % al backend
    const form = marginInp.closest('form');
    if (form) {
      form.addEventListener('submit', () => {
        marginInp.value = stripPct(marginInp.value);
      });
    }
  }

  priceInp.addEventListener('input', () => {
    if (syncing) return;
    recompute({ source: 'price' });
  });

  // Ejecutar una vez al cargar (y por si el layout tarda en hidratar inputs).
  try { recompute(); } catch (_) {}

  // Refuerzo (delegación): si por algún motivo el select/input se re-renderiza,
  // el cálculo sigue respondiendo sin recargar.
  document.addEventListener('change', (ev) => {
    const t = ev.target;
    if (!(t instanceof HTMLElement)) return;
    if (t.matches('[data-role="sale-iva-rate"]')) {
      try { recompute({ changedIva: true }); } catch (_) {}
    }
  });
}


function initBrandQuickCreate() {
  const openBtn = document.getElementById('brand-quick-open');
  const modal   = document.getElementById('brand-quick-modal');
  const nameInp = document.getElementById('brand-quick-name');
  const saveBtn = document.getElementById('brand-quick-save');
  const errEl   = document.getElementById('brand-quick-error');
  const select  = document.getElementById('product-brand-select');
  const form    = document.getElementById('product-form');

  if (!openBtn || !modal || !nameInp || !saveBtn || !select || !form ) return;

  const closeEls = Array.from(modal.querySelectorAll('[data-brand-quick-close]'));

  const showError = (msg) => {
    if (!errEl) return;
    if (!msg) { errEl.classList.add('av3-hidden'); errEl.textContent = ''; return; }
    errEl.textContent = msg;
    errEl.classList.remove('av3-hidden');
  };

  const open = () => {
    showError('');
    nameInp.value = '';
    modal.classList.remove('av3-hidden');
    // focus after paint
    setTimeout(() => nameInp.focus(), 0);
    document.addEventListener('keydown', onKeyDown);
  };

  const close = () => {
    modal.classList.add('av3-hidden');
    document.removeEventListener('keydown', onKeyDown);
  };

  const onKeyDown = (ev) => {
    if (ev.key === 'Escape') close();
  };

  const basePrefix = (() => {
    try {
      const action = form.getAttribute('action') || location.href;
      const u = new URL(action, location.href);
      // action típico: /<base>/admin/products/save
      const p = (u.pathname || '').replace(/\/admin\/products\/save\/?$/, '');
      return p.replace(/\/+$/, '');
    } catch (e) {
      return '';
    }
  })();

  const endpoint = (basePrefix ? basePrefix : '') + '/admin/brands/quick';

  const getCsrf = () => {
    const el = form.querySelector('input[name="_csrf"]');
    return el && el.value ? String(el.value) : '';
  };

  const addOrSelectBrandOption = (id, name) => {
    const sid = String(id);
    let opt = Array.from(select.options).find(o => String(o.value) === sid);
    if (!opt) {
      opt = document.createElement('option');
      opt.value = sid;
      opt.textContent = name || ('Marca #' + sid);
      select.appendChild(opt);
    } else if (name && !opt.textContent) {
      opt.textContent = name;
    }
    select.value = sid;
    select.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const save = async () => {
    const name = (nameInp.value || '').trim();
    if (!name) { showError('Ingresá un nombre.'); nameInp.focus(); return; }

    saveBtn.disabled = true;
    showError('');

    try {
      const fd = new FormData();
      const csrf = getCsrf();
      if (csrf) fd.append('_csrf', csrf);
      fd.append('name', name);

      const resp = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-Token': csrf } : {}) },
        body: fd,
      });

      const ct = (resp.headers.get('content-type') || '').toLowerCase();
      const data = ct.includes('application/json') ? await resp.json() : await resp.text();

      if (!resp.ok) {
        const msg = typeof data === 'string' ? data : (data && data.error ? data.error : 'No se pudo crear la marca.');
        throw new Error(msg);
      }

      if (typeof data !== 'object' || !data || !data.ok || !data.id) {
        throw new Error('Respuesta inválida del servidor.');
      }

      addOrSelectBrandOption(data.id, data.name || name);
      close();
    } catch (e) {
      showError(e && e.message ? e.message : 'No se pudo crear la marca.');
    } finally {
      saveBtn.disabled = false;
    }
  };

  openBtn.addEventListener('click', open);
  closeEls.forEach(el => el.addEventListener('click', close));
  saveBtn.addEventListener('click', save);
  nameInp.addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') { ev.preventDefault(); save(); }
  });
}


function initCategoryQuickCreate() {
  const openBtn = document.getElementById('category-quick-open');
  const modal   = document.getElementById('category-quick-modal');
  const nameInp = document.getElementById('category-quick-name');
  const parentSel = document.getElementById('category-quick-parent');
  const saveBtn = document.getElementById('category-quick-save');
  const errEl   = document.getElementById('category-quick-error');
  const select  = document.getElementById('product-category-select');
  const form    = document.getElementById('product-form');

  if (!openBtn || !modal || !nameInp || !parentSel || !saveBtn || !select || !form) return;

  // El formulario tiene contenedores que recortan elementos fixed en móvil.
  // Este modal opera por fetch y puede vivir directamente bajo body sin alterar Productos.
  document.body.appendChild(modal);

  const closeEls = Array.from(modal.querySelectorAll('[data-category-quick-close]'));

  const showError = (msg) => {
    if (!errEl) return;
    if (!msg) { errEl.classList.add('av3-hidden'); errEl.textContent = ''; return; }
    errEl.textContent = msg;
    errEl.classList.remove('av3-hidden');
  };

  const open = () => {
    showError('');
    nameInp.value = '';
    parentSel.value = '';
    modal.classList.remove('av3-hidden');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => nameInp.focus(), 0);
    document.addEventListener('keydown', onKeyDown);
  };

  const close = () => {
    modal.classList.add('av3-hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.removeEventListener('keydown', onKeyDown);
  };

  const onKeyDown = (ev) => {
    if (ev.key === 'Escape') close();
  };

  const basePrefix = (() => {
    try {
      const action = form.getAttribute('action') || location.href;
      const u = new URL(action, location.href);
      const p = (u.pathname || '').replace(/\/admin\/products\/save\/?$/, '');
      return p.replace(/\/+$/, '');
    } catch (e) { return ''; }
  })();

  const endpoint = (basePrefix ? basePrefix : '') + '/admin/categories/quick';

  const getCsrf = () => {
    const el = form.querySelector('input[name="_csrf"]');
    return el && el.value ? String(el.value) : '';
  };

  const addOrSelectCategoryOption = (id, name) => {
    const sid = String(id);
    let opt = Array.from(select.options).find(o => String(o.value) === sid);
    if (!opt) {
      opt = document.createElement('option');
      opt.value = sid;
      opt.textContent = name || ('Categoría #' + sid);
      select.appendChild(opt);
    } else if (name && !opt.textContent) {
      opt.textContent = name;
    }
    select.value = sid;
    select.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const save = async () => {
    const name = (nameInp.value || '').trim();
    if (!name) { showError('Ingresá un nombre.'); nameInp.focus(); return; }

    saveBtn.disabled = true;
    showError('');

    try {
      const fd = new FormData();
      const csrf = getCsrf();
      if (csrf) fd.append('_csrf', csrf);
      fd.append('name', name);
      fd.append('parent_id', parentSel.value || '0');

      const resp = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-Token': csrf } : {}) },
        body: fd,
      });

      const ct = (resp.headers.get('content-type') || '').toLowerCase();
      const data = ct.includes('application/json') ? await resp.json() : await resp.text();

      if (!resp.ok) {
        const msg = typeof data === 'string' ? data : (data && data.error ? data.error : 'No se pudo crear la categoría.');
        throw new Error(msg);
      }

      if (typeof data !== 'object' || !data || !data.ok || !data.id) {
        throw new Error('Respuesta inválida del servidor.');
      }

      addOrSelectCategoryOption(data.id, data.name || name);
      close();
    } catch (e) {
      showError(e && e.message ? e.message : 'No se pudo crear la categoría.');
    } finally {
      saveBtn.disabled = false;
    }
  };

  openBtn.addEventListener('click', open);
  closeEls.forEach(el => el.addEventListener('click', close));
  saveBtn.addEventListener('click', save);
  nameInp.addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') { ev.preventDefault(); save(); }
  });
}