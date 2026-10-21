import {
    toast,
    ariaLive
} from './feedback.js';

export function setupSort({
    gallery,
    upForm
}) {
    if (!gallery || gallery.dataset.sortBound === '1') return;
    gallery.dataset.sortBound = '1';

    const sortUrl = gallery.dataset.sortUrl || '';
    const getOrder = () => Array.from(gallery.querySelectorAll('figure')).map(f => +f.dataset.id);

    let srcEl = null; // figure arrastrado
    let overEl = null; // figure bajo el puntero
    let startOrder = [];

    // Visual helpers
    const setDragging = (fig, on) => {
        if (!fig) return;
        fig.classList.toggle('av3-opacity-60', !!on);
        fig.setAttribute('aria-grabbed', on ? 'true' : 'false');
    };

    // Delegado: permitir drag desde handle o desde todo el figure
    gallery.addEventListener('dragstart', (e) => {
        const fig = e.target.closest('figure');
        if (!fig) return;

        // Si hay handle, drag solo desde handle
        /* allow drag from anywhere (handle optional) */

        srcEl = fig;
        startOrder = getOrder();
        setDragging(srcEl, true);
        e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setDragImage(fig, fig.clientWidth/2, fig.clientHeight/2); } catch {}
        // Firefox: se requiere data
        e.dataTransfer.setData('text/plain', fig.dataset.id);
    });

    gallery.addEventListener('dragover', (e) => {
        if (!srcEl) return;
        e.preventDefault(); // necesario para permitir drop
        const fig = e.target.closest('figure');
        if (!fig || fig === srcEl) return;

        overEl = fig;
        const children = Array.from(gallery.querySelectorAll('figure'));
        const srcIndex = children.indexOf(srcEl);
        const overIndex = children.indexOf(overEl);

        // Insertar antes o después según posición
        if (srcIndex < overIndex) {
            overEl.after(srcEl);
        } else {
            overEl.before(srcEl);
        }
    });

    const finish = async () => {
        setDragging(srcEl, false);
        const newOrder = getOrder();
        const changed = JSON.stringify(newOrder) !== JSON.stringify(startOrder);
        const pid = +(upForm?.querySelector('input[name="product_id"]')?.value || 0);
        const csrf = upForm?.querySelector('input[name="_csrf"]')?.value || '';
        // El alcance para ordenar lo define la vista (filtro) y se expone en dataset.
        const variantId = (gallery.dataset.variantId || '').trim();

        srcEl = null;
        overEl = null;

        if (!changed || !sortUrl || !pid) return;

        // Persistir
        const fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('product_id', String(pid))
        if (variantId !== '') fd.append('variant_id', variantId);
        newOrder.forEach(id => fd.append('order[]', String(id)));

        try {
            const res = await fetch(sortUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json, text/html;q=0.9'
                }
            });
            let ok = res.ok;
            try {
                const j = await res.json();
                if (j && j.ok === false) ok = false;
            } catch {}
            if (!ok) throw new Error('bad response');
            toast.success('Orden guardado');
            ariaLive.say('Orden de imágenes guardado.');
        } catch (err) {
            // Revertir DOM si falló
            const map = new Map();
            for (const f of gallery.querySelectorAll('figure')) map.set(+f.dataset.id, f);
            // limpiar y reinsertar en orden anterior
            gallery.innerHTML = '';
            startOrder.forEach(id => {
                const el = map.get(id);
                if (el) gallery.appendChild(el);
            });
            toast.error('No se pudo guardar el orden');
            ariaLive.say('No se pudo guardar el orden', 'assertive');
        }
    };

    gallery.addEventListener('drop', (e) => {
        e.preventDefault();
        finish();
    });
    gallery.addEventListener('dragend', () => finish());
}