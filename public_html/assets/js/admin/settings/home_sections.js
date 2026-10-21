export function init() {
  const root = document.querySelector('[data-home-sections-settings]');
  if (!root) return;

  const productsSearchUrl = root.querySelector('[data-home-sections-products-search-url]')?.value || '';

  const sectionsList = root.querySelector('[data-home-sections-list]');
  const sectionTemplate = root.querySelector('[data-home-section-template]');

  const picker = root.querySelector('[data-home-sections-picker]');
  const pickerQ = root.querySelector('[data-home-sections-picker-q]');
  const pickerSearch = root.querySelector('[data-home-sections-picker-search]');
  const pickerStatus = root.querySelector('[data-home-sections-picker-status]');
  const pickerResults = root.querySelector('[data-home-sections-picker-results]');

  let targetSection = null;
  let nextIndex = 0;

  const detectMaxIndex = () => {
    const names = Array.from(root.querySelectorAll('[name^="section_key["]'))
      .map((el) => String(el.getAttribute('name') || ''));
    const max = names.reduce((acc, name) => {
      const m = name.match(/\[(\d+)\]/);
      if (!m) return acc;
      const n = Number(m[1]);
      return Number.isInteger(n) && n > acc ? n : acc;
    }, -1);
    nextIndex = max + 1;
  };

  const syncSectionIds = (section) => {
    if (!section) return;
    const input = section.querySelector('[data-home-section-ids]');
    if (!input) return;
    const ids = Array.from(section.querySelectorAll('[data-home-product-row]'))
      .map((el) => Number(el.getAttribute('data-product-id') || 0))
      .filter((v) => Number.isInteger(v) && v > 0);
    input.value = ids.join(',');
  };

  const syncSectionPreview = (section) => {
    if (!section) return;
    const titleInput = section.querySelector('[data-home-section-title]');
    const keyInput = section.querySelector('[data-home-section-key-input]');
    const preview = section.querySelector('[data-home-section-title-preview]');
    const key = (keyInput?.value || '').trim();
    section.setAttribute('data-home-section-key', key);
    if (!preview) return;
    const title = (titleInput?.value || '').trim();
    preview.textContent = title || 'Seccion';
  };

  const createProductRow = (item) => {
    const row = document.createElement('div');
    row.className = 'av3-border av3-radius-control av3-pad-2 av3-flex av3-items-center av3-gap-3';
    row.setAttribute('data-home-product-row', '');
    row.setAttribute('data-product-id', String(item.id || 0));

    const img = document.createElement('img');
    img.className = 'av3-w-12 av3-h-12 av3-object-cover av3-rounded av3-border av3-surface-white';
    img.src = item.image_url || '';
    img.alt = '';

    const meta = document.createElement('div');
    meta.className = 'av3-min-w-0 av3-flex-1';

    const title = document.createElement('div');
    title.className = 'av3-type-body av3-weight-medium av3-truncate';
    title.textContent = item.name || '(Sin nombre)';

    const sub = document.createElement('div');
    sub.className = 'av3-type-caption av3-opacity-70 av3-truncate';
    const bits = ['ID ' + String(item.id || 0)];
    if (item.sku) bits.push('SKU: ' + String(item.sku));
    sub.textContent = bits.join(' - ');

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn-secondary';
    removeBtn.setAttribute('data-home-product-remove', '');
    removeBtn.textContent = 'Quitar';

    meta.appendChild(title);
    meta.appendChild(sub);
    row.appendChild(img);
    row.appendChild(meta);
    row.appendChild(removeBtn);
    return row;
  };

  const createSection = () => {
    if (!sectionsList || !sectionTemplate) return null;
    const idx = nextIndex++;
    const html = String(sectionTemplate.innerHTML || '').replaceAll('__IDX__', String(idx));
    const box = document.createElement('div');
    box.innerHTML = html.trim();
    const section = box.firstElementChild;
    if (!section) return null;
    sectionsList.appendChild(section);
    syncSectionIds(section);
    syncSectionPreview(section);
    return section;
  };

  const setPickerOpen = (open) => {
    if (!picker) return;
    picker.classList.toggle('av3-hidden', !open);
    picker.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) setTimeout(() => pickerQ?.focus(), 40);
  };

  const renderPickerItems = (items) => {
    if (!pickerResults) return;
    pickerResults.innerHTML = '';

    if (!Array.isArray(items) || items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'av3-pad-3 av3-type-body av3-opacity-70';
      empty.textContent = 'No se encontraron productos.';
      pickerResults.appendChild(empty);
      return;
    }

    items.forEach((it) => {
      const row = document.createElement('div');
      row.className = 'av3-pad-3 av3-border-bottom av3-last-no-bottom av3-flex av3-items-center av3-gap-3';

      const thumb = document.createElement('div');
      thumb.className = 'av3-w-12 av3-h-12 av3-radius-md av3-overflow-hidden av3-border av3-surface-white av3-shrink-0';
      if (it.image_url) {
        const image = document.createElement('img');
        image.src = it.image_url;
        image.alt = '';
        image.loading = 'lazy';
        image.decoding = 'async';
        image.className = 'av3-w-full av3-h-full av3-object-cover';
        thumb.appendChild(image);
      }

      const meta = document.createElement('div');
      meta.className = 'av3-min-w-0 av3-flex-1';

      const title = document.createElement('div');
      title.className = 'av3-type-body av3-weight-medium av3-truncate';
      title.textContent = it.name || '(Sin nombre)';

      const sub = document.createElement('div');
      sub.className = 'av3-type-caption av3-opacity-70 av3-truncate';
      const bits = ['ID ' + String(it.id || 0)];
      if (it.sku) bits.push('SKU: ' + String(it.sku));
      if (it.slug) bits.push(String(it.slug));
      sub.textContent = bits.join(' - ');

      meta.appendChild(title);
      meta.appendChild(sub);

      const useBtn = document.createElement('button');
      useBtn.type = 'button';
      useBtn.className = 'btn-primary';
      useBtn.textContent = 'Agregar';
      useBtn.addEventListener('click', () => {
        if (!targetSection) return;
        const list = targetSection.querySelector('[data-home-section-list]');
        if (!list) return;

        const id = Number(it.id || 0);
        if (!Number.isInteger(id) || id <= 0) return;

        if (list.querySelector(`[data-home-product-row][data-product-id="${id}"]`)) {
          if (pickerStatus) pickerStatus.textContent = 'Ese producto ya esta en la seccion.';
          return;
        }

        const count = list.querySelectorAll('[data-home-product-row]').length;
        if (count >= 12) {
          if (pickerStatus) pickerStatus.textContent = 'Maximo 12 productos por seccion.';
          return;
        }

        list.appendChild(createProductRow(it));
        syncSectionIds(targetSection);
        setPickerOpen(false);
        targetSection = null;
      });

      row.appendChild(thumb);
      row.appendChild(meta);
      row.appendChild(useBtn);
      pickerResults.appendChild(row);
    });
  };

  const runPickerSearch = async () => {
    const q = (pickerQ?.value || '').trim();
    if (!productsSearchUrl) {
      if (pickerStatus) pickerStatus.textContent = 'URL de busqueda no configurada.';
      return;
    }
    if (!q) {
      if (pickerStatus) pickerStatus.textContent = 'Escribe un termino.';
      renderPickerItems([]);
      return;
    }

    if (pickerStatus) pickerStatus.textContent = 'Buscando...';
    try {
      const res = await fetch(`${productsSearchUrl}?q=${encodeURIComponent(q)}`, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || data.ok !== true) {
        if (pickerStatus) pickerStatus.textContent = 'No se pudo buscar.';
        renderPickerItems([]);
        return;
      }
      const items = Array.isArray(data.items) ? data.items : [];
      if (pickerStatus) pickerStatus.textContent = `${items.length} resultado(s).`;
      renderPickerItems(items);
    } catch (_) {
      if (pickerStatus) pickerStatus.textContent = 'Error de red.';
      renderPickerItems([]);
    }
  };

  detectMaxIndex();
  root.querySelectorAll('[data-home-section]').forEach((section) => {
    syncSectionIds(section);
    syncSectionPreview(section);
  });

  root.addEventListener('input', (e) => {
    if (e.target.matches('[data-home-section-title], [data-home-section-key-input]')) {
      syncSectionPreview(e.target.closest('[data-home-section]'));
    }
  });

  root.addEventListener('click', (e) => {
    const addSectionBtn = e.target.closest('[data-home-section-create]');
    if (addSectionBtn) {
      const created = createSection();
      created?.querySelector('[data-home-section-title]')?.focus();
      return;
    }

    const addBtn = e.target.closest('[data-home-section-add]');
    if (addBtn) {
      targetSection = addBtn.closest('[data-home-section]');
      if (pickerQ) pickerQ.value = '';
      if (pickerStatus) pickerStatus.textContent = 'Escribe y busca.';
      renderPickerItems([]);
      setPickerOpen(true);
      return;
    }

    const removeSectionBtn = e.target.closest('[data-home-section-remove]');
    if (removeSectionBtn) {
      const section = removeSectionBtn.closest('[data-home-section]');
      section?.remove();
      return;
    }

    const removeBtn = e.target.closest('[data-home-product-remove]');
    if (removeBtn) {
      const section = removeBtn.closest('[data-home-section]');
      const row = removeBtn.closest('[data-home-product-row]');
      if (row) row.remove();
      syncSectionIds(section);
      return;
    }

    if (e.target.closest('[data-home-sections-picker-close]')) {
      setPickerOpen(false);
      targetSection = null;
    }
  });

  pickerSearch?.addEventListener('click', runPickerSearch);
  pickerQ?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      runPickerSearch();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && picker && !picker.classList.contains('av3-hidden')) {
      setPickerOpen(false);
      targetSection = null;
    }
  });
}
