// Admin → Marketing → Email → Templates (Etapa 4)

export function init() {
  // Confirmación de eliminación
  document.addEventListener('submit', (e) => {
    const form = e.target?.closest?.('form[data-confirm-delete-template]');
    if (!form) return;
    const ok = confirm('¿Eliminar la plantilla? Esta acción no se puede deshacer.');
    if (!ok) e.preventDefault();
  });
}
