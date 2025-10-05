(function () {
    const $main = document.getElementById('pg-main');
    if (!$main) return;

    const $thumbsWrap = document.getElementById('pg-thumbs');
    const $prev = document.querySelector('.pg-prev');
    const $next = document.querySelector('.pg-next');

    const $thumbs = $thumbsWrap ? Array.from($thumbsWrap.querySelectorAll('.pg-thumb')) : [];
    const imgs = $thumbs.length ?
        $thumbs.map(btn => btn.querySelector('img').getAttribute('src')) :
        [$main.getAttribute('src')];

    let idx = parseInt($main.getAttribute('data-idx') || '0', 10);

    const show = (i) => {
        idx = (i + imgs.length) % imgs.length;
        $main.src = imgs[idx];
        $main.setAttribute('data-idx', String(idx));
        // estado visual de thumbs
        $thumbs.forEach((b, k) => {
            if (k === idx) b.classList.add('ring-2', 'ring-brand');
            else b.classList.remove('ring-2', 'ring-brand');
        });
    };

    // Thumbs
    $thumbs.forEach(btn => {
        btn.addEventListener('click', () => {
            const i = parseInt(btn.getAttribute('data-idx') || '0', 10);
            show(i);
        });
    });

    // Prev/Next
    if ($prev) $prev.addEventListener('click', () => show(idx - 1));
    if ($next) $next.addEventListener('click', () => show(idx + 1));

    // Teclado
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') show(idx - 1);
        if (e.key === 'ArrowRight') show(idx + 1);
    });

    // Swipe básico
    let sx = 0,
        sy = 0;
    $main.addEventListener('touchstart', (e) => {
        const t = e.changedTouches[0];
        sx = t.screenX;
        sy = t.screenY;
    }, {
        passive: true
    });
    $main.addEventListener('touchend', (e) => {
        const t = e.changedTouches[0];
        const dx = t.screenX - sx,
            dy = t.screenY - sy;
        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 30) {
            if (dx < 0) show(idx + 1);
            else show(idx - 1);
        }
    }, {
        passive: true
    });

    // Preload adyacentes
    const preload = (url) => {
        const img = new Image();
        img.src = url;
    };
    $main.addEventListener('load', () => {
        preload(imgs[(idx + 1) % imgs.length]);
        preload(imgs[(idx - 1 + imgs.length) % imgs.length]);
    });
})();