// App entrypoint (module)
// asset touch: cart feedback/header clickability fix v5

// --- Core global (pÃºblico + admin) ---
import { init as headerInit } from './core/header.js';
import { init as flashInit }  from './core/flash.js';
import { init as toTopInit }  from './core/toTop.js';
import { init as confirmInit } from './core/confirm.js';
import { init as recentSearchInit } from './tracking/recent_searches.js';
import { init as mobileSearchSheetInit } from './header/mobile_search_sheet.js';
import { init as mobileMenuDrawerInit } from './header/mobile_menu_drawer.js';
import { init as heroCarouselInit } from './home/hero_carousel.js';
import { init as homeCarouselInit } from './home/home_carousel.js';
import { initAdminSidebarV3 } from './admin/core/sidebar.js';

try { headerInit(); } catch (e) { console.warn('[core] header', e); }
try { flashInit(); } catch (e) { console.warn('[core] flash', e); }
try { toTopInit(); } catch (e) { console.warn('[core] toTop', e); }
try { confirmInit(); } catch (e) { console.warn('[core] confirm', e); }
try { recentSearchInit(); } catch (e) { console.warn('[core] recentSearch', e); }
try { mobileSearchSheetInit(); } catch (e) { console.warn('[core] mobileSearchSheet', e); }
try { mobileMenuDrawerInit(); } catch (e) { console.warn('[core] mobileMenuDrawer', e); }
try { heroCarouselInit(); } catch (e) { console.warn('[core] heroCarousel', e); }
try { homeCarouselInit(); } catch (e) { console.warn('[core] homeCarousel', e); }
try { initAdminSidebarV3(); } catch (e) { console.warn('[admin] sidebarV2', e); }

// Ã¢Å“â€¦ Cache-bust para dynamic imports:
// prioridad a version global inyectada por PHP (window.__ASSET_VERSION),
// fallback al query de app.js.
const __VER = (() => {
  try {
    const v = (typeof window !== 'undefined' && window.__ASSET_VERSION != null)
      ? String(window.__ASSET_VERSION).trim()
      : '';
    if (v !== '') return `?v=${encodeURIComponent(v)}`;
  } catch (_) {}
  return new URL(import.meta.url).search || '';
})();

// --- helper: safe import & call ---
// Ã¢Å“â€¦ Evita doble import/bind (idempotencia) cuando hay loader + fallback.
const __TP_MODULES = (window.__TP_MODULES = window.__TP_MODULES || {});

function __tpKey(path, exportName) {
  const p = String(path || '');
  const clean = p.split('?')[0];
  return clean + '::' + String(exportName || '');
}

async function callModule(path, exportName) {
  const key = __tpKey(path, exportName);
  if (__TP_MODULES[key]) return true;
  __TP_MODULES[key] = 1;
  try {
    // Ã¢Å“â€¦ si el path es relativo y no trae query, le pegamos el ?v=...
    const withVer = (typeof path === 'string' && path.startsWith('./') && path.indexOf('?') === -1)
      ? (path + __VER)
      : path;

    const m = await import(withVer);

    if (exportName && m && typeof m[exportName] === 'function') { m[exportName](); return true; }
    if (!exportName && m && typeof m.default === 'function') { m.default(); return true; }
    if (!exportName && m && m.default && typeof m.default.init === 'function') { m.default.init(); return true; }
    if (exportName === 'init' && m && typeof m.init === 'function') { m.init(); return true; }
  } catch (e) { console.warn('No se pudo cargar', path, e); }
  return false;
}



// --- Loader por pÃ¡gina pÃºblica (data-page) ---
const page = document.body && document.body.dataset ? (document.body.dataset.page || '') : '';

const loaders = {
  cart: async () => {
    (await import('./cart/cart.js' + __VER)).init();
  },

  checkout: async () => {
    (await import('./checkout/loading.js' + __VER)).init();
    (await import('./checkout/checkout_steps.js' + __VER)).init();
    (await import('./checkout/address_modal.js' + __VER)).init();
    (await import('./checkout/shipping.js' + __VER)).init();
    (await import('./checkout/mp_payment_brick.js' + __VER)).init();
    (await import('./checkout/bank_transfer.js' + __VER)).init();
  },

  'admin-product-form': async () => {
    await callModule('./admin/product_form/index.js', 'init');
    await callModule('./admin/seo/product_score.js', 'init');
    await callModule('./admin/seo/product_blocks.js', 'init');
    await callModule('./admin/product_images/index.js', 'init');
    await callModule('./admin/products/wizard.js', 'init');
  },

  'admin-seo-audit': async () => {
    await callModule('./admin/seo/audit_index.js', 'init');
  },

  'admin-category-form': async () => {
    await callModule('./admin/seo/entity_score.js', 'init');
    await callModule('./admin/seo/product_blocks.js', 'init');
  },

  'admin-brand-form': async () => {
    await callModule('./admin/seo/entity_score.js', 'init');
    await callModule('./admin/seo/product_blocks.js', 'init');
  },

  'admin-dashboard': async () => {
    await callModule('./admin/dashboard/index.js', 'initTrendWidget');
    await callModule('./admin/orders/panel.js', 'initPendingOrdersPanel');
  },

  'admin-imports': async () => {
    await callModule('./admin/imports/preview.js' + __VER, 'init');
  },

  'admin-marketing-campaigns': async () => {
    await callModule('./admin/marketing/campaigns.js' + __VER, 'init');
  },

  'admin-email-campaign-form': async () => {
    await callModule('./admin/marketing/campaign_editor/index.js', 'init');
  },

  'admin-email-campaign-show': async () => {
    await callModule('./admin/marketing/campaign_show.js', 'init');
  },

  'admin-email-templates': async () => {
    await callModule('./admin/marketing/email_templates.js' + __VER, 'init');
  },

  'admin-orders-index': async () => {
    await callModule('./admin/orders/index.js', 'initOrdersIndex');
  },
  'admin-orders-show': async () => {
    await callModule('./admin/orders/show/index.js', 'init');
  },

  'admin-settings-envios': async () => {
    await callModule('./admin/settings/envios.js' + __VER, 'init');
  },
  'admin-settings-home-hero': async () => {
    await callModule('./admin/settings/home_hero.js' + __VER, 'init');
  },
  'admin-settings-home-sections': async () => {
    await callModule('./admin/settings/home_sections.js' + __VER, 'init');
  },

  'admin-facturacion-reportes': async () => {
    await callModule('./admin/billing/reportes.js' + __VER, 'init');
  },
  'admin-facturacion-reportes-pedidos-sin-factura': async () => {
    await callModule('./admin/billing/reportes.js' + __VER, 'init');
  },
  'admin-facturacion-reportes-comprobantes-pendientes': async () => {
    await callModule('./admin/billing/reportes.js' + __VER, 'init');
  },

'admin-facturacion-cierres-mensuales': async () => {
  (await import('./admin/billing/cierres_mensuales.js' + __VER)).init();
},




  product: async () => {
    try { (await import('./product/gallery.js' + __VER)).init(); } catch (e) { console.warn('[product] gallery', e); }
    try { (await import('./product/qty.js' + __VER)).init(); } catch (e) { console.warn('[product] qty', e); }
    try { (await import('./product/variants.js' + __VER)).init(); } catch (e) { console.warn('[product] variants', e); }
    try { (await import('./product/add_to_cart.js' + __VER)).init(); } catch (e) { console.warn('[product] add_to_cart', e); }
  },

  catalog: async () => {
    try { (await import('./catalog/catalog.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/filters.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/sort.js' + __VER)).init?.(); } catch {}
    try { await import('./catalog/perpage.js' + __VER); } catch {}
  },

  // Alias histÃ³rico (algunas vistas usan data-page="catalogo")
  catalogo: async () => {
    try { (await import('./catalog/catalog.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/filters.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/sort.js' + __VER)).init?.(); } catch {}
    try { await import('./catalog/perpage.js' + __VER); } catch {}
  },

  search: async () => {
    try { (await import('./catalog/catalog.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/filters.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/sort.js' + __VER)).init?.(); } catch {}
    try { await import('./catalog/perpage.js' + __VER); } catch {}
    try { (await import('./catalog/pagination.js' + __VER)).init(); } catch {}
  },

  // Alias histÃ³rico para bÃºsquedas (si alguna vista usa data-page="catalogo-search")
  'catalogo-search': async () => {
    try { (await import('./catalog/catalog.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/filters.js' + __VER)).init?.(); } catch {}
    try { (await import('./catalog/sort.js' + __VER)).init?.(); } catch {}
    try { await import('./catalog/perpage.js' + __VER); } catch {}
    try { (await import('./catalog/pagination.js' + __VER)).init(); } catch {}
  },

  'auth-google': async () => {
    (await import('./auth/google.js' + __VER)).init();
  },

  'auth-register': async () => {
    (await import('./auth/register.js' + __VER)).init();
  },

  account: async () => {
    try { (await import('./account/security.js' + __VER)).init(); } catch {}
  },

  account_order_show: async () => {
    const m = await import('./account/order_factura_a.js' + __VER);
    if (m && typeof m.initAccountOrderFacturaA === 'function') m.initAccountOrderFacturaA();
    const up = await import('./account/order_receipt_upload.js' + __VER);
    if (up && typeof up.initOrderReceiptUpload === 'function') up.initOrderReceiptUpload();
    const am = await import('./checkout/address_modal.js' + __VER);
    if (am && typeof am.init === 'function') am.init();
    const addr = await import('./account/order_address_modal_bridge.js' + __VER);
    if (addr && typeof addr.initOrderAddressModalBridge === 'function') addr.initOrderAddressModalBridge();
  },

  account_profile: async () => {
    const m = await import('./account/fiscal_profile.js');
    if (m && typeof m.initAccountProfile === 'function') m.initAccountProfile();
  },
};

// Ejecuta loader por data-page + fallbacks
(async () => {
  if (loaders[page]) {
    await loaders[page]();
  }

  // Fallback pÃºblico: CatÃ¡logo (por si el pageId cambia o queda desincronizado)
  // Importante para P0: Agregar al carrito desde cards debe funcionar siempre.
  if (
    document.getElementById('catalog-grid') ||
    document.querySelector('form[data-cart-add-card]') ||
    document.querySelector('[data-quick-add-trigger]')
  ) {
    try { (await import('./catalog/catalog.js' + __VER)).init?.(); } catch {}
  }

  if (
    document.getElementById('catalog-filters') &&
    document.querySelector('[data-filter-toggle]')
  ) {
    try { (await import('./catalog/filters.js' + __VER)).init?.(); } catch {}
  }

  if (document.querySelector('[data-catalog-sort]')) {
    try { (await import('./catalog/sort.js' + __VER)).init?.(); } catch {}
  }

  // Fallback OAuth embebido
  if (document.getElementById('google-btn-container')) {
    (await import('./auth/google.js' + __VER)).init();
  }

  // Fallback ImÃ¡genes Admin inicial
  if (document.getElementById('img-upload') || document.getElementById('img-gallery')) {
    await callModule('./admin/product_images/index.js', 'init');
  }

  // Fallback Admin: Pedido (show)
  // Si por algÃºn motivo no se seteÃ³ data-page="admin-orders-show", igual necesitamos bindear los listeners.
  if (
    location.pathname.indexOf('/admin/orders/show') !== -1 &&
    (document.getElementById('order-status') || document.getElementById('shipping-form'))
  ) {
    // Ã¢Å“â€¦ Si ya corriÃ³ el loader por data-page, callModule no re-importa.
    await callModule('./admin/orders/show/index.js', 'init');
  }

  // PÃºblico: Carrito (fallback por si data-page no está seteado)
  // Esto debe correr tambiÃ©n fuera de /admin.
  if (document.querySelector('[data-cart-form]')) {
    await callModule('./cart/cart.js', 'init');
  }

  // Fallbacks admin (si falta data-page o el contenido se inyecta luego)
  if (location.pathname.indexOf('/admin') !== -1) {
    await callModule('./admin/core/sidebar.js', 'initAdminSidebarV3');

    // Fallback Admin: Editor de producto
    // Si por algun motivo no corre el loader por data-page, necesitamos que
    // los tabs y el wizard sigan navegando para poder editar variantes/imagenes.
    if (document.getElementById('product-tabs-root')) {
      await callModule('./admin/product_form/index.js', 'init');
      await callModule('./admin/products/wizard.js', 'init');
      await callModule('./admin/seo/product_score.js', 'init');
      await callModule('./admin/seo/product_blocks.js', 'init');
    }

    if (location.pathname.endsWith('/admin/audit')) {
      await callModule('./admin/audit/filters.js' + __VER, 'init');
    }

    const ensureImagesModule = async () => {
      if (window.__adminImagesLoaded) return;
      if (document.getElementById('img-upload') || document.getElementById('img-gallery')) {
        const ok = await callModule('./admin/product_images/index.js', 'init');
        if (ok) window.__adminImagesLoaded = true;
      }
    };

    const ensureVariantsModule = async () => {
      if (window.__adminVariantsLoaded) return;
      if (document.querySelector('#variants-sale, [data-role="vs-config"], [data-role="vs-editor-fragment"], [data-role="vs-attr-quick-open"], [data-role="vs-attr-select"]')) {
        const ok = await callModule('./admin/product_variants/index.js', 'init');
        if (ok) window.__adminVariantsLoaded = true;
      }
    };

    const ensureAttributesModule = async () => {
      if (window.__adminAttributesLoaded) return;
      const body = document.body;
      if (body && body.getAttribute('data-page') === 'admin-attributes') {
        const ok = await callModule('./admin/attributes/index.js', 'init');
        if (ok) window.__adminAttributesLoaded = true;
      }
    };

    await ensureImagesModule();
    await ensureVariantsModule();
    await ensureAttributesModule();

    const moAdmin = new MutationObserver(() => {
      ensureImagesModule();
      ensureVariantsModule();
      ensureAttributesModule();
    });
    moAdmin.observe(document.body || document.documentElement, { childList: true, subtree: true });
  }

  // Fallback carrito para contenido inyectado sin pageId.
  const ensureCartModule = async () => {
    if (window.__cartModuleLoaded) return;
    if (document.querySelector('[data-cart-form]')) {
      const ok = await callModule('./cart/cart.js', 'init');
      if (ok) window.__cartModuleLoaded = true;
    }
  };
  await ensureCartModule();
  const moCart = new MutationObserver(() => { ensureCartModule(); });
  moCart.observe(document.body || document.documentElement, { childList: true, subtree: true });
})();

// === Checkout UX: al confirmar un pedido, reiniciamos el wizard visual ===
// Esto evita que, al volver a comprar, el usuario arranque en el Paso 3 por sessionStorage.
try {
  const qs = new URLSearchParams(location.search);
  if (location.pathname.endsWith('/cuenta/pedidos/ver') && qs.get('ok') === '1') {
    sessionStorage.removeItem('checkout_step');
    sessionStorage.removeItem('checkout_method');
    sessionStorage.removeItem('checkout_cart_sig');
  }
} catch (_) {}


// --- BFCache: al volver (pageshow persisted), re-ejecutar loader de la pÃ¡gina ---
// Esto es crÃ­tico para checkout (Mercado Pago Brick) porque el navegador puede restaurar la pÃ¡gina sin recargar scripts.
window.addEventListener('pageshow', (ev) => {
  try {
    if (!ev || !ev.persisted) return; // solo BFCache
    if (!page) return;
    if (!loaders[page]) return;
    // Re-run sin bloquear la UI
    loaders[page]();
  } catch (e) {
    console.warn('[app] pageshow loader error', e);
  }
});

