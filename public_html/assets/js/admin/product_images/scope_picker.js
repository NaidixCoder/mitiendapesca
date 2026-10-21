// admin/product_images/scope_picker.js

function qs(root, sel) { return root ? root.querySelector(sel) : null; }
function qsa(root, sel) { return root ? Array.from(root.querySelectorAll(sel)) : []; }

function setExpanded(btn, expanded) {
  if (!btn) return;
  btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
}

export function initScopePicker() {
  const root = document.querySelector('[data-scope-picker]');
  if (!root || root.dataset.bound === '1') return;
  root.dataset.bound = '1';

  const trigger = qs(root, '[data-scope-trigger]');
  const menu = qs(root, '[data-scope-menu]');
  const search = qs(root, '[data-scope-search]');
  const items = () => qsa(root, '[data-scope-item]');

  if (!trigger || !menu) return;
  const isDisabled = (trigger.getAttribute('aria-disabled') === 'true') || trigger.classList.contains('cursor-not-allowed');
  if (isDisabled) return;

  const open = () => {
    menu.classList.remove('av3-hidden');
    setExpanded(trigger, true);
    // reset filtro
    if (search) {
      search.value = '';
      items().forEach((b) => b.classList.remove('av3-hidden'));
      setTimeout(() => search.focus(), 0);
    }
  };

  const close = () => {
    menu.classList.add('av3-hidden');
    setExpanded(trigger, false);
  };

  const isOpen = () => !menu.classList.contains('av3-hidden');

  trigger.addEventListener('click', (e) => {
    e.preventDefault();
    if (isOpen()) close();
    else open();
  });

  // Accesibilidad: abrir/cerrar con Enter/Espacio
  trigger.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (isOpen()) close();
      else open();
    }
  });

  // Filtrado (sin generar HTML)
  if (search) {
    search.addEventListener('input', () => {
      const q = (search.value || '').trim().toLowerCase();
      items().forEach((b) => {
        const hay = (b.dataset.search || b.textContent || '').toLowerCase();
        b.classList.toggle('av3-hidden', q !== '' && !hay.includes(q));
      });
    });
  }

  // Navegar al scope elegido
  root.addEventListener('click', (e) => {
    const btn = e.target?.closest?.('[data-scope-item]');
    if (!btn) return;
    e.preventDefault();

    const vid = String(btn.dataset.variantId || '').trim();
    try {
      const url = new URL(window.location.href);
      if (vid && vid !== '0') url.searchParams.set('img_variant_id', vid);
      else url.searchParams.delete('img_variant_id');
      url.searchParams.set('tab', 'imagenes');
      url.hash = '';
      window.location.assign(url.toString());
    } catch (_) {}
  });

  // Cerrar al click afuera / escape
  document.addEventListener('click', (e) => {
    if (!isOpen()) return;
    if (root.contains(e.target)) return;
    close();
  }, { capture: true });

  document.addEventListener('keydown', (e) => {
    if (!isOpen()) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      close();
      trigger.focus?.();
    }
  });
}
