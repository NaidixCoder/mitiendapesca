export function bindShippingForm(ctx) {
  const { postJSON, pushToast } = ctx;
  const form = document.getElementById('shipping-form');
  if (!form) return;

  const carrierSel = form.querySelector('#ship-carrier');
  const serviceWrap = form.querySelector('#ship-service-wrap');
  const externalWrap = form.querySelector('#ship-external-wrap');
  const urlWrap = form.querySelector('#ship-url-wrap');
  const correoActions = document.getElementById('correo-actions');
  const correoTrackBtn = document.getElementById('correo-track-btn');
  const correoImportBtn = document.getElementById('correo-import-btn');
  const dispatchBtn = document.getElementById('ship-dispatch-btn');
  const saveWrap = document.getElementById('ship-save-wrap');

  const applyCarrierUi = () => {
    const carrier = carrierSel ? String(carrierSel.value || '') : '';
    const serviceInput = serviceWrap ? serviceWrap.querySelector('input[name="service"]') : null;
    const externalInput = externalWrap ? externalWrap.querySelector('input[name="external_shipment_id"]') : null;
    const tnInput = form.querySelector('input[name="tracking_number"]');
    const urlInput = form.querySelector('input[name="tracking_url"]');
    const urlOpen = form.querySelector('#tracking-url-open');

    const isCorreo = (carrier === 'correo_argentino' || carrier === '' || carrier === 'Correo Argentino');

    if (serviceInput) {
      serviceInput.readOnly = !!isCorreo;
      serviceInput.placeholder = isCorreo ? 'Paq.AR (viene del import)' : 'Ej: OCA / Moto / Retiro en local';
    }

    if (tnInput) {
      tnInput.readOnly = !!isCorreo;
      tnInput.placeholder = isCorreo ? 'Lo trae Correo (API)' : 'Ej: AB123456789AR';
    }

    if (urlInput) {
      urlInput.readOnly = !!isCorreo;
      urlInput.placeholder = isCorreo ? 'Se deriva del tracking' : 'https://...';
    }

    if (urlOpen) {
      const tn = tnInput ? String(tnInput.value || '').trim() : '';
      const u = urlInput ? String(urlInput.value || '').trim() : '';
      const fallback = 'https://www.correoargentino.com.ar/seguimiento-de-envios';
      const href = u ? u : (isCorreo && tn ? fallback : (u || '#'));
      urlOpen.setAttribute('href', href);
      urlOpen.classList.toggle('av3-opacity-50', href === '#');
      urlOpen.classList.toggle('av3-pointer-events-none', href === '#');
    }

    if (externalWrap) externalWrap.classList.toggle('av3-hidden', !isCorreo);
    if (externalInput) {
      externalInput.readOnly = true;
      externalInput.disabled = !isCorreo;
      if (!isCorreo) externalInput.value = '';
    }

    const hasExternal = externalInput ? (String(externalInput.value || '').trim() !== '') : false;
    if (correoActions) correoActions.classList.toggle('av3-hidden', !isCorreo || hasExternal);
    if (correoImportBtn) correoImportBtn.classList.toggle('av3-hidden', !isCorreo || hasExternal);
    if (correoTrackBtn) correoTrackBtn.classList.toggle('av3-hidden', !isCorreo || !hasExternal);
    if (dispatchBtn) dispatchBtn.classList.toggle('av3-hidden', !isCorreo || !hasExternal);
    if (saveWrap) saveWrap.classList.toggle('av3-hidden', isCorreo);
    if (urlWrap) urlWrap.classList.toggle('av3-hidden', false);
  };

  applyCarrierUi();
  form.addEventListener('shipping:refresh-ui', applyCarrierUi);

  if (carrierSel) {
    carrierSel.addEventListener('change', () => {
      applyCarrierUi();
      pushToast({
        type: 'ok',
        text: (carrierSel.value === 'manual')
          ? 'Transportista actualizado. Recorda guardar el envio.'
          : 'Transportista actualizado.'
      });
    });
  }

  const tnInput = form.querySelector('input[name="tracking_number"]');
  const urlInput = form.querySelector('input[name="tracking_url"]');
  if (tnInput) tnInput.addEventListener('input', applyCarrierUi);
  if (urlInput) urlInput.addEventListener('input', applyCarrierUi);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const currentCarrier = carrierSel ? String(carrierSel.value || '') : '';
    const isCorreo = (currentCarrier === 'correo_argentino' || currentCarrier === '' || currentCarrier === 'Correo Argentino');
    if (isCorreo) {
      pushToast({ type: 'warn', text: 'Para Correo Argentino no se usa "Guardar". Usa "Marcar como despachado".' });
      return;
    }

    const fd = new FormData(form);
    const payload = {
      order_id: parseInt(fd.get('order_id') || '0', 10),
      shipping_status: String(fd.get('shipping_status') || ''),
      carrier: String(fd.get('carrier') || ''),
      service: String(fd.get('service') || ''),
      tracking_number: String(fd.get('tracking_number') || ''),
      tracking_url: String(fd.get('tracking_url') || ''),
      external_shipment_id: String(fd.get('external_shipment_id') || ''),
      shipped_at: fd.get('shipped_at') || null,
    };

    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
      const data = await postJSON(window.__APP_BASE_URL + '/admin/orders/shipping', payload);
      pushToast({ type: data.warning ? 'warn' : 'ok', text: data.message || 'Envio actualizado.' });
    } catch (err) {
      pushToast({ type: 'error', text: err.message || 'No se pudo actualizar el envio.' });
    } finally {
      if (btn) btn.disabled = false;
    }
  });

  if (dispatchBtn) {
    dispatchBtn.addEventListener('click', async () => {
      const carrier = carrierSel ? String(carrierSel.value || '') : '';
      const isCorreo = (carrier === 'correo_argentino' || carrier === '' || carrier === 'Correo Argentino');
      if (!isCorreo) return;

      const ext = form.querySelector('input[name="external_shipment_id"]');
      const hasExternal = ext ? (String(ext.value || '').trim() !== '') : false;
      if (!hasExternal) {
        pushToast({ type: 'warn', text: 'Primero crea el envio para obtener el ShippingId.' });
        return;
      }

      const shippingSel = form.querySelector('select[name="shipping_status"]');
      if (shippingSel) shippingSel.value = 'despachado';

      const fd = new FormData(form);
      const payload = {
        order_id: parseInt(fd.get('order_id') || '0', 10),
        status: 'despachado',
        carrier: String(fd.get('carrier') || ''),
        service: String(fd.get('service') || ''),
        tracking_number: String(fd.get('tracking_number') || ''),
        tracking_url: String(fd.get('tracking_url') || ''),
        external_shipment_id: String(fd.get('external_shipment_id') || ''),
        shipped_at: fd.get('shipped_at') || null,
      };

      dispatchBtn.disabled = true;
      try {
        const data = await postJSON(window.__APP_BASE_URL + '/admin/orders/shipping', payload);
        pushToast({ type: data.warning ? 'warn' : 'ok', text: data.message || 'Envio despachado.' });
      } catch (err) {
        pushToast({ type: 'error', text: err.message || 'No se pudo marcar como despachado.' });
      } finally {
        dispatchBtn.disabled = false;
      }
    });
  }
}

export function bindCorreoTrack(ctx) {
  const { postJSON, pushToast, showProcessing, hideProcessing, showProcessingResult } = ctx;
  const btn = document.getElementById('correo-track-btn');
  if (!btn) return;
  const carrierSel = document.querySelector('#shipping-form #ship-carrier');
  const endpoint = btn.dataset.endpoint || '';
  const orderId = parseInt(btn.dataset.orderId || '0', 10);
  if (!endpoint || !orderId) return;

  btn.addEventListener('click', async () => {
    const carrier = carrierSel ? String(carrierSel.value || '') : '';
    if (carrier && carrier !== 'correo_argentino') {
      pushToast({ type: 'warn', text: 'El tracking por API solo aplica para Correo Argentino.' });
      return;
    }
    try {
      showProcessing({ title: 'Actualizando tracking', text: 'Consultando estado en Correo Argentino...' });
      const data = await postJSON(endpoint, { order_id: orderId });
      hideProcessing();

      const trackingEl = document.getElementById('ship-tracking-status');
      if (trackingEl && data.status) {
        const st = String(data.status || '').trim();
        const label = ({
          en_transito: 'En transito',
          en_sucursal: 'En sucursal',
          entregado: 'Entregado',
          fallido: 'Fallido',
        })[st] || (st || '-');
        trackingEl.textContent = label;
      }

      const tn = document.querySelector('#shipping-form input[name="tracking_number"]');
      if (tn && data.tracking_number) {
        tn.value = data.tracking_number;
        tn.dispatchEvent(new Event('input', { bubbles: true }));
      }

      showProcessingResult({
        ok: true,
        title: 'Tracking actualizado',
        text: `Estado: ${data.status || '-'} · Eventos: ${data.events_count ?? 0}`,
      });
      pushToast({ type: 'ok', text: 'Tracking actualizado.' });
    } catch (err) {
      hideProcessing();
      showProcessingResult({
        ok: false,
        title: 'No se pudo actualizar',
        text: (err && err.message) ? err.message : 'Error consultando el tracking.',
      });
      pushToast({ type: 'error', text: 'Error al consultar tracking.' });
    }
  });
}

export function bindCorreoImport(ctx) {
  const { postJSON, pushToast, confirmModal, showProcessing, hideProcessing, showProcessingResult } = ctx;
  const btn = document.getElementById('correo-import-btn');
  if (!btn) return;
  const carrierSel = document.querySelector('#shipping-form #ship-carrier');
  if (btn.dataset.bound === '1') return;
  btn.dataset.bound = '1';

  const endpoint = btn.dataset.endpoint || '';
  const orderId = parseInt(btn.dataset.orderId || '0', 10);
  if (!endpoint || !orderId) return;
  const initialDisabled = !!btn.disabled;

  btn.addEventListener('click', async () => {
    if (btn.disabled) return;
    const carrier = carrierSel ? String(carrierSel.value || '') : '';
    if (carrier && carrier !== 'correo_argentino') {
      pushToast({ type: 'warn', text: 'La creacion por API solo aplica para Correo Argentino.' });
      return;
    }
    if (btn.dataset.loading === '1') return;
    btn.dataset.loading = '1';

    const ans = await confirmModal({
      title: 'Crear envio en Correo Argentino',
      subtitle: 'MiCorreo / PAQ.AR',
      body: 'Esto importara el pedido en MiCorreo y generara un ShippingId para tracking. ¿Continuar?',
      okText: 'Si, crear envio',
      cancelText: 'Cancelar',
    });
    if (ans !== 'ok') {
      btn.dataset.loading = '0';
      return;
    }

    try {
      btn.disabled = true;
      showProcessing({ title: 'Creando envio', text: 'Enviando datos a Correo Argentino...' });
      const data = await postJSON(endpoint, { order_id: orderId });
      hideProcessing();

      const ext = document.querySelector('#shipping-form input[name="external_shipment_id"]');
      if (ext && data.external_shipment_id) ext.value = data.external_shipment_id;

      const form = document.getElementById('shipping-form');
      if (form) form.dispatchEvent(new CustomEvent('shipping:refresh-ui'));

      const tn = document.querySelector('#shipping-form input[name="tracking_number"]');
      if (tn && data.tracking_number) {
        tn.value = data.tracking_number;
        tn.dispatchEvent(new Event('input', { bubbles: true }));
      }

      showProcessingResult({
        ok: true,
        title: 'Envio creado',
        text: `ShippingId: ${data.external_shipment_id || '-'}${data.tracking_number ? ' · Tracking: ' + data.tracking_number : ''}`,
      });
      pushToast({ type: 'ok', text: 'Envio creado en MiCorreo.' });
    } catch (err) {
      hideProcessing();
      const msg = (err && err.message) ? err.message : 'No se pudo crear el envio.';
      showProcessingResult({ ok: false, title: 'No se pudo crear', text: msg });
      pushToast({ type: 'error', text: msg });
    } finally {
      btn.dataset.loading = '0';
      btn.disabled = initialDisabled;
    }
  });
}

export function bindMarkPaid(ctx) {
  const { postJSON, pushToast, confirmModal, updateShippingOptions } = ctx;
  const form = document.getElementById('mark-paid-form');
  if (!form) return;
  const carrierSel = document.querySelector('#shipping-form #ship-carrier');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const currentCarrier = carrierSel ? String(carrierSel.value || '') : '';
    const isCorreo = (currentCarrier === 'correo_argentino' || currentCarrier === '' || currentCarrier === 'Correo Argentino');
    if (isCorreo) {
      pushToast({ type: 'warn', text: 'Para Correo Argentino no se usa "Guardar". Usa "Marcar como despachado".' });
      return;
    }

    const ans = await confirmModal({
      title: 'Marcar como pagado',
      subtitle: 'Transferencia bancaria',
      body: 'Esto confirmara el pago del pedido. ¿Continuar?',
      okText: 'Si, marcar pagado',
      cancelText: 'Cancelar',
    });
    if (ans !== 'ok') return;

    const fd = new FormData(form);
    const payload = { id: parseInt(fd.get('id') || '0', 10), return_to: 'show' };
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    try {
      const data = await postJSON(window.__APP_BASE_URL + '/admin/orders/mark-paid', payload);
      const sel = document.getElementById('order-status');
      const badge = document.getElementById('status-badge');
      const r = data.result || {};
      if (sel) {
        sel.value = 'paid';
        sel.dataset.current = 'paid';
      }
      if (badge) {
        badge.textContent = 'Pagado';
        if (r.badge_class) badge.className = r.badge_class;
      }
      updateShippingOptions(false);
      const wrap = document.getElementById('mark-paid-wrap');
      if (wrap) wrap.classList.add('av3-hidden');
      pushToast({ type: 'ok', text: 'Pedido marcado como pagado.' });
    } catch (err) {
      pushToast({ type: 'error', text: err.message || 'No se pudo marcar como pagado.' });
    } finally {
      if (btn) btn.disabled = false;
    }
  });
}
