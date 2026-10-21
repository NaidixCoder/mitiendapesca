export function init() {
(function () {
      const roots = document.querySelectorAll('[x-auth-menu]');
      if (!roots.length) return;

      roots.forEach((root) => {
          const btn = root.querySelector('[data-auth-trigger]');
          const menu = root.querySelector('[data-auth-menu]');
          if (!btn || !menu) return;
          if (root.dataset.authBound === '1') return;
          root.dataset.authBound = '1';

          function close() {
              menu.hidden = true;
              btn.setAttribute('aria-expanded', 'false');
          }

          function open() {
              document.querySelectorAll('[x-auth-menu] [data-auth-menu]').forEach((otherMenu) => {
                  if (otherMenu !== menu) {
                      otherMenu.hidden = true;
                      const otherBtn = otherMenu.parentElement?.querySelector('[data-auth-trigger]');
                      if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                  }
              });
              menu.hidden = false;
              btn.setAttribute('aria-expanded', 'true');
          }

          btn.addEventListener('click', () => (menu.hidden ? open() : close()));
          btn.addEventListener('keydown', (e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  menu.hidden ? open() : close();
              }
          });
      });

      function onDocClick(e) {
          roots.forEach((root) => {
              if (!root.contains(e.target)) {
                  const btn = root.querySelector('[data-auth-trigger]');
                  const menu = root.querySelector('[data-auth-menu]');
                  if (btn && menu) {
                      menu.hidden = true;
                      btn.setAttribute('aria-expanded', 'false');
                  }
              }
          });
      }

      function onKey(e) {
          if (e.key !== 'Escape') return;
          roots.forEach((root) => {
              const btn = root.querySelector('[data-auth-trigger]');
              const menu = root.querySelector('[data-auth-menu]');
              if (btn && menu) {
                  menu.hidden = true;
                  btn.setAttribute('aria-expanded', 'false');
              }
          });
      }

      document.addEventListener('click', onDocClick);
      document.addEventListener('keydown', onKey);
  })();

  // Header sticky: elevación solo al scrollear (mobile/desktop)
  (function () {
      const header = document.querySelector('[data-site-header]');
      if (!header) return;

      let ticking = false;
      const apply = () => {
          const scrolled = (window.scrollY || document.documentElement.scrollTop || 0) > 6;
          header.classList.toggle('tp-header--scrolled', scrolled);
          ticking = false;
      };

      const onScroll = () => {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(apply);
      };

      apply();
      window.addEventListener('scroll', onScroll, { passive: true });
  })();
}
