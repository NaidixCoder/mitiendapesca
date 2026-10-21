import { pushToast } from '../../core/flash.js';

export function toastSafe(kind, msg) {
  const text = String(msg ?? '').trim();
  if (!text) return;

  const map = { ok: 'ok', success: 'ok', warn: 'warn', warning: 'warn', info: 'warn', error: 'error' };
  const type = map[String(kind || 'ok').toLowerCase()] || 'warn';

  try {
    if (typeof pushToast === 'function') {
      pushToast({ type, text });
      return;
    }
  } catch (_) {}

  try {
    if (typeof window !== 'undefined' && typeof window.toast === 'function') {
      window.toast(type, text);
      return;
    }
  } catch (_) {}
}

export function createFeedback(root) {
  const banner = root.querySelector('[data-role="vs-feedback"]');
  const titleEl = root.querySelector('[data-role="vs-feedback-title"]');
  const textEl = root.querySelector('[data-role="vs-feedback-text"]');
  const closeBtn = root.querySelector('[data-role="vs-feedback-close"]');
  const modal = root.querySelector('[data-role="vs-confirm"]');
  const mBackdrop = root.querySelector('[data-role="vs-confirm-backdrop"]');
  const mTitle = root.querySelector('[data-role="vs-confirm-title"]');
  const mText = root.querySelector('[data-role="vs-confirm-text"]');
  const mInputWrap = root.querySelector('[data-role="vs-confirm-input-wrap"]');
  const mInputLabel = root.querySelector('[data-role="vs-confirm-input-label"]');
  const mInput = root.querySelector('[data-role="vs-confirm-input"]');
  const mInputHint = root.querySelector('[data-role="vs-confirm-input-hint"]');
  const mCancel = root.querySelector('[data-role="vs-confirm-cancel"]');
  const mOk = root.querySelector('[data-role="vs-confirm-ok"]');

  if (closeBtn && !closeBtn._vsBound) {
    closeBtn._vsBound = true;
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      hide();
    });
  }

  function setBannerTone(level) {
    if (!banner) return;
    banner.classList.remove('av3-border-amber-200', 'av3-bg-amber-50', 'av3-text-amber-900', 'av3-border-emerald-200', 'av3-bg-emerald-50', 'av3-text-emerald-900', 'av3-border--color-var---danger-30', 'av3-bg--color-var---danger-10', 'av3-text--color-var---danger');
    if (level === 'error') {
      banner.classList.add('av3-border--color-var---danger-30', 'av3-bg--color-var---danger-10', 'av3-text--color-var---danger');
    } else if (level === 'warn') {
      banner.classList.add('av3-border-amber-200', 'av3-bg-amber-50', 'av3-text-amber-900');
    } else if (level === 'ok') {
      banner.classList.add('av3-border-emerald-200', 'av3-bg-emerald-50', 'av3-text-emerald-900');
    }
  }

  function show(level, title, text, opts = {}) {
    if (!banner || !titleEl || !textEl) {
      if (level === 'error') toastSafe('error', title || text);
      else toastSafe(level === 'warn' ? 'warn' : 'ok', title || text);
      return;
    }
    const scope = String(opts.scope || '');
    if (scope) banner.dataset.scope = scope;
    setBannerTone(level);
    titleEl.textContent = String(title || 'Aviso');
    textEl.textContent = String(text || '');
    banner.classList.remove('av3-hidden');
    banner.scrollIntoView?.({ behavior: 'smooth', block: 'nearest' });
  }

  function hide(scope = '') {
    if (!banner) return;
    if (scope && banner.dataset.scope && banner.dataset.scope !== scope) return;
    banner.classList.add('av3-hidden');
    banner.dataset.scope = '';
  }

  async function confirm({ title, text, okText = 'Confirmar', tone = 'danger', requireInput = null } = {}) {
    if (!modal || !mTitle || !mText || !mCancel || !mOk) {
      if (requireInput?.expected) {
        const r = window.prompt(`${text || ''}\n\nEscribi ${requireInput.expected}:`);
        return (r || '').trim();
      }
      return window.confirm(text || 'Confirmar?');
    }

    return new Promise((resolve) => {
      modal.classList.remove('av3-hidden');
      modal.setAttribute('aria-hidden', 'false');
      mTitle.textContent = String(title || 'Confirmar');
      mText.textContent = String(text || '');
      mOk.textContent = String(okText || 'Confirmar');
      mOk.classList.toggle('btn-danger', tone === 'danger');
      mOk.classList.toggle('btn-primary', tone === 'primary');

      let expected = null;
      let onInput = null;
      let onKey = null;
      let onBackdrop = null;
      let onEsc = null;

      const setOkEnabled = (enabled) => {
        try { mOk.disabled = !enabled; } catch (_) {}
        mOk.classList.toggle('av3-opacity-60', !enabled);
        mOk.classList.toggle('cursor-not-allowed', !enabled);
      };

      if (requireInput && mInputWrap && mInput && mInputLabel && mInputHint) {
        expected = String(requireInput.expected || '').trim().toUpperCase();
        mInputWrap.classList.remove('av3-hidden');
        mInputLabel.textContent = String(requireInput.label || 'Confirmacion');
        mInput.placeholder = String(requireInput.placeholder || '');
        mInput.value = '';
        mInputHint.textContent = String(requireInput.hint || '');
        setOkEnabled(false);

        onInput = () => {
          const v = String(mInput.value || '').trim().toUpperCase();
          const ok = (v === expected);
          setOkEnabled(ok);
        };
        onKey = (ev) => {
          if (ev.key === 'Enter') {
            ev.preventDefault();
            if (!mOk.disabled) mOk.click();
          }
        };
        mInput.addEventListener('input', onInput);
        mInput.addEventListener('keydown', onKey);
        queueMicrotask(() => mInput.focus());
      } else if (mInputWrap) {
        mInputWrap.classList.add('av3-hidden');
        setOkEnabled(true);
      }

      const cleanup = () => {
        modal.classList.add('av3-hidden');
        modal.setAttribute('aria-hidden', 'true');
        mCancel.removeEventListener('click', onCancel);
        mOk.removeEventListener('click', onOk);
        if (onBackdrop && mBackdrop) mBackdrop.removeEventListener('click', onBackdrop);
        if (onEsc) document.removeEventListener('keydown', onEsc);
        if (onInput && mInput) mInput.removeEventListener('input', onInput);
        if (onKey && mInput) mInput.removeEventListener('keydown', onKey);
        try { mOk.disabled = false; } catch (_) {}
      };

      const onCancel = (e) => {
        e.preventDefault();
        cleanup();
        resolve(false);
      };

      const onOk = (e) => {
        e.preventDefault();
        const val = (mInput && mInputWrap && !mInputWrap.classList.contains('av3-hidden')) ? String(mInput.value || '').trim() : null;
        if (expected != null && expected !== '') {
          const v = String(val || '').trim().toUpperCase();
          if (v !== expected) {
            try { mInput && mInput.focus(); } catch (_) {}
            return;
          }
        }

        cleanup();
        if (expected != null && expected !== '') resolve(val);
        else resolve(true);
      };

      onBackdrop = (e) => {
        e.preventDefault();
        cleanup();
        resolve(false);
      };

      onEsc = (ev) => {
        if (ev.key !== 'Escape') return;
        ev.preventDefault();
        cleanup();
        resolve(false);
      };

      mCancel.addEventListener('click', onCancel, { once: true });
      mOk.addEventListener('click', onOk, { once: true });
      if (mBackdrop) mBackdrop.addEventListener('click', onBackdrop, { once: true });
      document.addEventListener('keydown', onEsc);
    });
  }

  return { show, hide, confirm };
}
