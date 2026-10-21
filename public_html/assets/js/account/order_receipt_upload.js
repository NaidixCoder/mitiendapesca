export function initOrderReceiptUpload() {
  const forms = document.querySelectorAll('.order-show-upload-form');
  if (!forms.length) return;

  const fmtSize = (bytes) => {
    const mb = bytes / (1024 * 1024);
    if (mb >= 1) return `${mb.toFixed(1)} MB`;
    const kb = bytes / 1024;
    return `${Math.max(1, Math.round(kb))} KB`;
  };

  const extLabel = (name, type) => {
    const byType = (type || '').toLowerCase();
    if (byType.includes('jpeg')) return 'JPG';
    if (byType.includes('png')) return 'PNG';
    if (byType.includes('pdf')) return 'PDF';
    const p = String(name || '').split('.');
    return p.length > 1 ? p.pop().toUpperCase() : 'ARCHIVO';
  };

  forms.forEach((form) => {
    const input = form.querySelector('.order-show-upload-form__input');
    const dropzone = form.querySelector('.order-show-upload-dropzone');
    const selected = form.querySelector('[data-upload-selected]');
    const nameEl = form.querySelector('[data-upload-file-name]');
    const detailEl = form.querySelector('[data-upload-file-detail]');
    const readyText = form.querySelector('[data-upload-ready-text]');
    const hint = form.querySelector('.order-show-upload-selected__hint');
    const changeBtn = form.querySelector('[data-upload-change]');
    const changeText = form.querySelector('[data-upload-change-text]');
    const removeBtn = form.querySelector('[data-upload-remove]');
    const sentNote = form.querySelector('[data-upload-sent-note]');
    const waBtn = form.querySelector('[data-upload-whatsapp]');
    const submit = form.querySelector('button[type="submit"]');
    const uploadBlock = form.closest('#subir-comprobante');
    const hasExisting = form.dataset.uploadExisting === '1';
    const existingName = form.dataset.uploadExistingName || 'comprobante.jpg';
    const existingDetail = form.dataset.uploadExistingDetail || '0 KB';
    const whatsappHref = form.dataset.uploadWhatsappHref || '';

    if (!input || !dropzone || !selected || !nameEl || !detailEl || !submit) return;

    const render = () => {
      const file = input.files && input.files[0] ? input.files[0] : null;
      const hasFile = !!file;
      const isUploaded = hasExisting && !hasFile;
      const showSelected = hasFile || isUploaded;

      form.classList.toggle('is-uploaded', isUploaded);
      form.classList.toggle('has-pending-file', hasFile);
      uploadBlock?.classList.toggle('is-uploaded', isUploaded);

      dropzone.hidden = showSelected;
      selected.hidden = !showSelected;

      if (!showSelected) {
        submit.hidden = false;
        submit.disabled = true;
        submit.setAttribute('aria-disabled', 'true');
        nameEl.textContent = 'archivo.jpg';
        detailEl.textContent = '0 MB';
        if (readyText) readyText.textContent = 'Listo para subir';
        if (changeText) changeText.textContent = 'Cambiar';
        if (hint) hint.hidden = false;
        if (removeBtn) removeBtn.hidden = false;
        if (sentNote) sentNote.hidden = true;
        if (waBtn) waBtn.hidden = true;
        return;
      }

      if (isUploaded) {
        submit.hidden = true;
        submit.disabled = true;
        submit.setAttribute('aria-disabled', 'true');
        nameEl.textContent = existingName;
        detailEl.textContent = existingDetail;
        if (readyText) readyText.textContent = 'Subido';
        if (changeText) changeText.textContent = 'Cambiar';
        if (hint) hint.hidden = true;
        if (removeBtn) removeBtn.hidden = true;
        if (sentNote) sentNote.hidden = false;
        if (waBtn) {
          waBtn.hidden = whatsappHref === '';
          if (whatsappHref !== '') waBtn.setAttribute('href', whatsappHref);
        }
        return;
      }

      submit.hidden = false;
      submit.disabled = false;
      submit.setAttribute('aria-disabled', 'false');
      nameEl.textContent = file.name || 'archivo';
      detailEl.textContent = `${fmtSize(file.size || 0)}`;
      if (readyText) readyText.textContent = 'Listo para subir';
      if (changeText) changeText.textContent = 'Cambiar';
      if (hint) hint.hidden = false;
      if (removeBtn) removeBtn.hidden = false;
      if (sentNote) sentNote.hidden = true;
      if (waBtn) waBtn.hidden = true;
    };

    changeBtn?.addEventListener('click', () => input.click());

    removeBtn?.addEventListener('click', () => {
      input.value = '';
      render();
    });

    input.addEventListener('change', render);
    render();
  });
}
