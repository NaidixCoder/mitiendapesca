// admin/product_images/state.js
import { MAX_FILE_MB, DEDUPE_STRATEGY } from './config.js';

let pending = []; // [{ id, file, name, size, type, hash?, dupOfHash?:string }]
let isUploading = false;
let stats = { ok: 0, err: 0, inserted: 0 };
let lastActionFocus = null;

export function validateFile(f) {
  if (!f) return 'Elegí un archivo.';
  if (f.type && !/^image\//.test(f.type)) return 'El archivo no es una imagen.';
  if (f.size > MAX_FILE_MB * 1024 * 1024) return `El archivo supera los ${MAX_FILE_MB}MB.`;
  return null;
}

export function addFiles(files) {
  const added = [];
  for (const f of files || []) {
    const err = validateFile(f);
    if (err) continue;
    if (DEDUPE_STRATEGY === 'name-size') {
      if (pending.some(p => p.name === f.name && p.size === f.size)) continue;
    }
    added.push({ id: Math.random().toString(36).slice(2,9), file: f, name: f.name, size: f.size, type: f.type, hash: null });
  }
  if (added.length) pending = pending.concat(added);
  return added;
}

export function setHash(id, hash) {
  const p = pending.find(x => x.id === id);
  if (p) p.hash = hash;
  // Dedupe por hash
  if (DEDUPE_STRATEGY === 'hash' && hash) {
    const exists = pending.find(x => x.id !== id && x.hash && x.hash === hash);
    if (exists) {
      const item = pending.find(x => x.id === id);
      if (item) item.dupOfHash = hash;
    }
  }
}

export function getPending() { return pending.slice(); }
export function removeById(id) { pending = pending.filter(p => p.id !== id); }
export function clear() { pending = []; }

export function getIsUploading() { return isUploading; }
export function setIsUploading(v) { isUploading = !!v; }

export function resetStats() { stats = { ok: 0, err: 0, inserted: 0 }; }
export function bumpOk(n=1){ stats.ok += n; }
export function bumpErr(n=1){ stats.err += n; }
export function bumpInserted(n=1){ stats.inserted += n; }
export function getStats(){ return { ...stats }; }

export function setLastFocus(el){ lastActionFocus = el || null; }
export function getLastFocus(){ return lastActionFocus; }
export function clearLastFocus(){ lastActionFocus = null; }
