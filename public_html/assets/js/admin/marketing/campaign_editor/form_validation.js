export function setupFormValidation(options) {
  const {
    form,
    editor,
    rawArea,
    bodyHidden,
    getMode,
    syncFromHtml,
    syncFromVisual,
  } = options || {};

  if (!form) return () => true;

  const setInvalid = (el) => {
    if (!el) return;
    if (el.dataset._invalid === '1') return;
    el.dataset._invalid = '1';
    el.classList.add('is-invalid');
    el.setAttribute('aria-invalid', 'true');
  };

  const clearInvalid = (el) => {
    if (!el || el.dataset._invalid !== '1') return;
    el.dataset._invalid = '0';
    el.classList.remove('is-invalid');
    el.removeAttribute('aria-invalid');
  };

  const isBlank = (v) => String(v ?? '').trim() === '';
  const isEmptyEmailHtml = (html) => {
    if (isBlank(html)) return true;
    const tmp = document.createElement('div');
    tmp.innerHTML = String(html);
    if (tmp.querySelector('img, table, a, button, ul, ol, li')) return false;
    const text = (tmp.textContent || '').replace(/\u00a0/g, ' ').trim();
    return text.length === 0;
  };

  const $name = form.querySelector('input[name="name"]');
  const $subject = form.querySelector('input[name="subject"]');
  const $status = form.querySelector('select[name="status"]');
  const $scheduledAt = form.querySelector('input[name="scheduled_at"]');

  [$name, $subject, $scheduledAt].forEach((el) => {
    if (!el) return;
    el.addEventListener('input', () => {
      if (!isBlank(el.value)) clearInvalid(el);
    });
  });

  if ($status) {
    $status.addEventListener('change', () => clearInvalid($status));
  }

  if (editor) {
    editor.addEventListener('input', () => {
      if (!isEmptyEmailHtml(editor.innerHTML)) clearInvalid(editor);
    });
  }
  if (rawArea) {
    rawArea.addEventListener('input', () => {
      if (!isEmptyEmailHtml(rawArea.value)) clearInvalid(rawArea);
    });
  }

  const validateBeforeSubmit = () => {
    let firstInvalid = null;

    if ($name && isBlank($name.value)) {
      setInvalid($name);
      firstInvalid ??= $name;
    } else {
      clearInvalid($name);
    }

    if ($subject && isBlank($subject.value)) {
      setInvalid($subject);
      firstInvalid ??= $subject;
    } else {
      clearInvalid($subject);
    }

    if ($status && String($status.value) === 'scheduled') {
      if ($scheduledAt && isBlank($scheduledAt.value)) {
        setInvalid($scheduledAt);
        firstInvalid ??= $scheduledAt;
      } else {
        clearInvalid($scheduledAt);
      }
    } else {
      clearInvalid($scheduledAt);
    }

    const contentEl = getMode && getMode() === 'html' && rawArea ? rawArea : editor;
    const htmlValue = bodyHidden ? bodyHidden.value : '';
    if (contentEl) {
      if (isEmptyEmailHtml(htmlValue)) {
        setInvalid(contentEl);
        firstInvalid ??= contentEl;
      } else {
        clearInvalid(contentEl);
      }
    }

    if (firstInvalid) {
      firstInvalid.focus({ preventScroll: true });
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }
    return true;
  };

  form.addEventListener('submit', (e) => {
    if (getMode && getMode() === 'html') syncFromHtml && syncFromHtml();
    else syncFromVisual && syncFromVisual();

    if (!validateBeforeSubmit()) e.preventDefault();
  });

  return validateBeforeSubmit;
}

