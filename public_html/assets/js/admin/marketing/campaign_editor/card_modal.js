import { buildCardHtml, buildGrid2Html, computeDiscountPct } from './card_builder.js';
import { setupProductPicker } from './product_picker.js';
import { setEmailDialogOpen } from './dialog_scroll_lock.js';

export function setupCardBuilder(options) {
  const {
    form,
    editor,
    uploadUrl,
    safeUrl,
    insertHtml,
    syncFromVisual,
    updateInboxAndChecklist,
  } = options || {};

  const cbModal = document.getElementById('email-card-builder-modal');
  const cbApply = document.getElementById('cb-apply');

  const cbTabs = Array.from(document.querySelectorAll('[data-card-builder-tab]'));
  const cbPanels = Array.from(document.querySelectorAll('[data-card-builder-panel]'));

  const cbCardImg = document.getElementById('cb-card-img');
  const cbCardUpload = document.getElementById('cb-card-upload');
  const cbCardUploadMsg = document.getElementById('cb-card-upload-msg');
  const cbCardTitle = document.getElementById('cb-card-title');
  const cbCardDesc = document.getElementById('cb-card-desc');
  const cbCardPrice = document.getElementById('cb-card-price');
  const cbCardListPrice = document.getElementById('cb-card-list-price');
  const cbCardDiscount = document.getElementById('cb-card-discount');
  const cbCardSize = document.getElementById('cb-card-size');
  const cbGrid2Size = document.getElementById('cb-grid2-size');
  const cbCardCtaText = document.getElementById('cb-card-cta-text');
  const cbCardCtaUrl = document.getElementById('cb-card-cta-url');

  const g1 = {
    img: document.getElementById('cb-g1-img'),
    upload: document.getElementById('cb-g1-upload'),
    msg: document.getElementById('cb-g1-upload-msg'),
    title: document.getElementById('cb-g1-title'),
    desc: document.getElementById('cb-g1-desc'),
    price: document.getElementById('cb-g1-price'),
    listPrice: document.getElementById('cb-g1-list-price'),
    discount: document.getElementById('cb-g1-discount'),
    ctaText: document.getElementById('cb-g1-cta-text'),
    ctaUrl: document.getElementById('cb-g1-cta-url'),
  };

  const g2 = {
    img: document.getElementById('cb-g2-img'),
    upload: document.getElementById('cb-g2-upload'),
    msg: document.getElementById('cb-g2-upload-msg'),
    title: document.getElementById('cb-g2-title'),
    desc: document.getElementById('cb-g2-desc'),
    price: document.getElementById('cb-g2-price'),
    listPrice: document.getElementById('cb-g2-list-price'),
    discount: document.getElementById('cb-g2-discount'),
    ctaText: document.getElementById('cb-g2-cta-text'),
    ctaUrl: document.getElementById('cb-g2-cta-url'),
  };

  let cbMode = 'card';
  let cbReplaceEl = null;

  function updateDiscountInputs() {
    if (cbCardDiscount) {
      const pct = computeDiscountPct(cbCardListPrice?.value, cbCardPrice?.value);
      cbCardDiscount.value = pct ? `${pct}%` : '-';
    }
    if (g1?.discount) {
      const pct1 = computeDiscountPct(g1.listPrice?.value, g1.price?.value);
      g1.discount.value = pct1 ? `${pct1}%` : '-';
    }
    if (g2?.discount) {
      const pct2 = computeDiscountPct(g2.listPrice?.value, g2.price?.value);
      g2.discount.value = pct2 ? `${pct2}%` : '-';
    }
  }

  function showCbTab(tab) {
    cbMode = tab === 'grid2' ? 'grid2' : 'card';
    cbTabs.forEach((b) => {
      const active = b.getAttribute('data-card-builder-tab') === cbMode;
      b.classList.toggle('is-active', active);
    });
    cbPanels.forEach((p) => {
      const show = p.getAttribute('data-card-builder-panel') === cbMode;
      p.classList.toggle('is-hidden', !show);
    });
    if (cbMode === 'card') cbCardTitle?.focus();
    else g1.title?.focus();
  }

  function openCbModal(kind, replaceEl) {
    if (!cbModal) return;
    cbReplaceEl = replaceEl || null;
    cbModal.classList.remove('is-hidden');
    cbModal.setAttribute('aria-hidden', 'false');
    setEmailDialogOpen(cbModal, true);
    if (cbCardCtaText) cbCardCtaText.value = cbCardCtaText.value || 'Ver';
    if (g1.ctaText) g1.ctaText.value = g1.ctaText.value || 'Ver';
    if (g2.ctaText) g2.ctaText.value = g2.ctaText.value || 'Ver';
    showCbTab(kind === 'grid2' ? 'grid2' : 'card');
  }

  function closeCbModal() {
    if (!cbModal) return;
    cbModal.classList.add('is-hidden');
    cbModal.setAttribute('aria-hidden', 'true');
    setEmailDialogOpen(cbModal, false);
    cbReplaceEl = null;
  }

  function findSelectedEmailBlock() {
    try {
      const sel = window.getSelection();
      const node = sel && sel.anchorNode ? sel.anchorNode : null;
      if (!node) return null;
      const el = node.nodeType === 1 ? node : node.parentElement;
      if (!el) return null;

      const marked = el.closest('[data-email-block]');
      if (marked && editor && editor.contains(marked)) return marked;

      const table = el.closest('table[role="presentation"]');
      if (table && editor && editor.contains(table)) {
        const html = (table.outerHTML || '').toLowerCase();
        if (html.includes('border-radius:16px') && html.includes('overflow:hidden') && html.includes('<img')) return table;
        if (html.includes('padding-right:8px') && html.includes('padding-left:8px') && html.includes('border-radius:16px')) return table;
      }
      return null;
    } catch {
      return null;
    }
  }

  function parseCardFromBlock(blockEl) {
    const wrap = document.createElement('div');
    wrap.innerHTML = blockEl ? blockEl.outerHTML || '' : '';
    const img = wrap.querySelector('img')?.getAttribute('src') || '';
    const h3 = wrap.querySelector('h3')?.textContent || '';
    const pDesc = wrap.querySelector('p[style*="color"]')?.textContent || wrap.querySelectorAll('p')[0]?.textContent || '';
    const pPrice = wrap.querySelector('p[style*="font-weight:700"]')?.textContent || '';
    const a = wrap.querySelector('a[href]');
    const ctaText = a ? a.textContent || '' : '';
    const ctaUrl = a ? a.getAttribute('href') || '' : '';
    const listPrice = wrap.querySelector('[data-email-price="list"]')?.textContent || '';
    const size = wrap.querySelector('[data-email-card-size]')?.getAttribute('data-email-card-size') || 'normal';
    return { img, title: h3, desc: pDesc, price: pPrice, listPrice, ctaText, ctaUrl, size };
  }

  function parseGrid2FromBlock(blockEl) {
    const wrap = document.createElement('div');
    wrap.innerHTML = blockEl ? blockEl.outerHTML || '' : '';
    const size = wrap.querySelector('[data-email-card-size]')?.getAttribute('data-email-card-size') || 'normal';
    const tables = Array.from(wrap.querySelectorAll('table')).filter((t) => (t.outerHTML || '').toLowerCase().includes('border-radius:16px'));
    const first = tables[0] || null;
    const second = tables[1] || null;

    function parseInner(t) {
      if (!t) return { img: '', title: '', desc: '', price: '', listPrice: '', ctaText: '', ctaUrl: '' };
      const img = t.querySelector('img')?.getAttribute('src') || '';
      const title = t.querySelector('p')?.textContent || '';
      const ps = t.querySelectorAll('p');
      const desc = ps[1] ? ps[1].textContent : '';
      const price = ps[2] ? ps[2].textContent : '';
      const listPrice = t.querySelector('[data-email-price="list"]')?.textContent || '';
      const a = t.querySelector('a[href]');
      const ctaText = a ? a.textContent || '' : '';
      const ctaUrl = a ? a.getAttribute('href') || '' : '';
      return { img, title, desc, price, listPrice, ctaText, ctaUrl };
    }

    return { a: parseInner(first), b: parseInner(second), size };
  }

  function replaceBlockHtml(targetEl, html) {
    if (!targetEl) return false;
    try {
      targetEl.outerHTML = html;
      return true;
    } catch {
      return false;
    }
  }

  async function cbUploadFile(file, msgEl, outInput) {
    if (!file) return;
    if (msgEl) msgEl.textContent = 'Subiendo...';

    try {
      const csrf = form?.querySelector('input[name="csrf"]')?.value || '';
      const fd = new FormData();
      if (csrf) fd.append('csrf', csrf);
      fd.append('image', file);

      const res = await fetch(uploadUrl, { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data || !data.ok || !data.url) {
        throw new Error(data && data.error ? data.error : 'No se pudo subir.');
      }

      if (outInput) outInput.value = data.url;
      if (msgEl) msgEl.textContent = 'Listo OK';
    } catch (e) {
      if (msgEl) msgEl.textContent = `Error: ${e && e.message ? e.message : 'no se pudo subir'}`;
    }
  }

  const cbOpenBtns = Array.from(document.querySelectorAll('[data-open-card-builder]'));
  cbOpenBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const kind = btn.getAttribute('data-open-card-builder');
      if (kind === 'edit') {
        const block = findSelectedEmailBlock();
        if (!block) {
          alert('Coloca el cursor dentro de una card o grilla para editar.');
          return;
        }
        const isGrid = block.getAttribute('data-email-block') === 'grid2' || (block.outerHTML || '').toLowerCase().includes('padding-right:8px');
        if (isGrid) {
          const parsed = parseGrid2FromBlock(block);
          if (g1.img) g1.img.value = parsed.a.img || '';
          if (g1.title) g1.title.value = parsed.a.title || '';
          if (g1.desc) g1.desc.value = parsed.a.desc || '';
          if (g1.price) g1.price.value = parsed.a.price || '';
          if (g1.listPrice) g1.listPrice.value = parsed.a.listPrice || '';
          if (g1.ctaText) g1.ctaText.value = parsed.a.ctaText || 'Ver';
          if (g1.ctaUrl) g1.ctaUrl.value = parsed.a.ctaUrl || '';
          if (g2.img) g2.img.value = parsed.b.img || '';
          if (g2.title) g2.title.value = parsed.b.title || '';
          if (g2.desc) g2.desc.value = parsed.b.desc || '';
          if (g2.price) g2.price.value = parsed.b.price || '';
          if (g2.listPrice) g2.listPrice.value = parsed.b.listPrice || '';
          if (g2.ctaText) g2.ctaText.value = parsed.b.ctaText || 'Ver';
          if (g2.ctaUrl) g2.ctaUrl.value = parsed.b.ctaUrl || '';
          if (cbGrid2Size) cbGrid2Size.value = parsed.size || 'normal';
          updateDiscountInputs();
          openCbModal('grid2', block);
        } else {
          const parsed = parseCardFromBlock(block);
          if (cbCardImg) cbCardImg.value = parsed.img || '';
          if (cbCardTitle) cbCardTitle.value = parsed.title || '';
          if (cbCardDesc) cbCardDesc.value = parsed.desc || '';
          if (cbCardPrice) cbCardPrice.value = parsed.price || '';
          if (cbCardListPrice) cbCardListPrice.value = parsed.listPrice || '';
          if (cbCardSize) cbCardSize.value = parsed.size || 'normal';
          updateDiscountInputs();
          if (cbCardCtaText) cbCardCtaText.value = parsed.ctaText || 'Ver';
          if (cbCardCtaUrl) cbCardCtaUrl.value = parsed.ctaUrl || '';
          openCbModal('card', block);
        }
        return;
      }

      if (kind === 'grid2') {
        if (g1.ctaText) g1.ctaText.value = g1.ctaText.value || 'Ver';
        if (g2.ctaText) g2.ctaText.value = g2.ctaText.value || 'Ver';
        openCbModal('grid2', null);
      } else {
        if (cbCardCtaText) cbCardCtaText.value = cbCardCtaText.value || 'Ver';
        openCbModal('card', null);
      }
    });
  });

  cbTabs.forEach((b) => {
    b.addEventListener('click', () => showCbTab(b.getAttribute('data-card-builder-tab')));
  });

  if (cbModal) {
    cbModal.querySelectorAll('[data-card-modal-close]').forEach((b) => b.addEventListener('click', closeCbModal));
    cbModal.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeCbModal();
    });
  }

  const uploadBtns = Array.from(document.querySelectorAll('[data-cb-upload-btn]'));
  uploadBtns.forEach((b) => {
    b.addEventListener('click', async () => {
      const key = b.getAttribute('data-cb-upload-btn');
      if (key === 'card') cbCardUpload?.click();
      else if (key === 'g1') g1.upload?.click();
      else if (key === 'g2') g2.upload?.click();
    });
  });

  cbCardUpload?.addEventListener('change', async () => {
    const file = cbCardUpload.files && cbCardUpload.files[0] ? cbCardUpload.files[0] : null;
    await cbUploadFile(file, cbCardUploadMsg, cbCardImg);
  });
  g1.upload?.addEventListener('change', async () => {
    const file = g1.upload.files && g1.upload.files[0] ? g1.upload.files[0] : null;
    await cbUploadFile(file, g1.msg, g1.img);
  });
  g2.upload?.addEventListener('change', async () => {
    const file = g2.upload.files && g2.upload.files[0] ? g2.upload.files[0] : null;
    await cbUploadFile(file, g2.msg, g2.img);
  });

  [cbCardPrice, cbCardListPrice].forEach((el) => el && el.addEventListener('input', updateDiscountInputs));
  [g1?.price, g1?.listPrice, g2?.price, g2?.listPrice].forEach((el) => el && el.addEventListener('input', updateDiscountInputs));
  updateDiscountInputs();

  const productsSearchUrl = document.getElementById('email-products-search-url')?.value || '';
  setupProductPicker({
    productsSearchUrl,
    updateDiscountInputs,
    cbCard: {
      img: cbCardImg,
      title: cbCardTitle,
      desc: cbCardDesc,
      price: cbCardPrice,
      listPrice: cbCardListPrice,
      ctaText: cbCardCtaText,
      ctaUrl: cbCardCtaUrl,
    },
    g1,
    g2,
  });

  cbApply?.addEventListener('click', () => {
    if (cbMode === 'grid2') {
      const html = buildGrid2Html(
        { img: g1.img?.value, title: g1.title?.value, desc: g1.desc?.value, price: g1.price?.value, listPrice: g1.listPrice?.value, ctaText: g1.ctaText?.value, ctaUrl: g1.ctaUrl?.value, size: cbGrid2Size?.value },
        { img: g2.img?.value, title: g2.title?.value, desc: g2.desc?.value, price: g2.price?.value, listPrice: g2.listPrice?.value, ctaText: g2.ctaText?.value, ctaUrl: g2.ctaUrl?.value, size: cbGrid2Size?.value },
        cbGrid2Size?.value,
        { sanitizeUrl: safeUrl, defaultSize: cbGrid2Size?.value || 'normal' },
      );
      const replaced = cbReplaceEl ? replaceBlockHtml(cbReplaceEl, html) : false;
      if (!replaced && insertHtml) insertHtml(html);
    } else {
      const html = buildCardHtml({
        img: cbCardImg?.value,
        title: cbCardTitle?.value,
        desc: cbCardDesc?.value,
        price: cbCardPrice?.value,
        listPrice: cbCardListPrice?.value,
        ctaText: cbCardCtaText?.value,
        ctaUrl: cbCardCtaUrl?.value,
        size: cbCardSize?.value,
      }, { sanitizeUrl: safeUrl, defaultSize: cbCardSize?.value || 'normal' });
      const replaced = cbReplaceEl ? replaceBlockHtml(cbReplaceEl, html) : false;
      if (!replaced && insertHtml) insertHtml(html);
    }
    if (syncFromVisual) syncFromVisual();
    if (updateInboxAndChecklist) updateInboxAndChecklist();
    closeCbModal();
  });
}

