export function init() {
  const root = document.querySelector('[data-home-hero-settings]');
  if (!root) return;

  const csrf = root.querySelector('[data-home-hero-csrf]')?.value || '';
  const uploadUrl = root.querySelector('[data-hero-upload-url]')?.value || '';
  const deleteUrl = root.querySelector('[data-hero-delete-url]')?.value || '';
  const productsSearchUrl = root.querySelector('[data-hero-products-search-url]')?.value || '';
  const fileInput = root.querySelector('[data-hero-file-input]');

  const picker = root.querySelector('[data-hero-product-picker]');
  const pickerQ = root.querySelector('[data-hero-picker-q]');
  const pickerSearch = root.querySelector('[data-hero-picker-search]');
  const pickerStatus = root.querySelector('[data-hero-picker-status]');
  const pickerResults = root.querySelector('[data-hero-picker-results]');

  let uploadTargetInput = null;
  let pickerTargetSlide = null;
  let pickerTargetMode = 'url'; // url | desktop | mobile

  const setSlideStatus = (slide, msg) => {
    const el = slide?.querySelector('[data-hero-upload-status]');
    if (el) el.textContent = msg || '';
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
        const img = document.createElement('img');
        img.src = it.image_url;
        img.alt = '';
        img.loading = 'lazy';
        img.decoding = 'async';
        img.className = 'av3-w-full av3-h-full av3-object-cover';
        thumb.appendChild(img);
      }

      const meta = document.createElement('div');
      meta.className = 'av3-min-w-0 av3-flex-1';

      const title = document.createElement('div');
      title.className = 'av3-type-body av3-weight-medium av3-truncate';
      title.textContent = it.name || '(Sin nombre)';

      const sub = document.createElement('div');
      sub.className = 'av3-type-caption av3-opacity-70 av3-truncate';
      const bits = [];
      if (it.sku) bits.push('SKU: ' + it.sku);
      if (it.slug) bits.push(it.slug);
      sub.textContent = bits.join(' · ');

      meta.appendChild(title);
      meta.appendChild(sub);

      const useBtn = document.createElement('button');
      useBtn.type = 'button';
      useBtn.className = 'btn-primary';
      useBtn.textContent = 'Usar';
      useBtn.addEventListener('click', () => {
        if (!pickerTargetSlide) return;

        const urlInput = pickerTargetSlide.querySelector('[data-hero-url]');
        const desktopInput = pickerTargetSlide.querySelector('[data-hero-image-desktop]');
        const mobileInput = pickerTargetSlide.querySelector('[data-hero-image-mobile]');
        const titleInput = pickerTargetSlide.querySelector('[data-hero-title]');
        const altInput = pickerTargetSlide.querySelector('[data-hero-alt]');

        if (urlInput && it.product_url && (pickerTargetMode === 'url' || !urlInput.value)) {
          urlInput.value = String(it.product_url);
        }

        if (it.image_url) {
          if (pickerTargetMode === 'desktop' && desktopInput) {
            desktopInput.value = String(it.image_url);
          } else if (pickerTargetMode === 'mobile' && mobileInput) {
            mobileInput.value = String(it.image_url);
          } else {
            if (desktopInput && !desktopInput.value) desktopInput.value = String(it.image_url);
            if (mobileInput && !mobileInput.value) mobileInput.value = String(it.image_url);
          }
        }

        if (titleInput && !titleInput.value && it.name) titleInput.value = String(it.name);
        if (altInput && !altInput.value && it.name) altInput.value = String(it.name);

        setSlideStatus(pickerTargetSlide, 'Producto aplicado al slide.');
        setPickerOpen(false);
        pickerTargetSlide = null;
        pickerTargetMode = 'url';
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

  if (fileInput) {
    fileInput.addEventListener('change', async () => {
      const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!file || !uploadTargetInput) return;

      const slide = uploadTargetInput.closest('[data-hero-slide]');
      if (!uploadUrl || !csrf) {
        setSlideStatus(slide, 'Falta configuracion de upload/CSRF.');
        return;
      }

      setSlideStatus(slide, 'Subiendo imagen...');
      try {
        const fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('image', file);

        const res = await fetch(uploadUrl, {
          method: 'POST',
          body: fd,
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });

        const data = await res.json().catch(() => null);
        if (!res.ok || !data || data.ok !== true || !data.url) {
          const msg = data && data.error ? String(data.error) : 'No se pudo subir la imagen.';
          setSlideStatus(slide, msg);
          return;
        }

        const nextValue = String(data.url || '').trim();
        uploadTargetInput.value = nextValue;

        // Regla de negocio: al subir desktop se limpia mobile para evitar arrastre
        // de imagen vieja elegida desde catalogo.
        const isDesktopTarget = uploadTargetInput.matches('[data-hero-image-desktop]');
        if (isDesktopTarget && slide) {
          const mobileInput = slide.querySelector('[data-hero-image-mobile]');
          if (mobileInput) {
            mobileInput.value = '';
          }
          setSlideStatus(slide, 'Imagen desktop subida. Imagen mobile limpiada.');
          return;
        }

        setSlideStatus(slide, 'Imagen subida.');
      } catch (_) {
        setSlideStatus(slide, 'Error de red al subir imagen.');
      } finally {
        fileInput.value = '';
        uploadTargetInput = null;
      }
    });
  }

  root.addEventListener('click', (e) => {
    const uploadBtn = e.target.closest('[data-hero-upload-btn]');
    if (uploadBtn) {
      const slide = uploadBtn.closest('[data-hero-slide]');
      if (!slide || !fileInput) return;
      const target = uploadBtn.getAttribute('data-hero-upload-btn');
      uploadTargetInput = target === 'mobile'
        ? slide.querySelector('[data-hero-image-mobile]')
        : slide.querySelector('[data-hero-image-desktop]');
      fileInput.click();
      return;
    }

    const pickBtn = e.target.closest('[data-hero-pick-product]');
    if (pickBtn) {
      pickerTargetSlide = pickBtn.closest('[data-hero-slide]');
      pickerTargetMode = (pickBtn.getAttribute('data-hero-pick-product') || 'url').trim() || 'url';
      if (pickerStatus) pickerStatus.textContent = 'Escribe y busca.';
      if (pickerQ) pickerQ.value = '';
      renderPickerItems([]);
      setPickerOpen(true);
      return;
    }

    const clearBtn = e.target.closest('[data-hero-clear-image]');
    if (clearBtn) {
      const slide = clearBtn.closest('[data-hero-slide]');
      if (!slide) return;
      const target = clearBtn.getAttribute('data-hero-clear-image');
      const input = target === 'mobile'
        ? slide.querySelector('[data-hero-image-mobile]')
        : slide.querySelector('[data-hero-image-desktop]');
      if (input) input.value = '';
      setSlideStatus(slide, 'Campo limpiado.');
      return;
    }

    const deleteBtn = e.target.closest('[data-hero-delete-file]');
    if (deleteBtn) {
      const slide = deleteBtn.closest('[data-hero-slide]');
      if (!slide) return;

      const target = deleteBtn.getAttribute('data-hero-delete-file');
      const input = target === 'mobile'
        ? slide.querySelector('[data-hero-image-mobile]')
        : slide.querySelector('[data-hero-image-desktop]');
      if (!input) return;

      const fileUrl = String(input.value || '').trim();
      if (!fileUrl) {
        setSlideStatus(slide, 'No hay archivo para eliminar.');
        return;
      }
      if (!deleteUrl || !csrf) {
        setSlideStatus(slide, 'Falta configuracion de eliminacion/CSRF.');
        return;
      }

      setSlideStatus(slide, 'Eliminando archivo...');
      const fd = new FormData();
      fd.append('_csrf', csrf);
      fd.append('url', fileUrl);
      fetch(deleteUrl, {
        method: 'POST',
        body: fd,
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      })
        .then((res) => res.json().catch(() => null).then((data) => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
          if (!ok || !data || data.ok !== true) {
            const msg = data && data.error ? String(data.error) : 'No se pudo eliminar el archivo.';
            setSlideStatus(slide, msg);
            return;
          }
          input.value = '';
          setSlideStatus(slide, data.deleted ? 'Archivo eliminado y campo limpiado.' : 'Campo limpiado (archivo no encontrado).');
        })
        .catch(() => {
          setSlideStatus(slide, 'Error de red al eliminar archivo.');
        });
      return;
    }

    if (e.target.closest('[data-hero-picker-close]')) {
      setPickerOpen(false);
      pickerTargetSlide = null;
      pickerTargetMode = 'url';
      return;
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
      pickerTargetSlide = null;
      pickerTargetMode = 'url';
    }
  });
}
