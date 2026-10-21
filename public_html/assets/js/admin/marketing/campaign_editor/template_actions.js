export function triggerSaveAsTemplate() {
  try {
    const btn = document.getElementById('save-as-template');
    const form = document.getElementById('save-as-template-form');
    if (!btn || !form) return false;

    const subjectInput = document.querySelector('input[name="subject"]');
    const preheaderInput = document.querySelector('input[name="preheader"]');

    const bodyInput =
      document.querySelector('input[name="body_html"]') ||
      document.querySelector('textarea[name="body_html"]') ||
      document.getElementById('body_html') ||
      document.getElementById('body_html_raw');

    const html = (bodyInput && ('value' in bodyInput) ? String(bodyInput.value) : '').trim();
    const suggested = (subjectInput?.value || '').trim() || 'Plantilla';
    const name = (window.prompt('Nombre de la plantilla:', suggested) || '').trim();
    if (!name) return false;

    const nameField = form.querySelector('input[name="name"]');
    const htmlField = form.querySelector('input[name="body_html"]') || form.querySelector('textarea[name="body_html"]');
    if (nameField) nameField.value = name;
    if (htmlField) htmlField.value = html;

    const subjField = form.querySelector('input[name="subject_default"]');
    if (subjField) subjField.value = (subjectInput?.value || '').trim();

    const preField = form.querySelector('input[name="preheader_default"]');
    if (preField) preField.value = (preheaderInput?.value || '').trim();

    const modeField = form.querySelector('input[name="editor_mode"]');
    if (modeField) {
      const mode = document.querySelector('input[name="editor_mode_current"]')?.value || '';
      modeField.value = mode;
    }

    form.submit();
    return true;
  } catch (err) {
    console.warn('[email_campaign_editor] triggerSaveAsTemplate error', err);
    return false;
  }
}

export function bindSaveAsTemplateFallback(formEl, getHtml) {
  if (!formEl || formEl.dataset.bound) return;
  formEl.dataset.bound = '1';

  formEl.addEventListener('submit', () => {
    try {
      const html = String(getHtml ? getHtml() : '').trim();
      const target = formEl.querySelector('input[name="body_html"]') || formEl.querySelector('textarea[name="body_html"]');
      if (target) target.value = html;
    } catch (_) {
      // no-op
    }
  });
}
