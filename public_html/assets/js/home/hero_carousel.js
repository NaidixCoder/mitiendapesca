export function init() {
  const root = document.querySelector('[data-hero-carousel]');
  if (!root) return;

  const slides = Array.from(root.querySelectorAll('[data-hero-slide]'));
  const dots = Array.from(root.querySelectorAll('[data-hero-dot]'));
  const prevBtn = root.querySelector('[data-hero-prev]');
  const nextBtn = root.querySelector('[data-hero-next]');
  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!slides.length) return;

  let current = Math.max(0, slides.findIndex((el) => el.classList.contains('is-active')));
  if (current < 0) current = 0;

  const setActive = (idx) => {
    current = (idx + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      slide.classList.toggle('is-active', i === current);
    });
    dots.forEach((dot, i) => {
      const active = i === current;
      dot.classList.toggle('is-active', active);
      dot.setAttribute('aria-current', active ? 'true' : 'false');
    });
  };

  const next = () => setActive(current + 1);
  const prev = () => setActive(current - 1);

  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => setActive(idx));
  });
  if (nextBtn) nextBtn.addEventListener('click', next);
  if (prevBtn) prevBtn.addEventListener('click', prev);

  let timer = 0;
  const startAuto = () => {
    if (prefersReducedMotion) return;
    stopAuto();
    timer = window.setInterval(next, 3000);
  };
  const stopAuto = () => {
    if (timer) {
      window.clearInterval(timer);
      timer = 0;
    }
  };

  root.addEventListener('mouseenter', stopAuto);
  root.addEventListener('mouseleave', startAuto);
  root.addEventListener('focusin', stopAuto);
  root.addEventListener('focusout', startAuto);

  let touchStartX = 0;
  let touchEndX = 0;
  const swipeThreshold = 48;

  root.addEventListener('touchstart', (ev) => {
    touchStartX = ev.changedTouches && ev.changedTouches[0] ? ev.changedTouches[0].clientX : 0;
    touchEndX = touchStartX;
    stopAuto();
  }, { passive: true });

  root.addEventListener('touchmove', (ev) => {
    touchEndX = ev.changedTouches && ev.changedTouches[0] ? ev.changedTouches[0].clientX : touchEndX;
  }, { passive: true });

  root.addEventListener('touchend', () => {
    const delta = touchEndX - touchStartX;
    if (Math.abs(delta) >= swipeThreshold) {
      if (delta < 0) next();
      if (delta > 0) prev();
    }
    startAuto();
  }, { passive: true });

  if (!root.hasAttribute('tabindex')) {
    root.setAttribute('tabindex', '0');
  }
  root.addEventListener('keydown', (ev) => {
    if (ev.key === 'ArrowLeft') {
      ev.preventDefault();
      prev();
    }
    if (ev.key === 'ArrowRight') {
      ev.preventDefault();
      next();
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopAuto();
    } else {
      startAuto();
    }
  });

  setActive(current);
  startAuto();
}
