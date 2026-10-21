export function bindArcaActions(ctx) {
  const {
    postJSON,
    pushToast,
    confirmModal,
    showProcessing,
    hideProcessing,
    getArcaCtx,
    replaceArcaBox,
    showArcaInline,
  } = ctx;

  const box = document.getElementById('arca-box');
  if (!box) return;
  if (box.dataset.bound === '1') return;
  box.dataset.bound = '1';

  box.addEventListener('submit', async (e) => {
    const form = e.target && e.target.tagName === 'FORM' ? e.target : null;
    if (!form) return;

    const action = String(form.getAttribute('action') || '');
    const method = String(form.getAttribute('method') || 'POST').toUpperCase();
    if (method !== 'POST') return;
    if (!(action.includes('/admin/orders/arca/') || action.includes('/admin/facturacion/comprobantes/'))) return;
    e.preventDefault();

    const map = {
      '/admin/orders/arca/emit': {
        title: 'Emitir factura',
        subtitle: 'ARCA',
        body: 'Se emitira el comprobante desde este pedido. Esto puede demorar 2-3 segundos. ¿Confirmas?',
        okText: 'Emitir',
      },
      '/admin/orders/arca/emit-nc': {
        title: 'Emitir Nota de Credito',
        subtitle: 'ARCA',
        body: 'Se intentara emitir una Nota de Credito total para este pedido (si corresponde). ¿Confirmas?',
        okText: 'Emitir NC',
      },
      '/admin/facturacion/comprobantes/retry-emit': {
        title: 'Reintentar emision',
        subtitle: 'ARCA',
        body: 'Se reintentara la emision del comprobante. ¿Confirmas?',
        okText: 'Reintentar',
      },
      '/admin/facturacion/comprobantes/resend-email': {
        title: 'Reenviar email',
        subtitle: 'ARCA',
        body: 'Se reenviara el email del comprobante al cliente. ¿Confirmas?',
        okText: 'Reenviar',
      },
    };

    let actionPath = action.startsWith('http') ? (new URL(action)).pathname : action;
    const adminPos = actionPath.indexOf('/admin/');
    if (adminPos >= 0) actionPath = actionPath.slice(adminPos);
    const meta = map[actionPath] || {
      title: 'Confirmar accion',
      subtitle: 'ARCA',
      body: '¿Confirmas la accion?',
      okText: 'Confirmar',
    };

    const arcaCtx = getArcaCtx();
    if (actionPath === '/admin/orders/arca/emit-nc') {
      const label = arcaCtx.creditLabel || 'Nota de Credito';
      const st = String(arcaCtx.orderStatus || '');
      const isRefundLike = ['cancelled','refunded','chargeback'].includes(st);
      meta.body = isRefundLike
        ? `Se emitira: ${label} (total).\n\nEsto se usa para anular una factura ya emitida cuando el pedido fue cancelado/reembolsado. ¿Confirmas?`
        : `Se emitira: ${label} (total).\n\nAtencion: el pedido NO esta Cancelado/Reembolsado/Chargeback.\nEmitir una Nota de Credito en este estado es una accion manual y puede no corresponder.\n\n¿Confirmas igualmente?`;
      meta.okText = `Emitir ${label}`;
    }

    if (actionPath === '/admin/orders/arca/emit') {
      if (arcaCtx.hasInvoice) {
        await confirmModal({
          title: 'Factura ya emitida',
          subtitle: 'ARCA',
          body: 'Este pedido ya tiene una factura emitida. Si hubo un error, usa "Reintentar emision".',
          okText: 'Entendido',
          altText: null,
          cancelText: 'Cerrar',
        });
        return;
      }
      if (arcaCtx.invoiceStatus && arcaCtx.invoiceStatus !== '' && arcaCtx.invoiceStatus !== 'issued') {
        await confirmModal({
          title: 'Factura en proceso',
          subtitle: 'ARCA',
          body: `Ya existe un comprobante en estado "${arcaCtx.invoiceStatus}". Proba con "Reintentar emision" si corresponde.`,
          okText: 'Entendido',
          altText: null,
          cancelText: 'Cerrar',
        });
        return;
      }
    }

    if (actionPath === '/admin/orders/arca/emit-nc') {
      if (!arcaCtx.hasInvoice) {
        await confirmModal({
          title: 'No corresponde emitir NC',
          subtitle: 'ARCA',
          body: 'No hay una factura emitida asociada a este pedido. Primero debes emitir la factura.',
          okText: 'Entendido',
          altText: null,
          cancelText: 'Cerrar',
        });
        return;
      }
      if (arcaCtx.hasCreditNote) {
        await confirmModal({
          title: 'NC ya emitida',
          subtitle: 'ARCA',
          body: 'Este pedido ya tiene una Nota de Credito emitida.',
          okText: 'Entendido',
          altText: null,
          cancelText: 'Cerrar',
        });
        return;
      }
    }

    const ans = await confirmModal({
      title: meta.title,
      subtitle: meta.subtitle,
      body: meta.body,
      okText: meta.okText,
      cancelText: 'Cancelar',
    });
    if (ans !== 'ok') return;

    const fd = new FormData(form);
    const payload = {};
    fd.forEach((v, k) => {
      if (k === '_token' || k === 'csrf_token') return;
      payload[k] = v;
    });

    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
      const url = String(form.action || action || '');
      if (!url) throw new Error('Accion invalida (URL vacia).');

      const procMap = {
        '/admin/orders/arca/emit': { title: 'Procesando emision...', text: 'Emitiendo factura en ARCA. No cierres esta ventana.' },
        '/admin/orders/arca/emit-nc': { title: 'Procesando emision...', text: 'Emitiendo Nota de Credito en ARCA. No cierres esta ventana.' },
        '/admin/facturacion/comprobantes/retry-emit': { title: 'Procesando...', text: 'Reintentando emision en ARCA. No cierres esta ventana.' },
        '/admin/facturacion/comprobantes/resend-email': { title: 'Procesando...', text: 'Reenviando email del comprobante. No cierres esta ventana.' },
      };
      const pm = procMap[actionPath] || { title: 'Procesando...', text: 'Ejecutando accion. No cierres esta ventana.' };
      showProcessing({ title: pm.title, text: pm.text });

      const data = await postJSON(url, payload);
      if (data.arca_html) replaceArcaBox(data.arca_html);
      const type = data.ok ? (data.skipped ? 'warn' : 'ok') : 'error';
      const message = data.message || (data.ok ? 'Accion realizada.' : 'No se pudo completar la accion.');
      pushToast({ type, text: message });
      showArcaInline(type === 'warn' ? 'warn' : (type === 'ok' ? 'ok' : 'error'), message);
    } catch (err) {
      pushToast({ type: 'error', text: err.message || 'No se pudo completar la accion.' });
      showArcaInline('error', err.message || 'No se pudo completar la accion.');
    } finally {
      hideProcessing();
      if (btn) btn.disabled = false;
    }
  });
}
