// Confirmaciones de acciones que pueden iniciar envíos reales.
export function init() {
  const root = document.querySelector('[data-email-campaign-detail]');
  if (!root || root.dataset.confirmInit === '1') return;
  root.dataset.confirmInit = '1';
  root.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    let message = '';
    if (form.matches('[data-confirm-schedule-email]')) {
      message = '¿Programar este envío ahora? Los mensajes podrán comenzar a enviarse a destinatarios reales.';
    } else if (form.matches('[data-confirm-cancel-email]')) {
      message = '¿Cancelar este envío? Los mensajes pendientes se descartarán, pero los ya enviados no pueden recuperarse.';
    }
    if (message && !window.confirm(message)) event.preventDefault();
  });
}
