export function bindCopyButtons(pushToast) {
  const buttons = document.querySelectorAll('[data-copy]');
  if (!buttons || !buttons.length) return;

  buttons.forEach((btn) => {
    if (!(btn instanceof HTMLElement)) return;
    if (btn.dataset.bound === '1') return;
    btn.dataset.bound = '1';

    btn.addEventListener('click', async () => {
      const text = String(btn.dataset.copy || '').trim();
      if (!text) {
        pushToast({ type: 'warn', text: 'Nada para copiar.' });
        return;
      }

      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(text);
        } else {
          const ta = document.createElement('textarea');
          ta.value = text;
          ta.style.position = 'fixed';
          ta.style.left = '-9999px';
          document.body.appendChild(ta);
          ta.focus();
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        }
        pushToast({ type: 'ok', text: 'Copiado.' });
      } catch (_) {
        pushToast({ type: 'error', text: 'No se pudo copiar.' });
      }
    });
  });
}
