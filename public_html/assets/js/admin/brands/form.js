/**
 * Marcas Admin V3: mejora local del formulario.
 * No modifica hooks ni servicios del editor SEO compartido.
 */
(() => {
  const form = document.querySelector('[data-brands-editor]');
  if (!form) return;

  const root = form.closest('.av3-brands--form');
  const groups = [
    ['bullets', 'Todavía no hay puntos clave.'],
    ['specs', 'Todavía no hay especificaciones.'],
    ['faq', 'Todavía no hay preguntas frecuentes.'],
  ];

  // El parcial compartido imprime una fila vacía por grupo. En Marcas se
  // conserva en DOM/POST y sólo se contrae visualmente en espacios estrechos.
  groups.forEach(([name, message]) => {
    const list = form.querySelector('[data-seo-list="' + name + '"]');
    if (!list) return;
    const card = list.parentElement;
    const addButton = card.querySelector('[data-seo-add]');
    if (!addButton) return;

    const rowHasText = (row) => [...row.querySelectorAll('input,textarea')]
      .some(field => field.value.trim() !== '');
    const rows = [...list.querySelectorAll('[data-seo-row]')];
    if (rows.length !== 1 || rowHasText(rows[0])) return;

    const empty = document.createElement('p');
    empty.className = 'av3-brands__block-empty';
    empty.textContent = message;
    list.before(empty);
    card.classList.add('av3-brands__block-collapsed');

    // Captura antes del manejador del editor compartido: primer clic en
    // Agregar abre la fila ya existente, sin duplicar un campo vacío.
    addButton.addEventListener('click', event => {
      if (!card.classList.contains('av3-brands__block-collapsed')) return;
      if (getComputedStyle(list).display !== 'none') return;
      event.preventDefault();
      event.stopPropagation();
      card.classList.remove('av3-brands__block-collapsed');
      empty.hidden = true;
      list.querySelector('input,textarea')?.focus();
    }, true);
  });

  // Guardado contextual al modificar cualquier campo (incluye SEO dinámico).
  // Conservamos la acción de submit de siempre, con requestSubmit y validación.
  const primary = form.querySelector('[data-brands-savebar] button[type="submit"]');
  if (!primary) return;
  const bar = document.createElement('div');
  bar.className = 'av3-brands__floating-actions';
  bar.setAttribute('role', 'region');
  bar.setAttribute('aria-label', 'Guardar cambios de marca');
  bar.hidden = true;

  const notice = document.createElement('span');
  notice.textContent = 'Cambios sin guardar';
  const actions = document.createElement('div');
  const save = document.createElement('button');
  save.type = 'button';
  save.className = 'av3-ui-btn av3-ui-btn--primary';
  save.textContent = primary.textContent.trim();
  save.addEventListener('click', () => form.requestSubmit(primary));
  actions.appendChild(save);
  bar.append(notice, actions);
  root.appendChild(bar);

  let dirty = false;
  let footerVisible = false;
  const sync = () => { bar.hidden = !dirty || footerVisible; };
  const markDirty = () => { dirty = true; sync(); };
  const footer = form.querySelector('[data-brands-savebar]');
  if ('IntersectionObserver' in window && footer) {
    const observer = new IntersectionObserver(([entry]) => {
      footerVisible = entry.isIntersecting;
      sync();
    }, { threshold: 0 });
    observer.observe(footer);
  }
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('submit', () => { dirty = false; sync(); });
})();
