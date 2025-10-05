// Toggle de densidad (sin inline)
(() => {
  const LS_KEY = 'gridDensity';
  const grid = document.getElementById('catalog-grid');
  if (!grid) return;

  const sets = {
    dense: { grid:['grid-cols-2','sm:grid-cols-3','lg:grid-cols-5','gap-2','sm:gap-3','lg:gap-4'], cardAdd:['p-2'] },
    comfy: { grid:['grid-cols-2','sm:grid-cols-3','lg:grid-cols-4','gap-3','sm:gap-4','lg:gap-6'], cardAdd:['p-3','lg:p-4'] }
  };

  function apply(mode){
    const cfg = sets[mode] || sets.comfy;
    grid.className = 'grid ' + cfg.grid.join(' ') + ' py-4';
    document.querySelectorAll('.js-card').forEach(el=>{
      el.classList.remove('p-2','p-3','lg:p-4');
      el.classList.add(...cfg.cardAdd);
    });
    document.querySelectorAll('[data-density]').forEach(b=>{
      b.setAttribute('aria-pressed', b.dataset.density===mode ? 'true':'false');
    });
    localStorage.setItem(LS_KEY, mode);
  }

  // init
  apply(localStorage.getItem(LS_KEY) || 'comfy');

  // eventos
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-density]');
    if (!btn) return;
    apply(btn.dataset.density);
  });
})();
