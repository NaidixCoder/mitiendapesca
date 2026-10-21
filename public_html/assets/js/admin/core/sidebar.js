export function initAdminSidebarV3() {
  const shell = document.body;
  const btn = document.getElementById('av3-toggle');
  if (!shell || !btn) return;
  // app.js puede invocar este modulo desde el arranque y desde el fallback.
  // Evitar dos listeners que reviertan el mismo clic inmediatamente.
  if (btn.dataset.adminSidebarInitialized === '1') return;
  btn.dataset.adminSidebarInitialized = '1';

  const mobileButton = document.getElementById('av3-mobile-menu-toggle');
  const backdrop = document.querySelector?.('[data-av3-mobile-backdrop]');
  const sidebar = document.querySelector?.('[data-av3-sidebar]');
  const mobileMedia = window.matchMedia('(max-width: 767px)');
  let restoreFocus = null;

  function closeMobileMenu(returnFocus = false) {
    shell.classList.remove('av3-mobile-nav-open');
    if (backdrop) backdrop.hidden = true;
    if (mobileButton) {
      mobileButton.setAttribute('aria-expanded', 'false');
      mobileButton.setAttribute('aria-label', 'Abrir menú de navegación');
    }
    if (returnFocus && restoreFocus?.isConnected) restoreFocus.focus();
    restoreFocus = null;
  }

  function openMobileMenu() {
    if (!mobileMedia.matches || !sidebar || !mobileButton || !backdrop) return;
    restoreFocus = document.activeElement;
    shell.classList.add('av3-mobile-nav-open');
    backdrop.hidden = false;
    mobileButton.setAttribute('aria-expanded', 'true');
    mobileButton.setAttribute('aria-label', 'Cerrar menú de navegación');
    const firstLink = sidebar.querySelector('a, button:not(#av3-toggle)');
    firstLink?.focus();
  }

  mobileButton?.addEventListener('click', () => {
    if (shell.classList.contains('av3-mobile-nav-open')) closeMobileMenu(true);
    else openMobileMenu();
  });
  backdrop?.addEventListener('click', () => closeMobileMenu(true));
  document.addEventListener?.('keydown', (event) => {
    if (!mobileMedia.matches || !shell.classList.contains('av3-mobile-nav-open')) return;
    if (event.key === 'Escape') { event.preventDefault(); closeMobileMenu(true); }
    if (event.key === 'Tab' && sidebar) {
      const focusables = [...sidebar.querySelectorAll('a[href], button:not([disabled]):not(#av3-toggle)')];
      if (!focusables.length) return;
      const first = focusables[0], last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  sidebar?.addEventListener('click', event => {
    if (mobileMedia.matches && event.target.closest('a[href]')) closeMobileMenu();
  });
  if (typeof mobileMedia.addEventListener === 'function') mobileMedia.addEventListener('change', () => closeMobileMenu());
  else mobileMedia.addListener?.(() => closeMobileMenu());

  const DESKTOP_MIN_WIDTH = 1200;
  const STORAGE_KEY = 'admin_v2_sidebar_state';
  const mq = window.matchMedia(`(min-width: ${DESKTOP_MIN_WIDTH}px)`);

  function defaultState() {
    return mq.matches
      ? 'expanded'
      : 'collapsed';
  }

  function readState() {
    try {
      const saved = window.localStorage.getItem(STORAGE_KEY);
      if (saved === 'expanded' || saved === 'collapsed') return saved;
    } catch (_) {}
    return defaultState();
  }

  function apply(state) {
    const collapsed = state === 'collapsed';
    shell.classList.toggle('av3-collapsed', collapsed);
    shell.classList.toggle('av3-expanded', !collapsed);
    btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    btn.setAttribute('aria-label', collapsed ? 'Expandir barra lateral' : 'Colapsar barra lateral');
  }

  function persist(state) {
    try {
      window.localStorage.setItem(STORAGE_KEY, state);
    } catch (_) {}
  }

  apply(readState());

  // Al cruzar el breakpoint (resize), re-aplicamos el estado por defecto del rango.
  // Dentro del mismo rango, el usuario puede seguir togglando manualmente.
  const onBreakpointChange = () => apply(readState());
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', onBreakpointChange);
  } else if (typeof mq.addListener === 'function') {
    mq.addListener(onBreakpointChange);
  }

  btn.addEventListener('click', () => {
    const next = shell.classList.contains('av3-collapsed') ? 'expanded' : 'collapsed';
    apply(next);
    persist(next);
  });
}
