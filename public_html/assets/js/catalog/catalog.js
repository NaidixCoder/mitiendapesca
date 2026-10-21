// public_html/assets/js/catalog/catalog.js
// Orquesta la página de catálogo: filtros + búsqueda + densidad.

import { init as initFilters } from './filters.js';
import { init as initSearchbar } from './searchbar.js';
// ⚠️ cache-bust específico: este módulo es crítico para P0 y debe recargarse al desplegar fixes.
import { init as initAddToCartCards } from './add_to_cart_cards.js?v=154';

export function init() {
  const page = document.body && document.body.dataset ? document.body.dataset.page : '';
  // Históricamente algunas vistas usan "catalogo" como pageId.
  // Además, evitamos romper P0: si detectamos la grilla del catálogo, inicializamos igual.
  const looksLikeCatalog = !!(
    document.getElementById('catalog-grid') ||
    document.querySelector('form[data-cart-add-card]') ||
    document.querySelector('[data-quick-add-trigger]')
  );
  if (!looksLikeCatalog && page !== 'catalog' && page !== 'search' && page !== 'catalogo' && page !== 'catalogo-search') return;

  // ⚠️ Nunca dejar que un módulo rompa el resto (P0: agregar al carrito debe vivir siempre)
  try { initAddToCartCards(); } catch (e) { console.warn('catalog:addToCart', e); }
  try { initFilters(); } catch (e) { console.warn('catalog:filters', e); }
  try { initSearchbar(); } catch (e) { console.warn('catalog:searchbar', e); }
  // Densidad desactivada (coherencia visual). Rehabilitar cuando se defina el sistema final.
}
