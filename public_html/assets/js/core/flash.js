// Core flash/toast behavior (always safe)
export function init() {
  (function(){
// --- original flash.js start ---
(function () {
    const root = document.getElementById('toast-root');
    if (!root) return;

    // Easing suave
    const enter = (el) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px) scale(0.98)';
        requestAnimationFrame(() => {
            el.style.transition = 'opacity .18s ease, transform .22s cubic-bezier(.2,.8,.2,1)';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0) scale(1)';
        });
    };

    const leave = (el) => {
        el.style.transition = 'opacity .16s ease, transform .16s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-4px) scale(0.98)';
        setTimeout(() => el.remove(), 170);
    };

    root.querySelectorAll('.toast').forEach((toast, idx) => {
        const btnClose = toast.querySelector('.toast-close');
        const bar = toast.querySelector('.toast-progress');
        const durAttr = parseInt(toast.getAttribute('data-duration') || '500', 10);
        const duration = isNaN(durAttr) ? 500 : Math.max(2000, durAttr); // 2s mínimo

        // Stacking sutil
        toast.style.willChange = 'transform, opacity';
        toast.style.transformOrigin = 'top right';
        toast.style.marginTop = idx ? '0.35rem' : '0';
        enter(toast);

        // Progress
        let start = performance.now();
        let rafId;
        const tick = (now) => {
            const elapsed = now - start;
            const p = Math.max(0, 1 - elapsed / duration);
            if (bar) bar.style.width = (p * 100).toFixed(2) + '%';
            if (elapsed >= duration) {
                leave(toast);
            } else {
                rafId = requestAnimationFrame(tick);
            }
        };
        rafId = requestAnimationFrame(tick);

        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            if (rafId) cancelAnimationFrame(rafId);
        });
        toast.addEventListener('mouseleave', () => {
            start = performance.now() - (duration * (1 - parseFloat(bar.style.width) / 100));
            rafId = requestAnimationFrame(tick);
        });

        // Manual close
        if (btnClose) btnClose.addEventListener('click', () => leave(toast));
    });
})();
// --- original flash.js end ---
  })();
}
