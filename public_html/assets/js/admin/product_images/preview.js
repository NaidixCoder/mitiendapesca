// assets/js/admin/product_images/preview.js
// Previsualización y cola sin blob: usa Data URLs (readAsDataURL) para compatibilidad CSP.
// Exports: setupPreview, renderQueue, clearPreview, stateQueue, stateRemoveById
// + aliases: getPendingFiles, addFilesToQueue, removeFromQueueById, clearPending, initPreview

let queue = []; // [{ pid, file, url, name }]
let grid  = null;
let gridOverride = null; // grid provisto por la vista (p.ej. #img-preview-grid)

function ensureGrid(){
  // Resolver el grid de la cola (preferir el provisto por la vista)
  let candidate = null;

  if (gridOverride && document.body.contains(gridOverride)) {
    candidate = gridOverride;
  } else if (grid && document.body.contains(grid)) {
    candidate = grid;
  } else {
    candidate = document.getElementById('img-queue') || document.querySelector('[data-queue-grid], #img-queue');
    if (!candidate) {
      const form = document.getElementById('img-upload');
      const wrap = document.createElement('div');
      wrap.id = 'img-queue';
      wrap.className = 'av3-mt-3 av3-grid av3-grid-cols-2 av3-md-grid-cols-3 av3-gap-3';
      form?.appendChild(wrap);
      candidate = wrap;
    }
  }

  grid = candidate;

  // Bind remove handler (incluye grid provisto por la vista)
  if (grid && !grid.dataset.removeBound) {
    grid.addEventListener('click', onRemoveClick); // no passive: queremos poder cancelar efectos si hiciera falta
    grid.dataset.removeBound = '1';
  }

  return grid;
}

function onRemoveClick(e){
  const t = (e.target && e.target.nodeType === 1) ? e.target : (e.target && e.target.parentElement);
  if (!t || !t.closest) return;
  const btn = t.closest('button[data-remove]');
  if (!btn) return;
  const pid = String(btn.dataset.remove || '');
  if (!pid) return;

  stateRemoveById(pid);

  const card = btn.closest('[data-id]');
  if (card) card.remove();

  dispatchQueueChanged();

  if (queue.length === 0) clearPreview();
}

function makePid(){ return `${Date.now()}_${Math.random().toString(36).slice(2,8)}`; }

function cardTemplate({ pid, name }){
  const fig = document.createElement('figure');
  fig.className = 'av3-relative av3-border av3-rounded av3-pad-2 av3-bg-white-60';
  fig.dataset.id = pid;
  fig.innerHTML = `
    <button type="button"
            class="av3-absolute top-1 right-1 z-10 av3-rounded av3-px-1 av3-type-caption av3-opacity-80 av3-hover-opacity-100 av3-bg-white-80"
            title="Quitar de la cola" aria-label="Quitar"
            data-remove="${pid}">×</button>
    <img class="av3-w-full av3-aspect-square av3-object-cover av3-rounded" alt="">
    <div class="av3-mt-1 av3-text--11px av3-opacity-70 av3-truncate">${name || 'Listo para subir'}</div>
  `;
  return fig;
}

// Lee un File a DataURL (promesa)
function fileToDataURL(file){
  return new Promise((resolve, reject) => {
    const fr = new FileReader();
    fr.onload = () => resolve(fr.result);
    fr.onerror = () => reject(fr.error || new Error('read error'));
    fr.readAsDataURL(file);
  });
}

function toFiles(list){
  if (!list) return [];
  if (list instanceof FileList) return Array.from(list);
  if (Array.isArray(list)) return list.filter(f => f && typeof f.size === 'number');
  if (list.items && typeof list.items.length === 'number') {
    const out = [];
    for (let i=0;i<list.items.length;i++){
      const it = list.items[i];
      const f = typeof it.getAsFile === 'function' ? it.getAsFile() : null;
      if (f) out.push(f);
    }
    return out;
  }
  return [];
}

// =============== API pública ===============

export async function renderQueue(list){
  const files = toFiles(list);
  if (!files.length) return;

  const g = ensureGrid();
  for (const file of files){
    if (!/^image\//i.test(file.type)) continue;
    if (file.size > 3 * 1024 * 1024) continue;

    const pid = makePid();
    const fig = cardTemplate({ pid, name: file.name });
    g.appendChild(fig);

    try {
      const dataUrl = await fileToDataURL(file);
      queue.push({ pid, file, url: dataUrl, name: file.name || '' });
      const img = fig.querySelector('img');
      if (img) img.src = dataUrl;
    } catch {}
  }
  dispatchQueueChanged();
}

export function clearPreview(){
  const g = ensureGrid();
  queue = [];
  dispatchQueueChanged();
  if (g) {
    g.innerHTML = '';
    // Si usamos grid provisto por la vista, lo ocultamos cuando queda vacío
    if (gridOverride === g) g.classList.add('av3-hidden');
  }
}

export function stateQueue(){ return queue.map(it => it.file); }
export function stateRemoveById(pid){
  const before = queue.length;
  queue = queue.filter(it => String(it.pid) != String(pid));
  return before !== queue.length;
}

function dispatchQueueChanged(){
  try {
    document.dispatchEvent(new CustomEvent('img-queue-changed', { detail: { count: queue.length } }));
  } catch (_) {}
}

// =============== setupPreview (idempotente) ===============
export function setupPreview(opts = {}) {
  // Soportar 2 APIs:
  // 1) Nueva (usada por admin/product_images/index.js): { fileInput, dropZone, previewGrid, onAdd }
  // 2) Legacy: { formId, inputId, dropSelector }

  const isNewApi = !!(opts.fileInput || opts.dropZone || opts.previewGrid || opts.onAdd);

  let form, input, dropArea;

  if (isNewApi) {
    input = opts.fileInput || document.getElementById('img-file');
    form = opts.form || input?.closest?.('form') || document.getElementById('img-upload');
    dropArea = opts.dropZone || form;

    if (opts.previewGrid) {
      gridOverride = opts.previewGrid;
      // aseguramos clases básicas por si viene vacío
      if (!gridOverride.classList.contains('av3-grid')) {
        gridOverride.className = 'av3-mt-3 av3-grid av3-grid-cols-2 av3-md-grid-cols-3 av3-gap-3';
      }
    }
  } else {
    const { formId = 'img-upload', inputId = 'img-file', dropSelector = '#img-upload' } = opts;
    form = document.getElementById(formId);
    input = document.getElementById(inputId);
    dropArea = document.querySelector(dropSelector) || form;
  }

  if (!form || !dropArea) return;

  // Marcar una sola vez
  if (!form.dataset.previewBound) {
    const afterAdd = async (files) => {
      await renderQueue(files);
      // Mostrar grid provisto por vista
      if (gridOverride) gridOverride.classList.remove('av3-hidden');
      try { opts.onAdd?.(); } catch {}
    };

    input?.addEventListener('change', async () => {
      if (!input.files || !input.files.length) return;
      await afterAdd(input.files);
      try { input.value = ''; } catch {}
    });

    const on = () => dropArea.classList.add('ring-2','ring-brand','av3-bg-brand-5');
    const off = () => dropArea.classList.remove('ring-2','ring-brand','av3-bg-brand-5');

    dropArea.addEventListener('dragenter', (e) => { e.preventDefault(); on(); });
    dropArea.addEventListener('dragover',  (e) => { e.preventDefault(); on(); });
    dropArea.addEventListener('dragleave', (e) => { e.preventDefault(); off(); });
    dropArea.addEventListener('drop', async (e) => {
      e.preventDefault(); off();
      const dt = e.dataTransfer;
      if (!dt) return;
      const files = (dt.files && dt.files.length) ? dt.files : (dt.items || []);
      await afterAdd(files);
    });

    form.dataset.previewBound = '1';
  }

  ensureGrid();
}

// Aliases legacy
export const getPendingFiles     = stateQueue;
export const addFilesToQueue     = renderQueue;
export const removeFromQueueById = stateRemoveById;
export const clearPending        = clearPreview;
export const initPreview         = setupPreview;
