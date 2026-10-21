export function init() {
  const form = document.querySelector('[data-close-other-sessions-form]');
  const btn = document.querySelector('[data-close-other-sessions-button]');
  const modal = document.getElementById('modal-close-other-sessions');
  if (!form || !btn || !modal) return;

  const backdrop = modal.querySelector('[data-modal-backdrop]');
  const cancel = modal.querySelector('[data-modal-cancel]');
  const confirm = modal.querySelector('[data-modal-confirm]');

  const open = () => {
    modal.classList.remove('ui-is-hidden');
    modal.setAttribute('aria-hidden', 'false');
  };
  const close = () => {
    modal.classList.add('ui-is-hidden');
    modal.setAttribute('aria-hidden', 'true');
  };

  // Intercepta submit para mostrar modal (pero si JS falla, el form igual funciona)
  btn.addEventListener('click', (e) => {
    // Solo en click primario
    if (e) e.preventDefault();
    open();
  });

  backdrop?.addEventListener('click', close);
  cancel?.addEventListener('click', close);

  confirm?.addEventListener('click', () => {
    // Evitamos dobles envíos
    confirm.disabled = true;
    form.submit();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('ui-is-hidden')) close();
  });
}
