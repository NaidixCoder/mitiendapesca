from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\js\admin\core\sidebar.js')
s=p.read_text(encoding='utf8')
needle="  const DESKTOP_MIN_WIDTH = 1200;"
add='''  const mobileButton = document.getElementById('av3-mobile-menu-toggle');
  const backdrop = document.querySelector('[data-av3-mobile-backdrop]');
  const sidebar = document.querySelector('[data-av3-sidebar]');
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
  document.addEventListener('keydown', (event) => {
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

'''
assert needle in s
s=s.replace(needle,add+needle,1)
p.write_text(s,encoding='utf8')
print('SIDEBAR_MOBILE_SCRIPT_UPDATED')
