// admin/product_images/config.js
export const MAX_FILE_MB = 3;

// Subida
export const CONCURRENCY = 1;               // podés subir a 2–3 si tu backend lo banca
export const REQUEST_TIMEOUT_MS = 20000;    // por ítem
export const RETRY_COUNT = 1;               // reintentos en 5xx/network
export const RETRY_BACKOFF_MS = 800;        // base para backoff (ms)
export const ACCEPT_HEADER = 'application/json, text/html;q=0.9, text/plain;q=0.8';

// Galería
export const SOFT_REFRESH_ON_UNKNOWN = true;
export const FOCUS_AFTER_ACTION = true;

// Dedupe
export const DEDUPE_STRATEGY = 'hash';      // 'hash' | 'name-size'
export const MAX_QUEUE = null;              // o un número para limitar la cola

// Debug
export const DEBUG = false;
