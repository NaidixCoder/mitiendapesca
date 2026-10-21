/**
 * Admin > Productos > Tab SEO (Lote 7 PRO MAX)
 * - Agregar / quitar filas Bullets/Specs/FAQ
 * - Reordenar por drag&drop (solo con handle ⋮⋮)
 * - Vista previa en vivo (sin guardar)
 */

export function init() {
  const root = document.querySelector('[data-seo-blocks]');
  if (!root) return;

  const typeToList = {
    bullet: 'bullets',
    spec: 'specs',
    faq: 'faq',
  };

  function getTemplate(type) {
    const tpl = root.querySelector(`[data-seo-template="${type}"]`);
    if (!tpl) return null;
    return tpl.content ? tpl.content.firstElementChild : null;
  }

  function ensureAtLeastOneRow(listName, templateType) {
    const list = root.querySelector(`[data-seo-list="${listName}"]`);
    if (!list) return;

    const rows = list.querySelectorAll('[data-seo-row]');
    if (rows.length > 0) return;

    const tpl = getTemplate(templateType);
    if (!tpl) return;
    list.appendChild(tpl.cloneNode(true));
  }

  // Reordenamiento táctil y por teclado: conserva el orden real de los campos.
  function ensureReorderActions() {
    root.querySelectorAll('[data-seo-list] [data-seo-row]').forEach((row) => {
      if (row.querySelector('[data-seo-mobile-moves]')) return;
      const actions = document.createElement('div');
      actions.className = 'av3-seo-mobile-moves';
      actions.setAttribute('data-seo-mobile-moves', '');
      for (const [direction, label, symbol] of [['up', 'Subir', '↑'], ['down', 'Bajar', '↓']]) {
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('data-seo-move', direction);
        button.setAttribute('aria-label', label + ' este elemento');
        button.textContent = symbol + ' ' + label;
        actions.appendChild(button);
      }
      row.appendChild(actions);
    });
  }

  function moveRow(row, direction) {
    const target = direction === 'up' ? row?.previousElementSibling : row?.nextElementSibling;
    if (!row || !target || !target.matches('[data-seo-row]')) return;
    if (direction === 'up') target.before(row);
    else target.after(row);
    updatePreview();
  }

  // ---------- Live preview ----------
  const previewRoot = root.querySelector('[data-seo-live-preview]');
  const preview = previewRoot ? {
    bulletsBox: previewRoot.querySelector('[data-seo-preview-bullets]'),
    bulletsList: previewRoot.querySelector('[data-seo-preview-bullets-list]'),
    specsBox: previewRoot.querySelector('[data-seo-preview-specs]'),
    specsBody: previewRoot.querySelector('[data-seo-preview-specs-body]'),
    faqBox: previewRoot.querySelector('[data-seo-preview-faq]'),
    faqList: previewRoot.querySelector('[data-seo-preview-faq-list]'),
    empty: previewRoot.querySelector('[data-seo-preview-empty]'),
  } : null;

  function readBullets() {
    const list = root.querySelector('[data-seo-list="bullets"]');
    if (!list) return [];
    return Array.from(list.querySelectorAll('input[name="seo_bullets[]"]'))
      .map(i => (i.value || '').trim())
      .filter(Boolean);
  }

  function readSpecs() {
    const list = root.querySelector('[data-seo-list="specs"]');
    if (!list) return [];
    const labels = Array.from(list.querySelectorAll('input[name="seo_specs_label[]"]'));
    const values = Array.from(list.querySelectorAll('input[name="seo_specs_value[]"]'));
    const out = [];
    const n = Math.max(labels.length, values.length);
    for (let i = 0; i < n; i++) {
      const l = (labels[i]?.value || '').trim();
      const v = (values[i]?.value || '').trim();
      if (l && v) out.push({ l, v });
    }
    return out;
  }

  function readFaq() {
    const list = root.querySelector('[data-seo-list="faq"]');
    if (!list) return [];
    const qs = Array.from(list.querySelectorAll('input[name="seo_faq_q[]"]'));
    const as = Array.from(list.querySelectorAll('textarea[name="seo_faq_a[]"]'));
    const out = [];
    const n = Math.max(qs.length, as.length);
    for (let i = 0; i < n; i++) {
      const q = (qs[i]?.value || '').trim();
      const a = (as[i]?.value || '').trim();
      if (q && a) out.push({ q, a });
    }
    return out;
  }

  function updatePreview() {
    if (!preview) return;

    const bullets = readBullets();
    const specs = readSpecs();
    const faq = readFaq();

    const hasAny = bullets.length || specs.length || faq.length;

    // Bullets
    if (bullets.length) {
      preview.bulletsBox.hidden = false;
      preview.bulletsList.innerHTML = '';
      for (const b of bullets) {
        const li = document.createElement('li');
        li.textContent = '✓ ' + b;
        preview.bulletsList.appendChild(li);
      }
    } else {
      preview.bulletsBox.hidden = true;
      preview.bulletsList.innerHTML = '';
    }

    // Specs
    if (specs.length) {
      preview.specsBox.hidden = false;
      preview.specsBody.innerHTML = '';
      for (const s of specs) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="av3-w-1-2 av3-pad-x3 av3-pad-y2 av3-text-gray-600 av3-border-bottom av3-border-gray-100">${escapeHtml(s.l)}</td>
          <td class="av3-w-1-2 av3-pad-x3 av3-pad-y2 av3-text-gray-900 av3-border-bottom av3-border-gray-100">${escapeHtml(s.v)}</td>
        `;
        preview.specsBody.appendChild(tr);
      }
    } else {
      preview.specsBox.hidden = true;
      preview.specsBody.innerHTML = '';
    }

    // FAQ
    if (faq.length) {
      preview.faqBox.hidden = false;
      preview.faqList.innerHTML = '';
      for (const f of faq) {
        const d = document.createElement('details');
        d.className = 'av3-radius-card av3-border av3-border-gray-100 av3-pad-3';
        d.innerHTML = `
          <summary class="av3-cursor-pointer av3-type-body av3-weight-medium av3-text-gray-900">${escapeHtml(f.q)}</summary>
          <div class="av3-mt-2 av3-type-body av3-text-gray-700 av3-seo-preview-answer">${escapeHtml(f.a)}</div>
        `;
        preview.faqList.appendChild(d);
      }
    } else {
      preview.faqBox.hidden = true;
      preview.faqList.innerHTML = '';
    }

    preview.empty.hidden = !!hasAny;
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  // ---------- Drag & Drop sorting ----------
  let dragging = null;

  function closestRow(el) {
    return el ? el.closest?.('[data-seo-row]') : null;
  }

  function getListFromRow(row) {
    return row ? row.parentElement?.closest?.('[data-seo-list]') : null;
  }

  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('[data-seo-row]:not(.is-dragging)')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset, element: child };
      }
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
  }

  root.addEventListener('dragstart', (ev) => {
    const row = closestRow(ev.target);
    if (!row) return;

    // Solo si inicia desde handle
    const isHandle = !!ev.target.closest?.('[data-seo-handle]');
    if (!isHandle) {
      ev.preventDefault();
      return;
    }

    dragging = row;
    row.classList.add('is-dragging', 'av3-opacity-60');
    ev.dataTransfer.effectAllowed = 'move';
    try { ev.dataTransfer.setData('text/plain', ''); } catch {}
  });

  root.addEventListener('dragend', () => {
    if (!dragging) return;
    dragging.classList.remove('is-dragging', 'av3-opacity-60');
    dragging = null;
    updatePreview();
  });

  root.addEventListener('dragover', (ev) => {
    if (!dragging) return;

    const list = ev.target.closest?.('[data-seo-list]');
    if (!list) return;

    // Evitar que se pueda cruzar de una lista a otra
    const draggingList = getListFromRow(dragging);
    if (draggingList !== list) return;

    ev.preventDefault();
    const afterElement = getDragAfterElement(list, ev.clientY);
    if (afterElement == null) {
      list.appendChild(dragging);
    } else {
      list.insertBefore(dragging, afterElement);
    }
  });

  root.addEventListener('drop', (ev) => {
    if (!dragging) return;
    ev.preventDefault();
  });

  // ---------- Clicks: add / remove / move ----------
  root.addEventListener('keydown', (ev) => {
    const handle = ev.target.closest?.('[data-seo-handle]');
    if (!handle || !['ArrowUp', 'ArrowDown'].includes(ev.key)) return;
    ev.preventDefault();
    const row = handle.closest('[data-seo-row]');
    moveRow(row, ev.key === 'ArrowUp' ? 'up' : 'down');
    handle.focus();
  });

  root.addEventListener('click', (ev) => {
    const moveButton = ev.target.closest('[data-seo-move]');
    if (moveButton) {
      moveRow(moveButton.closest('[data-seo-row]'), moveButton.dataset.seoMove);
      return;
    }
    const addBtn = ev.target.closest('[data-seo-add]');
    if (addBtn) {
      const type = addBtn.getAttribute('data-seo-add');
      const listName = typeToList[type] || null;
      if (!listName) return;

      const list = root.querySelector(`[data-seo-list="${listName}"]`);
      const tpl = getTemplate(type);
      if (!list || !tpl) return;

      list.appendChild(tpl.cloneNode(true));
      ensureReorderActions();
      updatePreview();
      return;
    }

    const removeBtn = ev.target.closest('[data-seo-remove]');
    if (removeBtn) {
      const row = removeBtn.closest('[data-seo-row]');
      if (row) row.remove();

      // UX: que nunca quede vacío
      ensureAtLeastOneRow('bullets', 'bullet');
      ensureAtLeastOneRow('specs', 'spec');
      ensureAtLeastOneRow('faq', 'faq');
      ensureReorderActions();
      updatePreview();
    }
  });

  // ---------- Live preview events ----------
  root.addEventListener('input', (ev) => {
    if (ev.target.matches('input[name="seo_bullets[]"], input[name="seo_specs_label[]"], input[name="seo_specs_value[]"], input[name="seo_faq_q[]"], textarea[name="seo_faq_a[]"]')) {
      updatePreview();
    }
  });

  // Bootstrap
  ensureAtLeastOneRow('bullets', 'bullet');
  ensureAtLeastOneRow('specs', 'spec');
  ensureAtLeastOneRow('faq', 'faq');
  ensureReorderActions();
  updatePreview();
}
