export function bindFiscalRequestRevalidate(ctx) {
  const {
    postForm,
    showProcessing,
    showProcessingResult,
    hideProcessing,
    pushToast,
  } = ctx;

  const form = document.querySelector('form[action$="/admin/fiscal/requests/revalidate"]');
  if (!form) return;

  form.addEventListener('submit', async (ev) => {
    if (ev.defaultPrevented) return;
    ev.preventDefault();

    showProcessing({
      title: 'Validando informacion...',
      text: 'Estamos validando el CUIT en Padron ARCA. Esto puede tardar unos segundos.',
    });

    try {
      const data = await postForm(form.action, form);
      const status = data && data.status ? String(data.status) : '';
      const redirect = data && data.redirect ? String(data.redirect) : '';

      const map = {
        valid: { type: 'ok', title: 'Validacion OK', text: 'CUIT validado en Padron ARCA.' },
        invalid: { type: 'error', title: 'Validacion invalida', text: 'El CUIT no es valido para Factura A o no corresponde a RI.' },
        pending: { type: 'warn', title: 'Validacion pendiente', text: 'ARCA no respondio. La solicitud queda pendiente y podes reintentar mas tarde.' },
      };
      const result = map[status] || { type: 'ok', title: 'Listo', text: 'Revalidacion completada.' };

      showProcessingResult({
        type: result.type,
        title: result.title,
        text: result.text,
        hint: '',
        okText: 'Cerrar',
        onOk: () => {
          if (redirect) window.location.href = redirect;
          else window.location.reload();
        },
      });
    } catch (e) {
      hideProcessing();
      pushToast({ type: 'error', text: e && e.message ? e.message : 'No se pudo revalidar.' });
    }
  });
}

export function bindFiscalEmitFacturaA(ctx) {
  const {
    postForm,
    showProcessing,
    showProcessingResult,
    hideProcessing,
    pushToast,
  } = ctx;

  const form = document.querySelector('form[action$="/admin/fiscal/requests/emit-a"]');
  if (!form) return;

  form.addEventListener('submit', async (ev) => {
    if (ev.defaultPrevented) return;
    ev.preventDefault();

    showProcessing({
      title: 'Emitiendo Factura A...',
      text: 'Estamos solicitando CAE a ARCA. Esto puede tardar unos segundos. No cierres esta ventana.',
    });

    try {
      const data = await postForm(form.action, form);
      showProcessingResult({
        type: data && data.ok ? 'ok' : 'error',
        title: data && data.ok ? 'Listo' : 'No se pudo emitir',
        text: data && data.message ? String(data.message) : (data && data.ok ? 'Factura A emitida.' : 'No se pudo emitir la Factura A.'),
        hint: '',
        okText: 'Cerrar',
        onOk: () => {
          const redirect = data && data.redirect ? String(data.redirect) : '';
          if (redirect) window.location.href = redirect;
          else window.location.reload();
        },
      });
    } catch (e) {
      hideProcessing();
      pushToast({ type: 'error', text: e && e.message ? e.message : 'No se pudo emitir la Factura A.' });
    }
  });
}
