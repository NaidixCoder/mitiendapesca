export function init() {
  const roots = document.querySelectorAll('[data-catalog-sort]');
  if (!roots.length) return;

  roots.forEach((root) => bindSort(root));
}

function bindSort(root) {
  if (!(root instanceof HTMLElement) || root.dataset.sortBound === '1') return;
  root.dataset.sortBound = '1';

  const form = root instanceof HTMLFormElement ? root : root.querySelector('form');
  const trigger = root.querySelector('[data-catalog-sort-trigger]');
  const label = root.querySelector('[data-catalog-sort-label]');
  const input = root.querySelector('[data-catalog-sort-input]');
  const popover = root.querySelector('[data-catalog-sort-popover]');
  const backdrop = root.querySelector('[data-catalog-sort-backdrop]');
  const sheet = root.querySelector('[data-catalog-sort-sheet]');
  const closeBtn = root.querySelector('[data-catalog-sort-close]');
  const options = root.querySelectorAll('[data-sort-value]');
  const mqDesktop = window.matchMedia('(min-width: 768px)');

  if (!form || !trigger || !label || !input || !popover || !backdrop || !sheet || !options.length) return;

  let isOpen = false;

  const syncActive = (value) => {
    options.forEach((option) => {
      const isActive = String(option.getAttribute('data-sort-value') || '') === value;
      option.classList.toggle('is-active', isActive);
      option.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
  };

  const close = () => {
    isOpen = false;
    trigger.setAttribute('aria-expanded', 'false');
    popover.hidden = true;
    popover.setAttribute('aria-hidden', 'true');
    backdrop.hidden = true;
    sheet.hidden = true;
    sheet.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('catalog-sort-open');
    document.body.classList.remove('catalog-sort-open');
  };

  const open = () => {
    isOpen = true;
    trigger.setAttribute('aria-expanded', 'true');
    if (mqDesktop.matches) {
      popover.hidden = false;
      popover.setAttribute('aria-hidden', 'false');
      return;
    }

    backdrop.hidden = false;
    sheet.hidden = false;
    sheet.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('catalog-sort-open');
    document.body.classList.add('catalog-sort-open');
  };

  const toggle = () => {
    if (isOpen) {
      close();
      return;
    }
    open();
  };

  const submitValue = (value, optionLabel) => {
    const nextValue = String(value || '').trim();
    if (!nextValue) return;
    input.value = nextValue;
    label.textContent = String(optionLabel || '').trim() || label.textContent || '';
    syncActive(nextValue);
    close();
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }
    form.submit();
  };

  trigger.addEventListener('click', (event) => {
    event.preventDefault();
    toggle();
  });

  options.forEach((option) => {
    option.addEventListener('click', (event) => {
      event.preventDefault();
      submitValue(
        String(option.getAttribute('data-sort-value') || ''),
        String(option.getAttribute('data-sort-label') || '')
      );
    });
  });

  closeBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    close();
  });

  backdrop.addEventListener('click', close);

  document.addEventListener('click', (event) => {
    if (!isOpen || !mqDesktop.matches) return;
    if (root.contains(event.target)) return;
    close();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen) close();
  });

  const syncMode = () => close();
  mqDesktop.addEventListener?.('change', syncMode);

  syncActive(String(input.value || ''));
  close();
}
