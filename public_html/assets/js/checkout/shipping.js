// checkout/shipping.js
// Cotización y selección de envío (Correo Argentino) — FIX516
// - Render del bloque viene del server (HTML en JSON)
// - Guardamos snapshot en sesión vía /checkout/envio/seleccionar

export function init() {
  const root = document.querySelector('[data-checkout-shipping-root]');
  if (!root) return;
  const loading = window.checkoutLoading || null;

  const calcUrl = root.getAttribute('data-shipping-calc-url') || '';
  const selectUrl = root.getAttribute('data-shipping-select-url') || '';
  const box = document.getElementById('checkout-shipping-box');

  // En Step 2 (Envío) el botón "Continuar al pago" está en el mismo bloque.
  const continueEl = root.querySelector('[data-checkout-step2-continue]') || document.querySelector('[data-checkout-step2-continue]');

  const setContinueEnabled = (enabled) => {
    if (!continueEl) return;
    if (enabled) {
      continueEl.classList.remove('is-disabled');
      continueEl.removeAttribute('aria-disabled');
    } else {
      continueEl.classList.add('is-disabled');
      continueEl.setAttribute('aria-disabled', 'true');
    }
  };

  const updateContinueState = () => {
    const method = (root.querySelector('input[name="shipping_method"]')?.value || '').trim();
    const agencyId = (root.querySelector('input[name="shipping_agency_id"]')?.value || '').trim();
    if (!method) {
      setContinueEnabled(false);
      return;
    }
    // method viene como "D|SERVICIO" o "S|SERVICIO"
    if (method.startsWith('S|') && !agencyId) {
      setContinueEnabled(false);
      return;
    }
    setContinueEnabled(true);
  };

  // Estado inicial
  setContinueEnabled(false);
  updateContinueState();

  // Persistimos en memoria el paquete calculado para guardarlo en sesión al seleccionar una opción
  let lastPackage = null;
  let lastCartSig = '';
  let lastCalculationId = '';

  let lastSelectKey = '';
  let lastSelectAt = 0;


  const btn = root.querySelector('[data-action="checkout-calc-shipping"]');

  const getCsrf = () => {
    const el = document.querySelector('input[name="_csrf"]');
    return el ? (el.value || '') : '';
  };

  const getAddressId = () => {
    const sel = document.querySelector('input[name="address_id"]:checked');
    return sel ? parseInt(sel.value, 10) : 0;
  };

  const setHidden = (name, value) => {
    const el = root.querySelector(`input[name="${name}"]`);
    if (el) el.value = (value === null || typeof value === 'undefined') ? '' : String(value);
  };

  async function postJson(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'fetch',
        'X-CSRF-Token': getCsrf(),
      },
      body: JSON.stringify(payload || {}),
      credentials: 'same-origin',
    });

    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (_) { data = null; }

    return { ok: res.ok, status: res.status, data, raw: text };
  }

  function handleAuthRequired(resp) {
    try {
      if (!resp) return false;
      if (resp.status !== 401) return false;
      const d = resp.data || {};
      if ((d.code === 'auth_required' || d.error === 'unauthorized') && d.login_url) {
        window.location.href = d.login_url;
        return true;
      }
    } catch (_) {}
    return false;
  }


  function moneyAr(v) {
    const n = parseInt(v || 0, 10) || 0;
    return '$ ' + n.toLocaleString('es-AR');
  }

  function updateSummary(cost) {
    const shipEl = document.getElementById('checkout-summary-shipping');
    if (shipEl) shipEl.textContent = (cost > 0) ? moneyAr(cost) : 'Gratis';

    const subEl = document.getElementById('checkout-summary-subtotal');
    const discEl = document.getElementById('checkout-summary-discount');
    const totalEl = document.getElementById('checkout-summary-total');

    const subtotal = subEl ? (parseInt(subEl.getAttribute('data-value') || '0', 10) || 0) : 0;
    const discount = discEl ? (parseInt(discEl.getAttribute('data-value') || '0', 10) || 0) : 0;
    const total = Math.max(0, subtotal + (parseInt(cost || 0, 10) || 0) - discount);

    if (totalEl) totalEl.textContent = moneyAr(total);
  }

  async function selectSnapshotFromUI() {
    const checked = root.querySelector('input[name="shipping_option"]:checked');
    if (!checked) return;

    // Sin un cálculo vigente no podemos persistir selección (seguridad-first).
    if (!lastCalculationId) {
      setContinueEnabled(false);
      return;
    }

    const mode = (checked.getAttribute('data-mode') || 'D').toUpperCase();
    const serviceCode = checked.getAttribute('data-service-code') || '';

    // SECURITY-FIRST:
    // el cliente NO manda costo/nombre; solo el option_id (+ agency_id si aplica).
    const optionId = checked.value || `${mode}|${serviceCode}`;

    let agencyId = '';
    let agencyName = '';
    let agencyAddress = '';

    if (mode === 'S') {
      const card = checked.closest('[data-shipping-card]');
      const sel = card ? card.querySelector('[data-shipping-agency-select]') : root.querySelector('[data-shipping-agency-select]');
      if (sel) {
        agencyId = sel.value || '';
        const opt = sel.options && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        if (opt) {
          agencyName = opt.getAttribute('data-name') || (opt.textContent || '').trim();
          agencyAddress = opt.getAttribute('data-address') || '';
        }
      }
    }

    // Validación: si es retiro en sucursal, exigimos selección explícita.
// Importante UX: no mostramos alert; simplemente mantenemos deshabilitado "Continuar".
if (mode === 'S') {
  const card = checked.closest('[data-shipping-card]');
  const sel = card ? card.querySelector('[data-shipping-agency-select]') : root.querySelector('[data-shipping-agency-select]');
  const hint = card ? card.querySelector('[data-shipping-agency-required]') : root.querySelector('[data-shipping-agency-required]');
  if (sel && (!agencyId || agencyId === '')) {
    setContinueEnabled(false);
    // UX: guiar al usuario
    if (hint) hint.classList.remove('ui-is-hidden');
    try { sel.focus({ preventScroll: true }); } catch (e) {}
    return;
  }
  if (hint) hint.classList.add('ui-is-hidden');
}

    // Dedupe: evita doble POST (click + change) con la misma selección
    const selectKey = `${lastCalculationId}|${optionId}|${agencyId}`;
    const now = Date.now();
    if (selectKey === lastSelectKey && (now - lastSelectAt) < 500) {
      return;
    }
    lastSelectKey = selectKey;
    lastSelectAt = now;

    // Persistimos en sesión (strict)
    const resp = await postJson(selectUrl, {
      calculation_id: lastCalculationId,
      option_id: optionId,
      agency_id: agencyId,
    });
    if (handleAuthRequired(resp)) return;

    if (!resp.ok || !resp.data || resp.data.ok !== true) {
      try { loading && loading.fail && loading.fail('shipping'); } catch (_) {}
      try { loading && loading.fail && loading.fail('shipping'); } catch (_) {}
      const msg = (resp.data && resp.data.error) ? resp.data.error : 'No se pudo guardar la selección de envío.';
      alert(msg);
      return;
    }

    // Hidden inputs para el submit final
    // Importante: el checkout step2 habilita/valida con shipping_method + shipping_agency_id
    // (ver updateContinueState). Mantener estos nombres como source of truth.
    const shippingMethod = (resp.data && resp.data.shipping_method) ? resp.data.shipping_method : `${mode}|${serviceCode}`;
    const shippingAgencyId = (resp.data && typeof resp.data.shipping_agency_id !== 'undefined') ? (resp.data.shipping_agency_id || '') : (agencyId || '');
    const selectedCost = (resp.data && typeof resp.data.cost !== 'undefined') ? (parseInt(resp.data.cost, 10) || 0) : 0;

    setHidden('shipping_method', shippingMethod);
    setHidden('shipping_agency_id', shippingAgencyId);

    updateSummary(selectedCost);

    // Habilitar "Continuar" cuando ya hay una selección persistida válida
    const hasMethod = !!shippingMethod;
    const hasAgency = (!shippingMethod.startsWith('S|')) || (!!shippingAgencyId && shippingAgencyId !== '');
    setContinueEnabled(hasMethod && hasAgency);
    updateContinueState();
  }

  function bindOptionHandlers() {
    // Cards clickeables + teclado (Enter/Espacio)
    const cards = Array.from(root.querySelectorAll('[data-shipping-card]'));
    const applySelectedUI = () => {
      const checked = root.querySelector('input[name="shipping_option"]:checked');
      const checkedVal = checked ? checked.value : null;
      cards.forEach((card) => {
        const radio = card.querySelector('input[name="shipping_option"]');
        const isOn = radio && checkedVal && (radio.value === checkedVal);
        card.classList.toggle('is-selected', !!isOn);
        if (!isOn) {
          card.classList.remove('is-expanded');
          const panel = card.querySelector('[data-shipping-pickup-panel]');
          const toggle = card.querySelector('[data-shipping-pickup-toggle]');
          if (panel) panel.classList.add('ui-is-hidden');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
      });
    };

    // Muestra la dirección de la sucursal (si el backend la trae) al seleccionar una opción.
    const updateAgencyAddressUI = (card) => {
      if (!card) return;
      const addressEl = card.querySelector('[data-shipping-agency-address-text]');
      const agencySel = card.querySelector('[data-shipping-agency-select]');
      if (!addressEl || !agencySel) return;

      const opt = agencySel.selectedOptions && agencySel.selectedOptions.length ? agencySel.selectedOptions[0] : null;
      const name = opt ? String(opt.dataset.name || '').trim() : '';
      const addr = opt ? String(opt.dataset.address || '').trim() : '';
      if (addr) {
        addressEl.textContent = `Dirección: ${addr}`;
        addressEl.classList.remove('ui-is-hidden');
      } else {
        // En QA a veces la API no trae domicilio. Igual mostramos feedback para no dejar un "hueco".
        addressEl.textContent = 'Dirección: (no disponible)';
        addressEl.classList.remove('ui-is-hidden');
      }
    };

    // Mapa embebido (iframe) en base a la dirección mostrada.
    // Usamos Google Maps sin API key por querystring (output=embed).
    const updateAgencyMapUI = (card) => {
      if (!card) return;
      const mapWrap = card.querySelector('[data-shipping-agency-map]');
      if (!mapWrap) return;
      const iframe = mapWrap.querySelector('iframe');
      if (!iframe) return;

      const mapsUrl = String(mapWrap.getAttribute('data-map-url') || '').trim();
      const placeId = String(mapWrap.getAttribute('data-map-place-id') || '').trim();
      const lat = String(mapWrap.getAttribute('data-map-lat') || '').trim();
      const lng = String(mapWrap.getAttribute('data-map-lng') || '').trim();
      const zoomRaw = parseInt(mapWrap.getAttribute('data-map-zoom') || '17', 10);
      const zoom = Number.isFinite(zoomRaw) ? Math.max(1, Math.min(21, zoomRaw)) : 17;

      if (placeId) {
        iframe.setAttribute('src', `https://www.google.com/maps?q=place_id:${encodeURIComponent(placeId)}&z=${zoom}&output=embed`);
        mapWrap.classList.remove('ui-is-hidden');
        return;
      }

      if (mapsUrl) {
        const normalizedUrl = mapsUrl.includes('output=embed') ? mapsUrl : `${mapsUrl}${mapsUrl.includes('?') ? '&' : '?'}output=embed`;
        iframe.setAttribute('src', normalizedUrl);
        mapWrap.classList.remove('ui-is-hidden');
        return;
      }

      if (lat && lng) {
        const q = encodeURIComponent(`${lat},${lng}`);
        iframe.setAttribute('src', `https://www.google.com/maps?q=${q}&z=${zoom}&output=embed`);
        mapWrap.classList.remove('ui-is-hidden');
        return;
      }

      const addressEl = card.querySelector('[data-shipping-agency-address-text]');
      if (!addressEl) return;

      const raw = String(addressEl.textContent || '').replace(/^Dirección:\s*/i, '').trim();
      const hasAddr = raw && raw !== '(no disponible)';
      if (!hasAddr) {
        iframe.removeAttribute('src');
        mapWrap.classList.add('ui-is-hidden');
        return;
      }

      const q = encodeURIComponent(`${raw}, Argentina`);
      iframe.setAttribute('src', `https://www.google.com/maps?q=${q}&output=embed`);
      mapWrap.classList.remove('ui-is-hidden');
    };

    const syncExternalMapLink = (card) => {
      if (!card) return;
      const link = card.querySelector('[data-shipping-agency-map-link]');
      const mapWrap = card.querySelector('[data-shipping-agency-map]');
      const iframe = mapWrap ? mapWrap.querySelector('iframe') : null;
      if (!link || !iframe) return;

      const src = String(iframe.getAttribute('src') || '').trim();
      if (!src) {
        link.setAttribute('href', '#');
        link.classList.add('ui-is-hidden');
        return;
      }

      link.setAttribute('href', src.replace('&output=embed', ''));
      link.classList.remove('ui-is-hidden');
    };

    cards.forEach((card) => {
      const activate = () => {
        const radio = card.querySelector('input[name="shipping_option"]');
        if (!radio) return;
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
        try { radio.focus({ preventScroll: true }); } catch (e) {}
        // UX: si es retiro en sucursal, guiar al selector
        const mode = (radio.getAttribute('data-mode') || 'D').toUpperCase();
        if (mode === 'S') {
          const sel = card.querySelector('[data-shipping-agency-select]');
          const hint = card.querySelector('[data-shipping-agency-required]');
          const panel = card.querySelector('[data-shipping-pickup-panel]');
          const toggle = card.querySelector('[data-shipping-pickup-toggle]');
          if (hint) hint.classList.remove('ui-is-hidden');
          if (panel) panel.classList.remove('ui-is-hidden');
          card.classList.add('is-expanded');
          if (toggle) toggle.setAttribute('aria-expanded', 'true');
          try { sel && sel.focus({ preventScroll: true }); } catch (e) {}
        }

      // Actualizar dirección/mapa cuando el card se activa.
      updateAgencyAddressUI(card);
      updateAgencyMapUI(card);
      syncExternalMapLink(card);
      };

      card.addEventListener('click', (ev) => {
        // Si el usuario está usando el selector de sucursal, no forzar cambio
        if (ev.target && ev.target.closest && ev.target.closest('select')) return;
        // Si el click fue sobre el radio/label, dejamos que el evento nativo dispare el change (evita doble POST)
        if (ev.target && ev.target.closest && (ev.target.closest('input[name="shipping_option"]') || ev.target.closest('label'))) return;
        activate();
      });

      card.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter' || ev.key === ' ') {
          ev.preventDefault();
          activate();
        }

        // Navegación por teclado entre cards
        if (["ArrowDown","ArrowRight","ArrowUp","ArrowLeft"].includes(ev.key)) {
          ev.preventDefault();
          const idx = cards.indexOf(card);
          if (idx === -1) return;
          const dir = (ev.key === 'ArrowDown' || ev.key === 'ArrowRight') ? 1 : -1;
          const next = cards[idx + dir];
          if (next) {
            try { next.focus({ preventScroll: true }); } catch (e) {}
          }
        }
      });

      const togglePickup = card.querySelector('[data-shipping-pickup-toggle]');
      if (togglePickup) {
        togglePickup.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          const panel = card.querySelector('[data-shipping-pickup-panel]');
          if (!panel) return;
          const radio = card.querySelector('input[name="shipping_option"]');
          if (radio && !radio.checked) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
          }
          updateAgencyMapUI(card);
          const isExpanded = panel.classList.toggle('ui-is-hidden') === false;
          card.classList.toggle('is-expanded', isExpanded);
          togglePickup.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        });
      }
    });

    // Radios
    const radios = Array.from(root.querySelectorAll('input[name="shipping_option"]'));
    radios.forEach(r => r.addEventListener('change', () => { selectSnapshotFromUI(); applySelectedUI(); }));

    // Sucursal select
    const agencySels = Array.from(root.querySelectorAll('[data-shipping-agency-select]'));
    agencySels.forEach((agencySel) => {
      agencySel.addEventListener('change', () => {
        const card = agencySel.closest('[data-shipping-card]') || root;
        updateAgencyAddressUI(card);
        updateAgencyMapUI(card);
        syncExternalMapLink(card);
        selectSnapshotFromUI();
        applySelectedUI();
      });
    });

    // Filtro por localidad (solo UI)
    const localitySels = Array.from(root.querySelectorAll('[data-shipping-locality-select]'));
    const applyLocalityFilter = (card) => {
      const locSel = card.querySelector('[data-shipping-locality-select]');
      const agSel = card.querySelector('[data-shipping-agency-select]');
      if (!locSel || !agSel) return;
      const val = (locSel.value || '').trim();

      // Mostrar/ocultar opciones según localidad
      Array.from(agSel.options).forEach((opt) => {
        if (!opt.value) {
          opt.hidden = false;
          opt.disabled = false;
          return;
        }
        const optLoc = (opt.getAttribute('data-locality') || '').trim();
        const show = (!val || optLoc === val);
        opt.hidden = !show;
        opt.disabled = !show;
      });

      // Si la selección actual no pertenece a la localidad elegida, la limpiamos (y dejamos que el usuario elija)
      const selectedOpt = agSel.options[agSel.selectedIndex];
      const selectedLoc = selectedOpt ? (selectedOpt.getAttribute('data-locality') || '').trim() : '';
      if (val && selectedOpt && selectedOpt.value && selectedLoc !== val) {
        agSel.value = '';
        updateAgencyAddressUI(card);
        updateAgencyMapUI(card);
        selectSnapshotFromUI();
        applySelectedUI();
      }
    };

    // Al cambiar la localidad, filtramos opciones. No hacemos POST: el POST ocurre al elegir sucursal.
    localitySels.forEach((locSel) => {
      locSel.addEventListener('change', () => {
        const card = locSel.closest('[data-shipping-card]') || root;
        applyLocalityFilter(card);
        updateAgencyAddressUI(card);
        updateAgencyMapUI(card);
        syncExternalMapLink(card);
      });
    });

    // Inicial: si ya hay una agencia seleccionada, sincronizamos localidad y filtramos.
    cards.forEach((card) => {
      const locSel = card.querySelector('[data-shipping-locality-select]');
      const agSel = card.querySelector('[data-shipping-agency-select]');
      if (!locSel || !agSel) return;
      const selectedOpt = agSel.options[agSel.selectedIndex];
      const optLoc = selectedOpt ? (selectedOpt.getAttribute('data-locality') || '').trim() : '';
      if (optLoc && !locSel.value) {
        locSel.value = optLoc;
      }
      applyLocalityFilter(card);
      updateAgencyAddressUI(card);
      updateAgencyMapUI(card);
      syncExternalMapLink(card);
    });

    // Inicial: solo sincronizamos UI/botón. NO hacemos POST automático,
    // para evitar sobreescribir una selección ya persistida al recargar.
    applySelectedUI();
    updateContinueState();
  }


  async function calculate() {
    const addressId = getAddressId();
    if (!addressId) {
      alert('Seleccioná una dirección para calcular el envío.');
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.classList.add('site-opacity-60', 'cursor-not-allowed');
      btn.textContent = 'Calculando...';
    }

    const resp = await postJson(calcUrl, { address_id: addressId });

    if (handleAuthRequired(resp)) return;

    if (btn) {
      btn.disabled = false;
      btn.classList.remove('site-opacity-60', 'cursor-not-allowed');
      btn.textContent = 'Calcular envío';
    }

    if (!resp.ok || !resp.data || resp.data.ok !== true) {
      try { loading && loading.fail && loading.fail('shipping'); } catch (_) {}
      const msg = (resp.data && resp.data.error) ? resp.data.error : 'No se pudo calcular el envío.';
      alert(msg);
      return;
    }

    lastPackage = resp.data.package || null;
    lastCartSig = resp.data.cart_sig || '';
    lastCalculationId = resp.data.calculation_id || '';

    // Rehidrata hidden fields desde servidor: evita estados stale en refresh/recalc.
    setHidden('shipping_method', resp.data.shipping_method || '');
    setHidden('shipping_agency_id', resp.data.shipping_agency_id || '');
    updateContinueState();

    if (box) box.innerHTML = resp.data.html || '';
    bindOptionHandlers();
    try { loading && loading.finish && loading.finish('shipping'); } catch (_) {}
  }

  if (btn) btn.addEventListener('click', calculate);

  // Si el usuario cambia de dirección, invalidamos visualmente el bloque (pero no spameamos la API)
  const addrRadios = Array.from(document.querySelectorAll('input[name="address_id"]'));
  addrRadios.forEach(r => r.addEventListener('change', () => {
    if (box) box.innerHTML = '';
    setHidden('shipping_method', '');
    setHidden('shipping_agency_id', '');
    lastCalculationId = '';
    updateContinueState();
  }));
  // Autoload (Paso 2): si el contenedor lo pide, calculamos una única vez al entrar.
  // Nota: el checkout renderiza todos los pasos en el DOM (algunos ocultos),
  // por eso solo autoload cuando el paso actual es 2.
  try {
    const top = document.querySelector('[data-checkout-top]');
    const currentStep = top && top.dataset.checkoutCurrentStep ? parseInt(top.dataset.checkoutCurrentStep, 10) : null;

    const auto = document.querySelector('[data-shipping-results][data-autoload-shipping="1"]');
    if (currentStep === 2 && auto && !auto.dataset.__autoloaded) {
      auto.dataset.__autoloaded = '1';
      const selectedAddress = document.querySelector('input[name="address_id"]:checked');
      if (selectedAddress) {
        calculate();
      }
    }
  } catch (e) {}

}
