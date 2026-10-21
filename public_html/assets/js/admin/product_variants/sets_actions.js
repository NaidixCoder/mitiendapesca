import { postForm } from './api.js';
import { toastSafe } from './feedback.js';
import { normalizeErrMessage } from './errors.js';
import { syncCombinationWarnings } from './selection.js';
import { startAction, successScoped, errorScoped } from './notify.js';

export function bindSetsActions(ctx) {
  const {
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    setBusy,
    syncGenerateWrapFromDom,
  } = ctx;

  const setsModal = root.querySelector('[data-role="vs-sets-modal"]');
  const openSetsBtn = root.querySelector('[data-role="vs-set-import-open"]');
  const endpointSetsImport = root.dataset.endpointSetsImport || '';
  const openSets = () => {
    if (!setsModal) return;
    setsModal.classList.remove('av3-hidden');
    setsModal.setAttribute('aria-hidden', 'false');
  };
  const closeSets = () => {
    if (!setsModal) return;
    setsModal.classList.add('av3-hidden');
    setsModal.setAttribute('aria-hidden', 'true');
  };

  on(openSetsBtn, 'click', (e) => {
    e.preventDefault();
    if (!openSetsBtn || openSetsBtn.disabled) return;
    openSets();
  });
  on(setsModal && setsModal.querySelector('[data-role="vs-sets-backdrop"]'), 'click', (e) => {
    e.preventDefault();
    closeSets();
  });
  on(setsModal && setsModal.querySelector('[data-role="vs-sets-close"]'), 'click', (e) => {
    e.preventDefault();
    closeSets();
  });
  on(setsModal && setsModal.querySelector('[data-role="vs-sets-close-2"]'), 'click', (e) => {
    e.preventDefault();
    closeSets();
  });

  on(setsModal, 'click', async (e) => {
    const btn = e.target.closest?.('[data-role="vs-set-import"]');
    if (!btn) return;
    e.preventDefault();
    if (!endpointSetsImport) {
      toastSafe('error', 'Accion no disponible: falta endpoint de importacion.');
      return;
    }
    const setId = Number(btn.getAttribute('data-set-id') || '0');
    if (!setId) return;

    const ok = await fb.confirm({
      title: 'Importar plantilla',
      text: 'Se copiaran atributos y valores al producto. No se borrara lo existente.',
      okText: 'Importar',
      tone: 'danger',
    });
    if (!ok) return;
    const action = startAction('set-import');

    await enqueue(async () => {
      try {
        setBusy(btn, true);
        const res = await postForm(endpointSetsImport, { product_id: productId, set_id: setId });
        if (res?.ok) {
          const msg = res.message || 'Plantilla importada.';
          closeSets();
          await reload();
          successScoped(action, msg);
          root.dataset.vsSelectionDirty = '1';
          syncGenerateWrapFromDom(root);
          syncCombinationWarnings(root, fb);
        } else {
          errorScoped(action, res?.message || 'No se pudo importar la plantilla.');
        }
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, 'No se pudo importar la plantilla.'));
      } finally {
        setBusy(btn, false);
      }
    });
  });

  const saveModal = root.querySelector('[data-role="vs-sets-save-modal"]');
  const openSaveBtn = root.querySelector('[data-role="vs-set-save-open"]');
  const endpointSetsCreate = root.dataset.endpointSetsCreate || '';
  const openSave = () => {
    if (!saveModal) return;
    try {
      const inp = saveModal.querySelector('[data-role="vs-set-save-name"]');
      if (inp) inp.value = '';
    } catch (_) {}
    saveModal.classList.remove('av3-hidden');
    saveModal.setAttribute('aria-hidden', 'false');
  };
  const closeSave = () => {
    if (!saveModal) return;
    saveModal.classList.add('av3-hidden');
    saveModal.setAttribute('aria-hidden', 'true');
  };

  on(openSaveBtn, 'click', (e) => {
    e.preventDefault();
    if (!openSaveBtn || openSaveBtn.disabled) return;
    openSave();
  });
  on(saveModal && saveModal.querySelector('[data-role="vs-sets-save-backdrop"]'), 'click', (e) => {
    e.preventDefault();
    closeSave();
  });
  on(saveModal && saveModal.querySelector('[data-role="vs-sets-save-close"]'), 'click', (e) => {
    e.preventDefault();
    closeSave();
  });
  on(saveModal && saveModal.querySelector('[data-role="vs-sets-save-cancel"]'), 'click', (e) => {
    e.preventDefault();
    closeSave();
  });

  on(saveModal && saveModal.querySelector('[data-role="vs-sets-save-submit"]'), 'click', async (e) => {
    e.preventDefault();
    if (!endpointSetsCreate) {
      toastSafe('error', 'Accion no disponible: falta endpoint para guardar plantillas.');
      return;
    }
    const nameEl = saveModal.querySelector('[data-role="vs-set-save-name"]');
    const name = (nameEl?.value || '').trim();
    if (!name) {
      toastSafe('warn', 'Nombre requerido: escribi un nombre para la plantilla.');
      try { nameEl && nameEl.focus(); } catch (_) {}
      return;
    }

    const prevName = name;
    closeSave();
    const ok = await fb.confirm({
      title: 'Guardar como plantilla',
      text: 'Se guardara para Categoria + Marca usando la seleccion actual de atributos y valores.',
      okText: 'Guardar',
      tone: 'primary',
    });
    if (!ok) {
      openSave();
      try {
        const inp = saveModal.querySelector('[data-role="vs-set-save-name"]');
        if (inp) inp.value = prevName;
        inp && inp.focus();
      } catch (_) {}
      return;
    }
    const action = startAction('set-save');

    const btn = saveModal.querySelector('[data-role="vs-sets-save-submit"]');
    await enqueue(async () => {
      try {
        setBusy(btn, true);
        const res = await postForm(endpointSetsCreate, { product_id: productId, name });
        if (res?.ok) {
          const msg = res.message || 'Plantilla guardada.';
          closeSave();
          await reload();
          successScoped(action, msg);
          syncGenerateWrapFromDom(root);
          syncCombinationWarnings(root, fb);
        } else {
          errorScoped(action, res?.message || 'No se pudo guardar la plantilla.');
        }
      } catch (err) {
        errorScoped(action, normalizeErrMessage(err, 'No se pudo guardar la plantilla.'));
      } finally {
        setBusy(btn, false);
      }
    });
  });
}
