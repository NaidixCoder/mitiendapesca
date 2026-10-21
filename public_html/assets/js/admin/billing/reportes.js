export function init() {
  // Reportes fiscales: por ahora es server-render.
  // Este módulo existe para futuros atajos (presets de fechas, UI, etc.) sin inline JS.
  try {
    const body = document.body;
    if (!body) return;
    const page = body.dataset.page || '';
    if (!['admin-facturacion-reportes','admin-facturacion-reportes-pedidos-sin-factura','admin-facturacion-reportes-comprobantes-pendientes'].includes(page)) return;

    // UX menor: si el navegador soporta, enfocamos el primer campo vacío.
    const from = document.querySelector('input[name="from"]');
    const to = document.querySelector('input[name="to"]');
    if (from && !from.value) from.focus();
    else if (to && !to.value) to.focus();
  } catch (e) {
    console.warn('[admin] facturacion_reportes', e);
  }
}
