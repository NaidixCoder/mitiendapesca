import { postForm } from './api.js';
import { normalizeErrMessage } from './errors.js';
import { syncCombinationWarnings, computeCombinationCount, collectSelections } from './selection.js';
import { startAction, successScoped, errorScoped } from './notify.js';

export function bindGenerationActions(ctx) {
  const {
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    syncGenerateWrapFromDom,
    setConfigCollapsedDom,
    setConfigCollapsed,
  } = ctx;

  on(root.querySelector('[data-role="vs-config-open"]'), 'click', (e) => {
    e.preventDefault();
    setConfigCollapsedDom(false);
    setConfigCollapsed(false);
    const cfg = root.querySelector('[data-role="vs-config"]');
    cfg && cfg.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
    try { root.querySelector('[data-role="vs-attr-select"]')?.focus(); } catch (_) {}
    syncGenerateWrapFromDom(root);
  });

  on(root, 'change', (e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) return;
    if (el.dataset.role !== 'vs-value') return;
    root.dataset.vsSelectionDirty = '1';
    syncGenerateWrapFromDom(root);
    syncCombinationWarnings(root, fb);
  });

  const btnGenerate = root.querySelector('[data-role="vs-generate-btn"], [data-role="vs-generate"]');
  on(btnGenerate, 'click', async (e) => {
    e.preventDefault();
    const total = computeCombinationCount(root);
    if (total > 0) {
      if (total > 150) {
        const input = await fb.confirm({
          title: 'Demasiadas combinaciones',
          text: `Vas a generar hasta ${total} variante(s). Esto puede tardar y llenar tu inventario.`,
          okText: 'Generar',
          tone: 'danger',
          requireInput: {
            label: 'Confirmacion',
            placeholder: 'Escribi GENERAR',
            hint: 'Para continuar, escribi GENERAR en mayusculas.',
            expected: 'GENERAR',
          },
        });
        if (input !== 'GENERAR') return;
      } else if (total > 60) {
        const ok = await fb.confirm({
          title: 'Confirmar generacion',
          text: `Vas a generar hasta ${total} variante(s). Quieres continuar?`,
          okText: 'Generar',
          tone: 'danger',
        });
        if (!ok) return;
      }
    }

    const btn = e.currentTarget;
    btn.disabled = true;
    btn.textContent = 'Generando...';
    const action = startAction('generate-combinations');
    await enqueue(async () => {
      try {
        const selections = collectSelections(root);
        const res = await postForm(root.dataset.endpointGenerate, {
          product_id: productId,
          selections: JSON.stringify(selections),
          obsolete_mode: 'pause',
        });
        setConfigCollapsed(true);
        successScoped(action, (res && res.message) ? res.message : 'Combinaciones actualizadas.');
        await reload();
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, 'No se pudieron generar las combinaciones.'));
      } finally {
        btn.disabled = false;
        btn.textContent = 'Generar combinaciones';
      }
    });
  });
}
