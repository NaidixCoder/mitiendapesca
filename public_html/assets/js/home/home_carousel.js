function initCarousel(root) {
  if (!root || root.dataset.carouselBound === '1') return;
  root.dataset.carouselBound = '1';

  const track = root.querySelector('[data-home-carousel-track]');
  const slides = Array.from(root.querySelectorAll('[data-home-carousel-slide]'));
  const dots = Array.from(root.querySelectorAll('[data-home-carousel-dot]'));
  const prevBtn = root.querySelector('[data-home-carousel-prev]');
  const nextBtn = root.querySelector('[data-home-carousel-next]');
  const intervalMs = Math.max(1200, parseInt(root.getAttribute('data-home-carousel-interval') || '3000', 10) || 3000);
  const mobileOnly = root.getAttribute('data-home-carousel-mobile-only') === '1';
  const mobileMq = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;
  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!track || slides.length <= 1) return;

  let current = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
  if (current < 0) current = 0;
  let timer = 0;

  const isEnabled = () => !mobileOnly || !mobileMq || !!mobileMq.matches;

  const setActive = (idx) => {
    if (!isEnabled()) return;
    current = (idx + slides.length) % slides.length;
    track.style.transform = `translateX(-${current * 100}%)`;
    slides.forEach((slide, i) => {
      const active = i === current;
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
    });
    dots.forEach((dot, i) => {
      const active = i === current;
      dot.classList.toggle('is-active', active);
      dot.setAttribute('aria-current', active ? 'true' : 'false');
    });
  };

  const stopAuto = () => {
    if (!timer) return;
    window.clearInterval(timer);
    timer = 0;
  };

  const startAuto = () => {
    if (!isEnabled() || prefersReducedMotion) return;
    stopAuto();
    timer = window.setInterval(() => setActive(current + 1), intervalMs);
  };

  const syncEnabledState = () => {
    if (isEnabled()) {
      setActive(current);
      startAuto();
      return;
    }
    stopAuto();
    track.style.transform = '';
    slides.forEach((slide) => {
      slide.classList.remove('is-active');
      slide.removeAttribute('aria-hidden');
    });
    dots.forEach((dot) => {
      dot.classList.remove('is-active');
      dot.setAttribute('aria-current', 'false');
    });
  };

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      setActive(current + 1);
      startAuto();
    });
  }
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      setActive(current - 1);
      startAuto();
    });
  }
  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => {
      setActive(idx);
      startAuto();
    });
  });

  let touchStartX = 0;
  let touchEndX = 0;
  const swipeThreshold = 40;

  root.addEventListener('touchstart', (ev) => {
    if (!isEnabled()) return;
    touchStartX = ev.changedTouches && ev.changedTouches[0] ? ev.changedTouches[0].clientX : 0;
    touchEndX = touchStartX;
    stopAuto();
  }, { passive: true });

  root.addEventListener('touchmove', (ev) => {
    if (!isEnabled()) return;
    touchEndX = ev.changedTouches && ev.changedTouches[0] ? ev.changedTouches[0].clientX : touchEndX;
  }, { passive: true });

  root.addEventListener('touchend', () => {
    if (!isEnabled()) return;
    const delta = touchEndX - touchStartX;
    if (Math.abs(delta) >= swipeThreshold) {
      if (delta < 0) setActive(current + 1);
      if (delta > 0) setActive(current - 1);
    }
    startAuto();
  }, { passive: true });

  root.addEventListener('mouseenter', stopAuto);
  root.addEventListener('mouseleave', startAuto);
  root.addEventListener('focusin', stopAuto);
  root.addEventListener('focusout', startAuto);

  if (mobileMq) {
    const onMqChange = () => syncEnabledState();
    if (typeof mobileMq.addEventListener === 'function') {
      mobileMq.addEventListener('change', onMqChange);
    } else if (typeof mobileMq.addListener === 'function') {
      mobileMq.addListener(onMqChange);
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stopAuto();
    else startAuto();
  });

  syncEnabledState();
}

export function init() {
  const roots = Array.from(document.querySelectorAll('[data-home-carousel]'));
  if (!roots.length) return;
  roots.forEach(initCarousel);
}
