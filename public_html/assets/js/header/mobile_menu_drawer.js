export function init() {
  function lockBodyScroll() {
    const body = document.body;
    const count = Number(body.dataset.phScrollLockCount || '0');
    if (count === 0) {
      const scrollbar = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
      body.dataset.phPrevPaddingRight = body.style.paddingRight || '';
      if (scrollbar > 0) {
        body.style.paddingRight = `${scrollbar}px`;
      }
      body.classList.add('ph-no-scroll');
    }
    body.dataset.phScrollLockCount = String(count + 1);
  }

  function unlockBodyScroll() {
    const body = document.body;
    const count = Number(body.dataset.phScrollLockCount || '0');
    if (count <= 1) {
      body.classList.remove('ph-no-scroll');
      body.style.paddingRight = body.dataset.phPrevPaddingRight || '';
      delete body.dataset.phPrevPaddingRight;
      delete body.dataset.phScrollLockCount;
      return;
    }
    body.dataset.phScrollLockCount = String(count - 1);
  }

  const root = document.querySelector('[data-ph-header-root]');
  if (!root || root.dataset.phMenuBound === '1') return;

  const drawer = root.querySelector('[data-ph-menu-drawer]');
  const overlay = root.querySelector('[data-ph-menu-overlay]');
  const openers = root.querySelectorAll('[data-ph-menu-open]');
  const closers = root.querySelectorAll('[data-ph-menu-close]');

  if (!drawer || !overlay || !openers.length) return;

  root.dataset.phMenuBound = '1';
  let closeTimer = null;

  const setExpanded = (open) => {
    openers.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
  };

  const open = () => {
    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }
    overlay.hidden = false;
    drawer.hidden = false;
    drawer.classList.remove('is-open');
    overlay.classList.remove('is-open');
    void drawer.offsetWidth;
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        overlay.classList.add('is-open');
        drawer.classList.add('is-open');
      });
    });
    lockBodyScroll();
    setExpanded(true);
  };

  const close = () => {
    overlay.classList.remove('is-open');
    drawer.classList.remove('is-open');
    closeTimer = setTimeout(() => {
      overlay.hidden = true;
      drawer.hidden = true;
      closeTimer = null;
    }, 360);
    unlockBodyScroll();
    setExpanded(false);
  };

  openers.forEach((el) => {
    el.addEventListener('click', (ev) => {
      ev.preventDefault();
      if (drawer.hidden) {
        open();
      } else {
        close();
      }
    });
  });

  closers.forEach((el) => el.addEventListener('click', close));
  overlay.addEventListener('click', close);

  drawer.addEventListener('click', (ev) => {
    const link = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
    if (link) close();
  });

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && !drawer.hidden) {
      close();
    }
  });
}
