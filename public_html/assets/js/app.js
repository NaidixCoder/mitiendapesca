// App entrypoint (module)
import { init as headerInit } from './core/header.js';
import { init as flashInit } from './core/flash.js';
import { init as toTopInit } from './core/toTop.js';

headerInit();
flashInit();
toTopInit();

const page = document.body?.dataset?.page || '';

const loaders = {
  'admin-product-form': async () => {
    const m1 = await import('./admin/product_form.js'); m1.init();
    const m2 = await import('./admin/product_images.js'); m2.init();
  },
  'product': async () => {
    const g = await import('./product/gallery.js'); g.init();
    const q = await import('./product/qty.js'); q.init();
  },
  'catalog': async () => {
    const c = await import('./catalog/catalog.js'); c.init();
  },
  'search': async () => {
    const c = await import('./catalog/catalog.js'); c.init();
  },
  'auth-google': async () => {
    const a = await import('./auth/google.js'); a.init();
  },
};

if (loaders[page]) loaders[page]();
