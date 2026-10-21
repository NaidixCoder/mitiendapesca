export function syncGenerateWrapFromDom(root) {
  const wrap = root.querySelector('[data-role="vs-generate-wrap"]');
  if (!wrap) return;

  const btn = root.querySelector('[data-role="vs-generate-btn"], [data-role="vs-generate"]');
  const countEl = root.querySelector('[data-role="vs-combo-count"]');
  const total = computeCombinationCount(root);
  const hasVariants = getMetaFlag(root, 'hasVariants');
  const dirtySel = (root.dataset.vsSelectionDirty === '1');
  const missing = getMissingSelectionNames(root);

  if (countEl) {
    if (total > 0) {
      if (hasVariants && !dirtySel) {
        countEl.textContent = `Combinaciones al dia. Para cambiar, ajusta la seleccion.`;
      } else {
        countEl.textContent = dirtySel
          ? `Cambios pendientes: se generaran hasta ${total} variante(s).`
          : `Se generaran hasta ${total} variante(s).`;
      }
    } else {
      countEl.textContent = missing.length
        ? `Selecciona al menos 1 valor en: ${missing.join(', ')}.`
        : 'Selecciona al menos 1 valor por atributo para generar.';
    }
  }

  if (btn instanceof HTMLButtonElement) {
    const canGenerate = (total > 0) && (!hasVariants || dirtySel);
    btn.disabled = !canGenerate;

    if (total > 0) {
      if (hasVariants && !dirtySel) {
        btn.textContent = 'Combinaciones al dia';
      } else {
        btn.textContent = dirtySel
          ? `Actualizar combinaciones (${total})`
          : `Generar ${total} variante(s)`;
      }
    } else {
      btn.textContent = 'Generar combinaciones';
    }

    btn.classList.toggle('av3-opacity-60', btn.disabled);
  }
}

export function getMissingSelectionNames(root) {
  const boxes = Array.from(root.querySelectorAll('[data-role="vs-attr-box"]'))
    .filter((b) => b instanceof HTMLElement)
    .map((b) => /** @type {HTMLElement} */ (b));

  if (boxes.length === 0) return [];

  const missing = [];
  boxes.forEach((box) => {
    const n = box.querySelectorAll('[data-role="vs-value"]:checked').length;
    const name = String(box.dataset.attributeName || '').trim();

    const highlightClasses = ['ring-1', 'ring-amber-300', 'av3-bg-amber-50'];
    if (n <= 0) {
      missing.push(name || 'un atributo');
      highlightClasses.forEach((c) => box.classList.add(c));
    } else {
      highlightClasses.forEach((c) => box.classList.remove(c));
    }
  });

  return Array.from(new Set(missing));
}

export function syncCombinationWarnings(root, fb) {
  const total = computeCombinationCount(root);
  if (total > 150) {
    fb.show('warn', 'Demasiadas combinaciones', `Tenes seleccionados valores que pueden generar hasta ${total} variantes. Considera reducir la seleccion.`, { scope: 'combos' });
    return;
  }
  fb.hide('combos');
}

export function computeCombinationCount(root) {
  const boxes = Array.from(root.querySelectorAll('[data-role="vs-attr-box"]'));
  if (boxes.length === 0) return 0;

  const counts = boxes.map((b) => b.querySelectorAll('[data-role="vs-value"]:checked').length);
  if (counts.some((n) => n <= 0)) return 0;

  return counts.reduce((acc, n) => acc * n, 1);
}

export function collectSelections(root) {
  const boxes = Array.from(root.querySelectorAll('[data-role="vs-attr-box"]'));
  const out = {};
  boxes.forEach((box) => {
    const attributeId = parseInt(String(box.dataset.attributeId || ''), 10) || 0;
    if (!attributeId) return;
    const ids = Array.from(box.querySelectorAll('[data-role="vs-value"]:checked'))
      .map((el) => parseInt(String(el.value || ''), 10) || 0)
      .filter((n) => n > 0);
    out[String(attributeId)] = ids;
  });
  return out;
}

export function getMetaFlag(root, key) {
  const el = root.querySelector('[data-role="vs-meta"]');
  if (!el) return false;
  const map = {
    hasVariants: el.dataset.hasVariants,
    hasIncomplete: el.dataset.hasIncomplete,
  };
  return String(map[key] ?? '0') === '1';
}

export function countUniqueVariants(root) {
  const els = root.querySelectorAll('[data-role="vs-row"][data-variant-id]');
  const set = new Set();
  els.forEach((el) => {
    const id = String(el.dataset.variantId || '').trim();
    if (id) set.add(id);
  });
  return set.size;
}
