// admin/product_images/logging.js
import { DEBUG } from './config.js';
export const log = (...a) => { if (DEBUG) console.debug('[product_images]', ...a); };
export const warn = (...a) => { if (DEBUG) console.warn('[product_images]', ...a); };
export const err = (...a) => console.error('[product_images]', ...a);
