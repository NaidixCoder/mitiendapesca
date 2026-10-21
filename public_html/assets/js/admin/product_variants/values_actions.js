import { fetchJson, postForm } from './api.js';
import { toInt } from './validators.js';
import { toastSafe } from './feedback.js';
import { startAction, successScoped, errorScoped } from './notify.js';
import { normalizeErrMessage, parseValuesList } from './errors.js';
import { syncCombinationWarnings, getMetaFlag, countUniqueVariants } from './selection.js';

export function bindValuesActions(ctx) {
  const {
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    syncGenerateWrapFromDom,
  } = ctx;

  bindSuggestions({ root, on, productId });
  bindAttributeCrud({ root, on, fb, productId, enqueue, reload, syncGenerateWrapFromDom });
  bindValuesCrud({ root, on, fb, productId, enqueue, reload, syncGenerateWrapFromDom });
}

function bindSuggestions(ctx) {
  const { root, on, productId } = ctx;
  const endpointSuggestFrag = root.dataset.endpointValuesSuggestFragment || '';
  const suggestTimers = new Map();

  root.querySelectorAll('[data-role="vs-value-suggest-input"]').forEach((inp) => {
    on(inp, 'input', () => {
      const aid = Number(inp.getAttribute('data-attribute-id') || '0');
      if (!aid || !endpointSuggestFrag) return;

      const box = inp.closest?.('[data-role="vs-attr-box"]');
      const wrap = box?.querySelector?.(`[data-role="vs-value-suggestions"][data-attribute-id="${aid}"]`);
      if (!wrap) return;

      const q = String(inp.value || '').trim();
      if (!q) {
        wrap.innerHTML = '';
        return;
      }

      const prev = suggestTimers.get(inp);
      if (prev) window.clearTimeout(prev);
      const t = window.setTimeout(async () => {
        try {
          const url = endpointSuggestFrag + '?product_id=' + encodeURIComponent(productId) + '&attribute_id=' + encodeURIComponent(aid) + '&q=' + encodeURIComponent(q);
          const html = await fetchJson(url);
          wrap.innerHTML = String(html || '');
        } catch (_) {
          wrap.innerHTML = '';
        }
      }, 250);

      suggestTimers.set(inp, t);
    });
  });

  on(root, 'click', (e) => {
    const btn = e.target.closest?.('[data-role="vs-suggest-use"]');
    if (!btn) return;
    e.preventDefault();
    const val = String(btn.getAttribute('data-value-label') || '').trim();
    if (!val) return;

    const box = btn.closest?.('[data-role="vs-attr-box"]');
    const ta = box?.querySelector?.('[data-role="vs-value-create-input"]');
    if (!ta) return;

    const current = String(ta.value || '').trim();
    const parts = current ? current.split(/\r?\n|\s*,\s*/).map((s) => s.trim()).filter(Boolean) : [];
    const exists = parts.some((p) => p.localeCompare(val, undefined, { sensitivity: 'accent' }) === 0);
    if (!exists) ta.value = (current ? (current + '\n') : '') + val;
  });
}

function bindAttributeCrud(ctx) {
  const { root, on, fb, productId, enqueue, reload } = ctx;
  const nounSingular = 'opcion de variante';
  const nounPlural = 'opciones de variante';

  on(root.querySelector('[data-role="vs-attr-select"]'), 'change', async (e) => {
    const sel = e.currentTarget;
    const attributeId = toInt(sel.value);
    sel.value = '';
    if (!attributeId) return;
    const action = startAction('attr-add');
    await enqueue(async () => {
      try {
        await postForm(root.dataset.endpointAttrAdd, { product_id: productId, attribute_id: attributeId });
        await reload();
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, `No se pudo agregar la ${nounSingular}.`));
      }
    });
  });

  root.querySelectorAll('[data-role="vs-attr-remove"]').forEach((btn) => {
    on(btn, 'click', async (e) => {
      e.preventDefault();
      const clickedBtn = e.currentTarget;
      if (!clickedBtn) return;
      const box = clickedBtn.closest('[data-role="vs-attr-box"]');
      const attributeId = toInt(box?.dataset.attributeId);
      if (!attributeId) return;

      const hasVariants = getMetaFlag(root, 'hasVariants');
      const attributeName = (box?.dataset?.attributeName || '').trim() || 'Opcion';
      let preview = null;
      const previewEndpoint = root.dataset.endpointAttrRemovePreview;
      if (previewEndpoint) {
        try {
          const r = await postForm(previewEndpoint, { product_id: productId, attribute_id: attributeId });
          if (r && (r.ok === true || r.will_delete !== undefined || r.will_pause !== undefined)) preview = r;
          else if (r && r.data && (r.data.will_delete !== undefined || r.data.will_pause !== undefined)) preview = r.data;
        } catch (_) {}
      }

      const willDelete = toInt(preview?.will_delete ?? 0);
      const willPause = toInt(preview?.will_pause ?? 0);
      const willReset = toInt(preview?.will_reset ?? 0) === 1;
      const needsHardConfirm = hasVariants || willDelete > 0 || willPause > 0 || willReset;

      if (needsHardConfirm) {
        const lines = [];
        lines.push(`Vas a quitar la ${nounSingular}: "${attributeName}".`);
        lines.push('');
        const totalVariants = countUniqueVariants(root);
        lines.push(`• Variantes actuales: ${totalVariants}.`);
        if (willReset) {
          lines.push('⚠️ Este era el ultimo atributo: se va a limpiar toda la configuracion de variantes y vas a empezar de cero.');
          lines.push(`• Se eliminaran ${willDelete} variante(s) sin pedidos.`);
          lines.push(`• Se pausaran ${willPause} variante(s) con pedidos (se conserva el historial).`);
          lines.push('• Atributos restantes despues de quitarlo: 0.');
        } else {
          const remainingVariants = Math.max(0, totalVariants - willDelete - willPause);
          lines.push(`• Se eliminaran ${willDelete} variante(s) sin pedidos.`);
          lines.push(`• Se pausaran ${willPause} variante(s) con pedidos (se conserva el historial).`);
          lines.push(`• Quedaran ${remainingVariants} variante(s) resultantes (se colapsan duplicadas al quitar el atributo).`);
        }
        lines.push(`• Se quitara el valor de este atributo en ${totalVariants} variante(s).`);
        lines.push('');
        lines.push('Esta accion es destructiva y no se puede deshacer.');
        const ok = await fb.confirm({
          title: `Quitar ${nounSingular}: ${attributeName}`,
          text: lines.join('\n'),
          okText: 'Quitar',
          tone: 'danger',
        });
        if (!ok) return;
      } else {
        const ok = await fb.confirm({
          title: `Quitar ${nounSingular}`,
          text: `Quieres quitar "${attributeName}" de las ${nounPlural} del producto?`,
          okText: 'Quitar',
          tone: 'danger',
        });
        if (!ok) return;
      }

      const originalText = clickedBtn.textContent;
      const action = startAction('attr-remove');
      await enqueue(async () => {
        try {
          clickedBtn.disabled = true;
          clickedBtn.textContent = 'Quitando...';
          await postForm(root.dataset.endpointAttrRemove, { product_id: productId, attribute_id: attributeId });
          await reload();
          successScoped(
            action,
            willReset
              ? 'Opcion quitada. Configuracion reiniciada (sin opciones de variante).'
              : `Opcion quitada. Se quito "${attributeName}" del producto.`
          );
        } catch (err) {
          errorScoped(action, normalizeErrMessage(err, `No se pudo quitar la ${nounSingular}.`));
        } finally {
          try { clickedBtn.disabled = false; } catch (_) {}
          try { clickedBtn.textContent = originalText; } catch (_) {}
        }
      });
    });
  });
}

function bindValuesCrud(ctx) {
  const { root, on, fb, productId, enqueue, reload, syncGenerateWrapFromDom } = ctx;
  const isVariant = true;

  on(root, 'click', async (e) => {
    const btn = e.target && e.target.closest ? e.target.closest('[data-role="vs-value-create-open"], [data-role="vs-value-create-cancel"], [data-role="vs-value-create-save"]') : null;
    if (!btn) return;
    e.preventDefault();
    const box = btn.closest('[data-role="vs-attr-box"]');
    const attributeId = toInt(box?.dataset.attributeId);
    if (!attributeId) return;
    const panel = box?.querySelector('[data-role="vs-value-create"]');
    const ta = box?.querySelector('[data-role="vs-value-create-input"]');

    if (btn.dataset.role === 'vs-value-create-open') {
      panel && panel.classList.remove('av3-hidden');
      try { ta && ta.focus(); } catch (_) {}
      return;
    }
    if (btn.dataset.role === 'vs-value-create-cancel') {
      if (ta) ta.value = '';
      panel && panel.classList.add('av3-hidden');
      return;
    }
    if (btn.dataset.role !== 'vs-value-create-save') return;

    const endpoint = root.dataset.endpointValuesCreate;
    if (!endpoint) {
      toastSafe('error', 'No se pudo agregar: falta endpointValuesCreate.');
      return;
    }
    const values = parseValuesList((ta && ta.value) ? ta.value : '');
    if (!values.length) {
      toastSafe('warn', 'No hay valores para agregar.');
      return;
    }
    const action = startAction('values-create');
    await enqueue(async () => {
      try {
        btn.disabled = true;
        await postForm(endpoint, { product_id: productId, attribute_id: attributeId, values: values.join('\n') });
        await reload();
        if (isVariant) {
          root.dataset.vsSelectionDirty = '1';
          syncGenerateWrapFromDom(root);
          syncCombinationWarnings(root, fb);
        }
        successScoped(action, 'Valores agregados.');
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, 'No se pudieron agregar valores.'));
      } finally {
        try { btn.disabled = false; } catch (_) {}
      }
    });
  });

  on(root, 'click', async (e) => {
    const targetEl = (e.target instanceof Element) ? e.target : (e.target && e.target.parentElement ? e.target.parentElement : null);
    const btn = targetEl && targetEl.closest ? targetEl.closest('[data-role="vs-value-remove"]') : null;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const attributeId = toInt(btn.dataset.attributeId);
    const valueId = toInt(btn.dataset.valueId);
    const valueLabel = String(btn.dataset.valueLabel || '').trim();
    if (!attributeId || !valueId) return;
    const box = btn.closest('[data-role="vs-attr-box"]');
    const selectedIds = box
      ? Array.from(box.querySelectorAll('input[data-role="vs-value"]:checked'))
          .map((el) => toInt(el.value))
          .filter((id) => id > 0 && id !== valueId)
      : [];
    const action = startAction('value-remove');
    await enqueue(async () => {
      try {
        btn.disabled = true;
        await postForm(root.dataset.endpointValuesSet, {
          product_id: productId,
          attribute_id: attributeId,
          value_id: valueId,
          selected: 0,
          value_ids: selectedIds.join(','),
          prune_unused: 1,
        });
        // Refrescamos el fragmento para sincronizar tabla + advertencias de sobrantes sin F5.
        await reload();
        successScoped(action, valueLabel ? `Se quito "${valueLabel}" de este producto.` : 'Se quito el valor de este producto.');
        if (isVariant) {
          // Mantener la accion pendiente visible para que el usuario pueda actualizar combinaciones.
          root.dataset.vsSelectionDirty = '1';
          syncGenerateWrapFromDom(root);
          syncCombinationWarnings(root, fb);
        }
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, 'No se pudo quitar el valor.'));
      } finally {
        try { btn.disabled = false; } catch (_) {}
      }
    });
  });

  on(root, 'click', async (e) => {
    const targetEl = (e.target instanceof Element) ? e.target : (e.target && e.target.parentElement ? e.target.parentElement : null);
    const btn = targetEl && targetEl.closest ? targetEl.closest('[data-role="vs-value-delete"]') : null;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const attributeId = toInt(btn.dataset.attributeId);
    const valueId = toInt(btn.dataset.valueId);
    const valueLabel = String(btn.dataset.valueLabel || '').trim();
    if (!attributeId || !valueId) return;
    const endpoint = root.dataset.endpointValueDeleteJson;
    if (!endpoint) {
      toastSafe('error', 'No se pudo eliminar: falta endpointValueDeleteJson.');
      return;
    }
    const confirmVal = await fb.confirm({
      title: 'Eliminar valor del catalogo',
      text: `Vas a eliminar "${valueLabel || 'este valor'}" del catalogo del atributo.\n\n• Si ya esta usado en variantes, no se podra eliminar.\n• Si solo esta seleccionado en productos, se quitara de esos productos.\n\nEsta accion no se puede deshacer.`,
      okText: 'Eliminar',
      tone: 'danger',
      requireInput: {
        expected: 'ELIMINAR',
        label: 'Escribi ELIMINAR para confirmar',
        placeholder: 'ELIMINAR',
        hint: 'Esto elimina el valor del sistema (no solo de este producto).',
      },
    });
    if (String(confirmVal || '').trim().toUpperCase() !== 'ELIMINAR') return;
    const action = startAction('value-delete');

    await enqueue(async () => {
      try {
        btn.disabled = true;
        const res = await postForm(endpoint, { attribute_id: attributeId, value_id: valueId });
        const msg = (res && res.message) ? String(res.message) : 'Valor eliminado del catalogo.';
        await reload();
        successScoped(action, msg);
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, 'No se pudo eliminar el valor.'));
      } finally {
        try { btn.disabled = false; } catch (_) {}
      }
    });
  });
}
