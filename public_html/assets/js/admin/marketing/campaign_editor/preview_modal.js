import { setEmailDialogOpen } from './dialog_scroll_lock.js';

export function setupPreviewModal(options) {
  const {
    form,
    previewUrl,
    iframe,
    previewWrap,
    getMode,
    syncFromHtml,
    syncFromVisual,
    updateInboxAndChecklist,
  } = options || {};

  const modal = document.getElementById('email-preview-modal');
  const openPreviewBtn = document.getElementById('email-preview-open');
  const refreshPreviewBtn = document.getElementById('email-preview-refresh');
  const previewStatus = document.getElementById('email-preview-status');

  let lastReqKey = null;
  let inflight = null;

  function setPreviewStatus(msg) {
    if (!previewStatus) return;
    previewStatus.textContent = String(msg || '-');
  }

  const sizeBtns = Array.from(document.querySelectorAll('[data-preview-size]'));
  function setPreviewSize(next) {
    const mode = next === 'mobile' ? 'mobile' : 'desktop';
    sizeBtns.forEach((b) => {
      const on = b.getAttribute('data-preview-size') === mode;
      b.classList.toggle('is-active', on);
    });
    if (previewWrap) previewWrap.style.maxWidth = mode === 'mobile' ? '420px' : '100%';
  }

  sizeBtns.forEach((b) => {
    b.addEventListener('click', (e) => {
      e.preventDefault();
      setPreviewSize(b.getAttribute('data-preview-size'));
    });
  });
  setPreviewSize('desktop');

  async function runPreview(force) {
    if (!previewUrl || !iframe || !form) return;

    try {
      setPreviewStatus('Generando vista previa...');
      if (refreshPreviewBtn) refreshPreviewBtn.disabled = true;

      if (getMode && getMode() === 'html') syncFromHtml && syncFromHtml();
      else syncFromVisual && syncFromVisual();

      if (updateInboxAndChecklist) updateInboxAndChecklist();

      const fd = new FormData(form);
      fd.delete('__preview_url');

      const key = [
        fd.get('subject') || '',
        fd.get('preheader') || '',
        fd.get('cta_label') || '',
        fd.get('cta_url') || '',
        fd.get('body_html') || '',
      ].join('||');

      if (!force && key === lastReqKey) {
        setPreviewStatus('Sin cambios.');
        return;
      }
      lastReqKey = key;

      if (inflight) inflight.abort();
      inflight = new AbortController();

      const res = await fetch(previewUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        signal: inflight.signal,
        headers: { Accept: 'text/html' },
      });

      const html = await res.text();
      iframe.srcdoc = html;
      setPreviewStatus('OK');
    } catch (e) {
      if (e && e.name === 'AbortError') return;
      console.warn('[email preview]', e);
      setPreviewStatus('Error al generar la vista previa.');
    } finally {
      if (refreshPreviewBtn) refreshPreviewBtn.disabled = false;
    }
  }

  function openModal() {
    if (!modal) return;
    modal.classList.remove('is-hidden');
    modal.setAttribute('aria-hidden', 'false');
    setEmailDialogOpen(modal, true);
    if (updateInboxAndChecklist) updateInboxAndChecklist();
    runPreview(false);
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('is-hidden');
    modal.setAttribute('aria-hidden', 'true');
    setEmailDialogOpen(modal, false);
  }

  openPreviewBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    openModal();
  });
  refreshPreviewBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    runPreview(true);
  });

  if (modal) {
    modal.querySelectorAll('[data-modal-close]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal();
      });
    });

    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !modal.classList.contains('is-hidden')) closeModal();
    });
  }

  return {
    open: openModal,
    close: closeModal,
    refresh: (force = false) => runPreview(!!force),
  };
}

