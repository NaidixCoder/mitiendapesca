// admin/product_images/index.js
import { setupPreview, getPendingFiles, clearPending } from './preview.js';
import { requestUpload, requestAction } from './api.js';
import { getGallery, insertFigure, replaceGalleryFromHtml, softRefreshGallery, unmarkAllCovers, markAsCover, focusAfterRemove } from './gallery.js';
import { createFigure } from './templates.js';
import { uploadQueue } from './uploader.js';
import { getIsUploading, setIsUploading, resetStats, bumpOk, bumpErr, bumpInserted, getStats, setLastFocus, getLastFocus, clearLastFocus } from './state.js';
import { SOFT_REFRESH_ON_UNKNOWN, CONCURRENCY, REQUEST_TIMEOUT_MS, RETRY_COUNT, RETRY_BACKOFF_MS } from './config.js';
import { toast, ariaLive, btnBusy } from './feedback.js';
import { setupSort } from './sort.js';
import { initScopePicker } from './scope_picker.js';


// Dispara refresh de badges del wizard (puntos en tabs) sin recargar
const wizardRefresh = () => {
  try { document.dispatchEvent(new CustomEvent('tp:wizard:refresh')); } catch (_) {}
};

function getProductIdFromPage() {
  const v1 = document.querySelector('#product-form input[name="id"]')?.value;
  const v2 = document.querySelector('input[name="product_id"]')?.value;
  const raw = (v1 || v2 || '').toString();
  const id = Number.parseInt(raw, 10);
  return Number.isFinite(id) ? id : 0;
}

function isImagesTabActive() {
  try {
    const hidden = document.getElementById('product-active-tab');
    const v = String(hidden?.value || '').trim();
    if (v) return v === 'imagenes';
    const activeBtn = document.querySelector('#tabs-nav .tab-btn.is-active[data-tab]');
    return String(activeBtn?.getAttribute('data-tab') || '').trim() === 'imagenes';
  } catch (_) {
    return false;
  }
}

// Cuando se crean variantes en el Tab Variantes (AJAX), refrescamos los selects de Imágenes sin F5.
async function refreshVariantSelectsFromServer(productId = 0) {
  // Solo refrescar si el usuario está en Imágenes y no hay operación de variantes en curso.
  if (!isImagesTabActive()) return;
  if (document.body?.dataset?.tpVariantsBusy === '1') return;
  const filterSel = document.getElementById('img-filter-select');
  const bulkSel = document.querySelector('#img-bulk-assign-form select[name="variant_id"]');
  const anyOnPage = !!(filterSel || bulkSel || document.querySelector('#img-gallery select[name="variant_id"]'));
  if (!anyOnPage) return;

  const pid = (Number.parseInt(String(productId || 0), 10) || 0) || getProductIdFromPage();
  if (!pid) return;

  const baseRaw = document.querySelector('meta[name="app-base-url"]')?.getAttribute('content') || '';
  const base = String(baseRaw).replace(/\/+$/, '');
  const url = `${base}/admin/products/edit?id=${encodeURIComponent(String(pid))}&tab=imagenes&_t=${Date.now()}`;

  try {
    const r = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' }, cache: 'no-store' });
    const html = await r.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');

    // 1) Select de filtro (VER)
    const srcFilter = doc.getElementById('img-filter-select');
    if (srcFilter && filterSel) {
      const keep = filterSel.value;
      filterSel.innerHTML = srcFilter.innerHTML;
      const exists = Array.from(filterSel.options || []).some(o => String(o.value) === String(keep));
      filterSel.value = exists ? keep : 'all';
    }

    // 2) Select de asignación masiva
    const srcBulk = doc.querySelector('#img-bulk-assign-form select[name="variant_id"]');
    if (srcBulk && bulkSel) {
      const keep = bulkSel.value;
      bulkSel.innerHTML = srcBulk.innerHTML;
      const exists = Array.from(bulkSel.options || []).some(o => String(o.value) === String(keep));
      bulkSel.value = exists ? keep : (bulkSel.querySelector('option[value="0"]') ? '0' : (bulkSel.options?.[0]?.value || ''));
    }

    // 3) Select por tarjeta + template
    const srcCardSel = doc.querySelector('#img-gallery select[name="variant_id"]') || srcBulk;
    const optionsHtml = srcCardSel ? srcCardSel.innerHTML : '';
    if (optionsHtml) {
      document.querySelectorAll('#img-gallery select[name="variant_id"]').forEach((sel) => {
        const keep = sel.value;
        sel.innerHTML = optionsHtml;
        const exists = Array.from(sel.options || []).some(o => String(o.value) === String(keep));
        if (exists) sel.value = keep;
      });

      // Template usado para insertar nuevas cards post-upload
      try {
        const tpl = document.getElementById('tpl-gallery-item');
        const tplSel = tpl?.content?.querySelector?.('select[name="variant_id"]');
        if (tplSel) tplSel.innerHTML = optionsHtml;
      } catch (_) {}
    }
  } catch (_) {
    // silencioso
  }
}

// local helper to ensure the cover badge is visible/exists
function ensureBadge(fig) {
  if (!fig) return;
  let badge = fig.querySelector('.cover-badge');
  if (!badge) {
    badge = document.createElement('span');
    badge.className = 'cover-badge img-badge';
    badge.textContent = 'Portada · General';
    fig.prepend(badge);
  }
  badge.classList.remove('av3-hidden');
}

// Actualiza contadores del encabezado (Total/General/Variantes) sin recargar la página
async function refreshCountersFromServer() {
  const ids = ['img-count-total', 'img-count-general', 'img-count-variants-covered', 'img-count-variants-total'];
  try {
    const r = await fetch(location.href, { credentials: 'same-origin' });
    const html = await r.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    ids.forEach((id) => {
      const src = doc.getElementById(id);
      const dst = document.getElementById(id);
      if (src && dst) dst.textContent = (src.textContent || '').trim();
    });
  } catch (_) {}
}

function syncBulkNow() {
  try { window.__imgBulkSync?.(); } catch (_) {}
}



function initGalleryFilter() {
  const sel = document.getElementById('img-filter-select');
  if (!sel) return;
  if (sel.dataset.bound === '1') return;
  sel.dataset.bound = '1';
  sel.addEventListener('change', async () => {
    // UX premium: no recargar página. Actualizamos la URL y hacemos soft refresh de la galería.
    try {
      const url = new URL(window.location.href);
      const v = (sel.value || 'all').trim();
      if (v && v !== 'all') url.searchParams.set('img_filter_variant_id', v);
      else url.searchParams.delete('img_filter_variant_id');
      // siempre permanecer en el tab imágenes
      url.searchParams.set('tab', 'imagenes');
      url.hash = '';

      // Actualizar URL sin navegación
      try { history.replaceState(null, '', url.toString()); } catch (_) {}

      // Refrescar galería + contadores sin F5
      await softRefreshGallery();
      await refreshCountersFromServer();
      wizardRefresh();
syncBulkNow();
    } catch (_) {}
  });
}

function initBulkSelection() {
  const gallery = getGallery();
  if (!gallery) return;
  const bulkbar = document.getElementById('img-bulkbar');
  const countEl = document.getElementById('img-selected-count');
  const idsInputs = Array.from(document.querySelectorAll('[data-role="bulk-ids"], .js-bulk-ids'));
  const selectAll = document.getElementById('img-select-all');
  const btnClear = document.getElementById('img-bulk-clear');

  const getChecks = () => Array.from(getGallery()?.querySelectorAll('input.img-select[type="checkbox"]') || []);

  const idFromCheckbox = (chk) => {
    if (!chk) return 0;
    const v = String(chk.value || '').trim();
    const n = parseInt(v, 10);
    if (Number.isFinite(n) && n > 0) return n;
    // fallback: figure[data-id]
    const fig = chk.closest?.('figure');
    const d = fig?.dataset?.id ? parseInt(String(fig.dataset.id), 10) : 0;
    if (Number.isFinite(d) && d > 0) return d;
    // fallback: hidden image_id dentro del figure
    const hid = fig?.querySelector?.('input[name="image_id"]');
    const h = hid ? parseInt(String(hid.value || ''), 10) : 0;
    return (Number.isFinite(h) && h > 0) ? h : 0;
  };

  const getSelectedIds = () => getChecks()
    .filter(c => c && c.checked)
    .map(idFromCheckbox)
    .filter(n => Number.isFinite(n) && n > 0)
    .map(String);

  const sync = () => {
    const ids = getSelectedIds();
    if (countEl) countEl.textContent = String(ids.length);
    idsInputs.forEach(inp => { inp.value = ids.join(','); });
    // Estado UI (siempre visible): habilitar/deshabilitar acciones según selección
    const has = ids.length > 0;
    const assignForm = document.getElementById('img-bulk-assign-form');
    const deleteForm = document.getElementById('img-bulk-delete-form');
    const assignBtn = assignForm?.querySelector('button[type="submit"]');
    const assignSel = assignForm?.querySelector('select[name="variant_id"]');
    const deleteBtn = deleteForm?.querySelector('button[type="submit"]');
    if (assignBtn) assignBtn.disabled = !has;
    if (assignSel) assignSel.disabled = !has;
    if (deleteBtn) deleteBtn.disabled = !has;
    if (btnClear) btnClear.disabled = !has;
    if (bulkbar) bulkbar.setAttribute('aria-hidden', 'false');

    if (bulkbar) {
      bulkbar.classList.toggle('is-active', ids.length > 0);
      bulkbar.classList.toggle('is-idle', ids.length === 0);
    }
  
  // Shift+click: selección por rango (tipo Windows)
  if (!window.__imgBulkShiftBound) {
    window.__imgBulkShiftBound = true;
    window.__imgBulkLastIndex = null;
    document.addEventListener('click', (ev) => {
      const chk = ev.target && ev.target.closest ? ev.target.closest('input.img-select[type="checkbox"]') : null;
      if (!chk) return;
      if (!chk.closest('#img-gallery')) return;

      const checks = getChecks();
      const idx = checks.indexOf(chk);
      if (idx < 0) return;

      const last = (typeof window.__imgBulkLastIndex === 'number') ? window.__imgBulkLastIndex : null;
      if (ev.shiftKey && last !== null && last >= 0 && last < checks.length) {
        const a = Math.min(last, idx);
        const b = Math.max(last, idx);
        const state = !!chk.checked;
        for (let i = a; i <= b; i++) {
          checks[i].checked = state;
        }
      }

      window.__imgBulkLastIndex = idx;
      sync();
    });
  }

  if (selectAll) {
      const checks = getChecks();
      const all = checks.length > 0 && checks.every(c => c.checked);
      const none = checks.every(c => !c.checked);
      selectAll.indeterminate = !all && !none;
      selectAll.checked = all;
    }
  };

  // Delegado: sobrevive si el #img-gallery se reemplaza (soft refresh)
  if (!window.__imgBulkChangeBound) {
    window.__imgBulkChangeBound = true;
    document.addEventListener('change', (ev) => {
      const chk = ev.target && ev.target.closest ? ev.target.closest('input.img-select[type="checkbox"]') : null;
      if (!chk) return;
      if (!chk.closest('#img-gallery')) return;
      sync();
    });
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      const checks = getChecks();
      checks.forEach(c => { c.checked = !!selectAll.checked; });
      sync();
    });
  }

  if (btnClear) {
    btnClear.addEventListener('click', () => {
      const checks = getChecks();
      checks.forEach(c => { c.checked = false; });
      if (selectAll) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
      }
      sync();
    });
  }

  // Exponer sync para re-evaluar luego de inserts/removes
  try { window.__imgBulkSync = sync; } catch (_) {}

  sync();
}

export function init() {
  const upForm = document.getElementById('img-upload');
  const file   = document.getElementById('img-file');
  const btn    = document.getElementById('btn-upload');
  const msgEl  = document.getElementById('img-msg');

  // Listeners globales: variantes creadas/actualizadas => refrescar selects de Imágenes sin recargar.
  // (El módulo de Variantes dispara tp:variants:changed luego de un POST exitoso.)
  try {
    if (document.body && document.body.dataset.tpImgVariantListen !== '1') {
      document.body.dataset.tpImgVariantListen = '1';
      document.addEventListener('tp:variants:changed', (e) => {
        const pid = e?.detail?.productId;
        refreshVariantSelectsFromServer(pid);
      });
      document.addEventListener('product:wizard:tab-changed', (e) => {
        const to = e?.detail?.to;
        if (String(to) === 'imagenes') refreshVariantSelectsFromServer();
      });
    }
  } catch (_) {}


const uploadActions = document.getElementById('upload-actions');
const uploadIdle    = document.getElementById('upload-idle');
const uploadCountEl = document.getElementById('upload-count');
const btnCancel     = document.getElementById('btn-upload-cancel');

const updateUploadUi = () => {
  try {
    const n = getPendingFiles(file)?.length || 0;
    if (uploadCountEl) uploadCountEl.textContent = n > 0 ? `(${n})` : '';
    if (uploadActions) uploadActions.classList.toggle('av3-hidden', n === 0);
    if (uploadIdle) uploadIdle.classList.toggle('av3-hidden', n !== 0);
    if (btn) btn.disabled = (n === 0) || getIsUploading();
    if (btnCancel) btnCancel.disabled = getIsUploading();
  } catch (_) {}
};
  const gallery = getGallery();

  // Asignación rápida: AJAX (sin recargar página). Listener delegado (sobrevive si se reemplaza la galería)
  if (!window.__imgAssignBound) {
    window.__imgAssignBound = true;
    document.addEventListener('change', async (ev) => {
      const sel = ev.target && ev.target.closest ? ev.target.closest('.js-assign-select') : null;
      if (!sel) return;
      if (!sel.closest('#img-gallery')) return;

      const form = sel.closest('form');
      if (!form) return;

      // Evitar submits normales (sin F5)
      ev.preventDefault();
      ev.stopPropagation();

      try {
        const fd = new FormData(form);
        // Asegurar header JSON vía requestAction
        const res = await requestAction(form.action, fd);
        if (!res.ok) throw new Error('Asignación rechazada');

        // Refrescar solo galería + contadores (sin navegar)
        await softRefreshGallery();
        await refreshCountersFromServer();
        wizardRefresh();
syncBulkNow();
        try { toast.success(res.json?.message || 'Imagen asignada'); } catch (_) {}
      } catch (e) {
        try { toast.error('No se pudo asignar la imagen'); } catch (_) {}
      }
    }, { capture: true });
  }

  // Selector premium de alcance (D). No genera HTML, solo filtra/toggle.
  try { initScopePicker(); } catch (_) {}

  // Filtro (Todas / General / Variante)
  initGalleryFilter();

  // Checkboxes + acciones masivas
  initBulkSelection();

  // Si el HTML del server reemplaza #img-gallery (soft refresh), re-inicializar bulk/sort.
  if (!window.__imgGalleryReplacedBound) {
    window.__imgGalleryReplacedBound = true;
    document.addEventListener('img-gallery-replaced', () => {
      try { initBulkSelection(); } catch (_) {}
      try {
        const g2 = getGallery();
        if (g2 && g2.dataset.disableSort !== '1') setupSort({ gallery: g2, upForm });
      } catch (_) {}
    });
  }

  // Fallback (solo si el select existe; por ejemplo en <noscript> o variantes viejas)
  const scopeSel = document.getElementById('img-variant-scope');
  if (scopeSel && scopeSel.dataset.bound !== '1') {
    scopeSel.dataset.bound = '1';
    scopeSel.addEventListener('change', () => {
      try {
        const url = new URL(window.location.href);
        const v = (scopeSel.value || '').trim();
        if (v) url.searchParams.set('img_variant_id', v);
        else url.searchParams.delete('img_variant_id');
        url.searchParams.set('tab', 'imagenes');
        url.hash = '';
        window.location.assign(url.toString());
      } catch (_) {}
    });
  }

  // habilitar drag & drop sort (solo cuando la vista lo permite)
  try {
    if (gallery && gallery.dataset.disableSort !== '1') {
      setupSort({ gallery, upForm });
    }
  } catch (e) { /* noop */ }
  const dropZone = upForm?.querySelector('label[for="img-file"]') || upForm;
  const previewGrid = document.getElementById('img-preview-grid');

  if (!upForm || !gallery) return;
  if (upForm.dataset.modInit === '1') return;
  upForm.dataset.modInit = '1';

  const setMsg = (t, ok = true) => {
    if (msgEl) {
      msgEl.textContent = t || '';
      msgEl.style.color = ok ? 'var(--muted)' : 'var(--danger)';
    }
    ariaLive.say(t || '');
  };

  setupPreview({ fileInput: file, dropZone, previewGrid, onAdd: () => setMsg('') });


// UI de subida: mostrar botones solo cuando hay archivos en cola
updateUploadUi();
if (!window.__imgQueueChangedBound) {
  window.__imgQueueChangedBound = true;
  document.addEventListener('img-queue-changed', () => updateUploadUi());
}
if (btnCancel && btnCancel.dataset.bound !== '1') {
  btnCancel.dataset.bound = '1';
  btnCancel.addEventListener('click', () => {
    clearPending(previewGrid, file);
    updateUploadUi();
    setMsg('');
    updateUploadUi();
  });
}

  upForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (getIsUploading()) return;
    setIsUploading(true);
    updateUploadUi();

    const pid = +(upForm.querySelector('input[name="product_id"]')?.value || 0);
    let variantId = (upForm.querySelector('input[name="variant_id"]')?.value || '').trim();
    if (variantId === '0') variantId = '';
    if (pid <= 0) { setMsg('Guardá el producto antes de subir imágenes.', false); setIsUploading(false); updateUploadUi(); return; }

    const files = getPendingFiles(file);
    if (files.length === 0) { setMsg('Elegí al menos una imagen.', false); setIsUploading(false); updateUploadUi(); return; }

    const csrf = upForm.querySelector('input[name="_csrf"]')?.value || '';
    const uploadUrl = upForm.dataset.uploadUrl || upForm.action || '/admin/products/image-upload';

    btnBusy(btn, true, 'Subiendo…');
    setMsg('Subiendo…', true);
    resetStats();

    await uploadQueue({
      files,
      concurrency: CONCURRENCY,
      timeoutMs: REQUEST_TIMEOUT_MS,
      retryCount: RETRY_COUNT,
      retryBackoffMs: RETRY_BACKOFF_MS,
      doRequest: (f, signal) => {
        const fields = { _csrf: csrf, product_id: String(pid) };
        if (variantId) fields.variant_id = variantId;
        return requestUpload(uploadUrl, fields, f, { signal });
      },
      onItemDone: (f, idx, res, err) => {
        if (err) { bumpErr(); return; }
        if (res?.ok) bumpOk();
        let inserted = false;
        if (res?.item) {
          const fig = createFigure({ item: res.item, csrf, pid, variantId });
          if (fig) { insertFigure(fig); inserted = true; }
        } else if (res?.figureEl) {
          insertFigure(res.figureEl); inserted = true;
        } else if (res?.kind === 'page' && res?.html) {
          if (replaceGalleryFromHtml(res.html)) inserted = true;
        }
        if (inserted) bumpInserted();
      },
      onBatchDone: () => {}
    });

    clearPending(previewGrid, file);

    const st = getStats();
    if (st.inserted === 0 && st.ok > 0 && SOFT_REFRESH_ON_UNKNOWN) {
      await softRefreshGallery();
    }

    // Recalcular contadores (Total/General/Variantes) sin F5
    if (st.ok > 0) { await refreshCountersFromServer(); wizardRefresh();
}
    syncBulkNow();

    if (st.err === 0 && (st.inserted > 0 || st.ok > 0)) { setMsg('Imágenes subidas.'); toast.success('Imágenes subidas.'); }
    else if (st.inserted > 0 && st.err > 0) { setMsg(`Subida completa con ${st.err} error(es).`, false); toast.error(`Subida completa con ${st.err} error(es).`); }
    else { setMsg('No se pudieron subir las imágenes.', false); toast.error('No se pudieron subir las imágenes.'); }

    btnBusy(btn, false);
    setIsUploading(false);
    updateUploadUi();
  });

  // Delegated actions (delete / cover)
  if (!window.__imgActionsBound) {
    window.__imgActionsBound = true;
    document.addEventListener('submit', async (e) => {
      const form = e.target?.closest?.('.img-action')
        || e.target?.closest?.('#img-bulk-assign-form')
        || e.target?.closest?.('#img-bulk-delete-form');
      if (!form) return;
      e.preventDefault();

      // Guardar foco actual para restaurar luego
      setLastFocus(document.activeElement);

      const isDelete = (form.action || '').includes('/image-delete');
      const isCover  = (form.action || '').includes('/image-cover');
      const isBulkDelete = (form.action || '').includes('/image-bulk-delete');
      const isBulkAssign = (form.action || '').includes('/image-bulk-assign');
      if (isDelete && !confirm('¿Eliminar imagen?')) return;
      if (isBulkDelete && !confirm('¿Eliminar las imágenes seleccionadas?')) return;

      const btnEl = form.querySelector('button[type="submit"]');
      btnBusy(btnEl, true, isDelete ? 'Eliminando…' : 'Aplicando…');

      try {
                // Para bulk: no confiar solo en hidden inputs (pueden quedar desincronizados tras soft refresh).
        // Recolectamos IDs directamente desde los checkboxes seleccionados.
        const fd = new FormData(form);
        if (isBulkAssign || isBulkDelete) {
          const checked = Array.from(document.querySelectorAll('#img-gallery input.img-select[type="checkbox"]:checked'));
          const ids = checked.map(chk => {
            const v = String(chk.value || '').trim();
            const n = parseInt(v, 10);
            if (Number.isFinite(n) && n > 0) return n;
            const fig = chk.closest('figure');
            const d = fig?.dataset?.id ? parseInt(fig.dataset.id, 10) : 0;
            return (Number.isFinite(d) && d > 0) ? d : 0;
          }).filter(n => Number.isFinite(n) && n > 0);
          if (ids.length === 0) {
            toast.info('Seleccioná al menos una imagen');
            ariaLive.say('Seleccioná al menos una imagen', 'assertive');
            return;
          }
          fd.set('image_ids', ids.join(','));
        }

        const res = await requestAction(form.action, fd);

        if (!res.ok) throw new Error('Acción rechazada');

        const fig = form.closest('figure');
        if (isBulkAssign || isBulkDelete) {
          await softRefreshGallery();
          await refreshCountersFromServer();
          wizardRefresh();
syncBulkNow();
          if (isBulkDelete) {
            toast.success('Imágenes eliminadas');
            ariaLive.say('Imágenes eliminadas');
          } else {
            toast.success('Imágenes asignadas');
            ariaLive.say('Imágenes asignadas');
          }
          return;
        }

        if (isDelete) {
          fig?.remove();
          focusAfterRemove(fig);
          // Si el server promovió una nueva portada, marcarla
          const g = getGallery();
          const nid = res && (res.new_cover_id || (res.data && res.data.new_cover_id));
          if (nid) {
            const tgt = g?.querySelector(`figure[data-id="${nid}"]`);
            if (tgt) { unmarkAllCovers(g); markAsCover(tgt); ensureBadge(tgt); }
          } else {
            // Fallback: si server no envía new_cover_id, si no hay portada actual,
            // marcamos la primera del grid (si existe)
            const curCover = g?.querySelector('figure.is-cover');
            if (!curCover) {
              const first = g?.querySelector('figure');
              if (first) { unmarkAllCovers(g); markAsCover(first); ensureBadge(first); }
            }
          }
          // actualizar contadores y estado del bulkbar
          await refreshCountersFromServer();
          wizardRefresh();
          syncBulkNow();
          toast.success('Imagen eliminada');
          ariaLive.say('Imagen eliminada');
        } else if (isCover) {
          const g = getGallery();
          unmarkAllCovers(g);
          markAsCover(fig);
          ensureBadge(fig);
          // Restaurar foco al botón "Portada"
          const b2 = fig?.querySelector('.js-cover');
          b2?.focus?.();
          toast.success('Imagen marcada como portada');
          ariaLive.say('Imagen marcada como portada');
        }
      } catch (err) {
        toast.error('Error al ejecutar la acción');
        ariaLive.say('Error al ejecutar la acción', 'assertive');
      } finally {
        btnBusy(btnEl, false);
        clearLastFocus();
      }
    }, { capture: true });
  }
}
