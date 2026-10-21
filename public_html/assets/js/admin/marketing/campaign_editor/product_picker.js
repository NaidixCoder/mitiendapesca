import { setEmailDialogOpen } from './dialog_scroll_lock.js';

export function setupProductPicker(options) {
  const {
    productsSearchUrl,
    updateDiscountInputs,
    cbCard,
    g1,
    g2,
  } = options || {};

  const ppModal = document.getElementById('email-product-picker-modal');
  const ppQ = document.getElementById('pp-q');
  const ppSearch = document.getElementById('pp-search');
  const ppResults = document.getElementById('pp-results');
  const ppStatus = document.getElementById('pp-status');
  let ppTarget = null;

  function ppSetStatus(msg) {
    if (ppStatus) ppStatus.textContent = msg;
  }

  function ppShow() {
    if (!ppModal) return;
    ppModal.classList.remove('is-hidden');
    ppModal.setAttribute('aria-hidden', 'false');
    setEmailDialogOpen(ppModal, true);
    setTimeout(() => ppQ?.focus(), 50);
  }

  function ppHide() {
    if (!ppModal) return;
    ppModal.classList.add('is-hidden');
    ppModal.setAttribute('aria-hidden', 'true');
    setEmailDialogOpen(ppModal, false);
    ppTarget = null;
  }

  function ppFillTarget(target, item) {
    const img = item.image_url || '';
    const title = item.name || '';
    const desc = item.short_desc || '';
    const price = item.price != null ? String(item.price) : '';
    const listPrice = item.list_price != null ? String(item.list_price) : '';
    const link = item.product_url || '';

    if (target === 'card') {
      if (cbCard?.img) cbCard.img.value = img;
      if (cbCard?.title) cbCard.title.value = title;
      if (cbCard?.desc) cbCard.desc.value = desc;
      if (cbCard?.price) cbCard.price.value = price;
      if (cbCard?.listPrice) cbCard.listPrice.value = listPrice;
      if (cbCard?.ctaUrl) cbCard.ctaUrl.value = link;
      if (cbCard?.ctaText && !cbCard.ctaText.value) cbCard.ctaText.value = 'Ver';
    } else if (target === 'g1') {
      if (g1?.img) g1.img.value = img;
      if (g1?.title) g1.title.value = title;
      if (g1?.desc) g1.desc.value = desc;
      if (g1?.price) g1.price.value = price;
      if (g1?.listPrice) g1.listPrice.value = listPrice;
      if (g1?.ctaUrl) g1.ctaUrl.value = link;
      if (g1?.ctaText && !g1.ctaText.value) g1.ctaText.value = 'Ver';
    } else if (target === 'g2') {
      if (g2?.img) g2.img.value = img;
      if (g2?.title) g2.title.value = title;
      if (g2?.desc) g2.desc.value = desc;
      if (g2?.price) g2.price.value = price;
      if (g2?.listPrice) g2.listPrice.value = listPrice;
      if (g2?.ctaUrl) g2.ctaUrl.value = link;
      if (g2?.ctaText && !g2.ctaText.value) g2.ctaText.value = 'Ver';
    }

    if (updateDiscountInputs) updateDiscountInputs();
  }

  function ppRender(items) {
    if (!ppResults) return;
    ppResults.innerHTML = '';

    if (!items || items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'av3-email-picker__empty';
      empty.textContent = 'No se encontraron productos.';
      ppResults.appendChild(empty);
      return;
    }

    items.forEach((it) => {
      const row = document.createElement('div');
      row.className = 'av3-email-picker__item';

      const imgWrap = document.createElement('div');
      imgWrap.className = 'av3-email-picker__thumbnail';
      if (it.image_url) {
        const im = document.createElement('img');
        im.src = it.image_url;
        im.alt = '';
        im.className = 'av3-email-picker__thumbnail-image';
        imgWrap.appendChild(im);
      } else {
        imgWrap.innerHTML = '<span class="av3-email-picker__no-image">Sin img</span>';
      }

      const meta = document.createElement('div');
      meta.className = 'av3-email-picker__meta';
      const t = document.createElement('div');
      t.className = 'av3-email-picker__title';
      t.textContent = it.name || '(Sin nombre)';
      const sub = document.createElement('div');
      sub.className = 'av3-email-picker__subtitle';
      const sku = it.sku ? `SKU: ${it.sku}` : '';
      const slug = it.slug ? `Slug: ${it.slug}` : '';
      sub.textContent = [sku, slug].filter(Boolean).join(' · ');

      const priceLine = document.createElement('div');
      priceLine.className = 'av3-email-picker__price';
      const p = it.price != null ? `$ ${it.price}` : '';
      const lp = it.list_price != null ? ` · Lista: $ ${it.list_price}` : '';
      priceLine.textContent = (p + lp).trim();

      meta.appendChild(t);
      meta.appendChild(sub);
      meta.appendChild(priceLine);

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'av3-ui-btn av3-ui-btn--primary';
      btn.textContent = 'Usar';
      btn.addEventListener('click', () => {
        if (!ppTarget) return;
        ppFillTarget(ppTarget, it);
        ppHide();
      });

      row.appendChild(imgWrap);
      row.appendChild(meta);
      row.appendChild(btn);
      ppResults.appendChild(row);
    });
  }

  async function ppDoSearch() {
    const q = (ppQ?.value || '').trim();
    if (!productsSearchUrl) {
      ppSetStatus('No se configuro la URL de busqueda.');
      return;
    }
    if (!q) {
      ppSetStatus('Escribi un termino para buscar.');
      ppRender([]);
      return;
    }
    ppSetStatus('Buscando...');

    try {
      const res = await fetch(`${productsSearchUrl}?q=${encodeURIComponent(q)}`, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || data.ok !== true) {
        ppSetStatus('No se pudo buscar.');
        ppRender([]);
        return;
      }
      ppSetStatus(`${data.items.length} resultado(s). Elegi uno.`);
      ppRender(data.items);
    } catch (e) {
      ppSetStatus('Error de red al buscar.');
      ppRender([]);
    }
  }

  document.querySelectorAll('[data-open-product-picker]').forEach((b) => {
    b.addEventListener('click', () => {
      ppTarget = b.getAttribute('data-open-product-picker');
      ppSetStatus('Escribi y toca "Buscar".');
      if (ppQ) ppQ.value = '';
      if (ppResults) ppResults.innerHTML = '';
      ppShow();
    });
  });

  ppModal?.querySelectorAll('[data-product-picker-close]').forEach((b) => {
    b.addEventListener('click', ppHide);
  });
  ppModal?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') ppHide();
  });
  ppSearch?.addEventListener('click', ppDoSearch);
  ppQ?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      ppDoSearch();
    }
  });

  return {
    open: ppShow,
    close: ppHide,
  };
}

