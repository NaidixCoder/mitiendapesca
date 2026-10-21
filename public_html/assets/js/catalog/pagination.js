// catalog/pagination.js
export function init() {
  function ensureHash(a) {
    try {
      const url = new URL(a.href, window.location.href);
      if (!url.hash) {
        const targetId = document.getElementById('catalog-grid')
          ? 'catalog-grid'
          : (document.getElementById('catalog-main') ? 'catalog-main' : '');
        if (targetId) {
          url.hash = '#' + targetId;
          a.href = url.toString();
        }
      }
    } catch {}
  }

  function setup() {
    let links = Array.from(document.querySelectorAll('a[rel="prev"], a[rel="next"]'));
    const pagContainer = document.querySelector('[data-pagination]');
    if (pagContainer) links = links.concat(Array.from(pagContainer.querySelectorAll('a[href]')));
    links.forEach(ensureHash);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
}
