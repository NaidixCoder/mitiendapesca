// Admin: Auditoría (filtros UX)

export function init() {
  const form = document.querySelector('form[data-audit-filters]');
  if (!form) return;

  const rangeSel = form.querySelector('[data-audit-range]');
  const datesBox = form.querySelector('[data-audit-dates]');
  const fromEl = form.querySelector('[data-audit-from]');
  const toEl = form.querySelector('[data-audit-to]');
  if (!rangeSel || !datesBox || !fromEl || !toEl) return;

  const sync = () => {
    const isCustom = String(rangeSel.value || '') === 'custom';
    datesBox.classList.toggle('is-hidden', !isCustom);
    fromEl.disabled = !isCustom;
    toEl.disabled = !isCustom;

    // Si el usuario sale de "Personalizado", limpiamos fechas para evitar filtros ocultos.
    if (!isCustom) {
      if (fromEl.value) fromEl.value = '';
      if (toEl.value) toEl.value = '';
    }
  };

  sync();
  rangeSel.addEventListener('change', sync);
}
