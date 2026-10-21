// Búsquedas recientes (localStorage) — Tienda Pesca
// - No logueado: se guarda localmente (no viaja al servidor)
// - Se hidrata en /productos (chips)

const KEY = 'tp_recent_searches_v1';
const MAX = 8;

function normalize(term) {
  if (typeof term !== 'string') return '';
  return term.trim().replace(/\s+/g, ' ');
}

function load() {
  try {
    const raw = localStorage.getItem(KEY);
    const arr = raw ? JSON.parse(raw) : [];
    return Array.isArray(arr) ? arr.filter((x) => typeof x === 'string') : [];
  } catch {
    return [];
  }
}

function save(list) {
  try {
    localStorage.setItem(KEY, JSON.stringify(list.slice(0, MAX)));
  } catch {
    // ignore (modo privado / storage full)
  }
}

function add(term) {
  const t = normalize(term);
  if (!t || t.length < 2) return;
  const list = load();
  const next = [t, ...list.filter((x) => x.toLowerCase() !== t.toLowerCase())];
  save(next);
}

function clear() {
  try { localStorage.removeItem(KEY); } catch {}
}

function buildUrl(basePath, q) {
  const url = new URL(basePath, window.location.origin);
  url.searchParams.set('q', q);
  return url.pathname + url.search;
}

function renderChips() {
  const wrappers = document.querySelectorAll('[data-recent-searches-wrapper]');
  if (!wrappers.length) return;

  const list = load();

  wrappers.forEach((wrapper) => {
    const holder = wrapper.querySelector('[data-recent-searches]');
    if (!holder) return;

    const basePath = holder.getAttribute('data-base-path') || '/productos';

    // Vacío => oculto
    if (!list.length) {
      wrapper.hidden = true;
      holder.innerHTML = '';
      return;
    }

    wrapper.hidden = false;
    holder.innerHTML = '';

    list.slice(0, MAX).forEach((q) => {
      const a = document.createElement('a');
      a.href = buildUrl(basePath, q);
      a.textContent = q;
      a.className = 'site-recent-search-chip';
      holder.appendChild(a);
    });

    const btnClear = wrapper.querySelector('[data-recent-searches-clear]');
    if (btnClear && !btnClear.__bound) {
      btnClear.__bound = true;
      btnClear.addEventListener('click', () => {
        clear();
        renderChips();
      });
    }
  });
}

function captureFromLocation() {
  // Captura desde /productos?q=...
  try {
    const p = (window.location.pathname || '');
    if (!p.endsWith('/productos') && !p.includes('/productos')) return;
    const qs = new URLSearchParams(window.location.search || '');
    const q = qs.get('q') || '';
    add(q);
  } catch {}
}

function bindSearchForms() {
  // Captura en cualquier página (header search)
  const forms = document.querySelectorAll('form[data-search-form="productos"]');
  forms.forEach((form) => {
    if (form.__bound) return;
    form.__bound = true;
    form.addEventListener('submit', () => {
      const input = form.querySelector('input[name="q"]');
      if (!input) return;
      add(input.value || '');
    });
  });
}

export function init() {
  bindSearchForms();
  captureFromLocation();
  renderChips();
}
