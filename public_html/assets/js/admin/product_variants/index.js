// Admin > Productos > Variantes
// UI server-render + JS solo para comportamiento (sin construir HTML en JS).

import { appUrl } from './api.js';
import { toInt } from './validators.js';
import { initMoneyInputs } from './money.js';
import { initAttrQuickCreate } from './attr_quick.js';
import { bindSaleUI, syncGenerateWrapFromDom, ensureUnsavedGuard, syncConfigCollapsedFromStorage } from './bindings.js';
import { saveDirtyRows, getDirtyRows, syncSaveToolbar } from './save_actions.js';


export function init() {
  initVariantsPanels();
}

function initVariantsPanels() {
  const root = document.getElementById('variants-sale');
  if (!root) return;
  initVariantsPanel(root, loadSaleFragment);
}

function initVariantsPanel(root, loader) {
  if (!root) return;

  const productId = toInt(root.dataset.productId);
  if (!productId) return;

  // Wizard: si hay cambios de variantes y el usuario toca "Siguiente",
  // mostramos el modal global y guardamos variantes antes de continuar.
  const tabsRoot = document.getElementById('product-tabs-root');
  if (tabsRoot && !tabsRoot.dataset.vsUnsavedHooked) {
    tabsRoot.dataset.vsUnsavedHooked = '1';
    tabsRoot.addEventListener('product:wizard:before-tab-change', (ev) => {
      try {
        const d = ev.detail || {};
        if ((d.from || '') !== 'variantes') return;
        // Solo interceptamos navegación hacia adelante (ej: Variantes -> Imágenes).
        if ((d.reason || '') !== 'wizard-next') return;

        const dirtyCount = getDirtyRows(root).length;
        if (!(dirtyCount > 0)) return;

        // Frenamos el cambio de tab normal y usamos el modal global
        ev.preventDefault();

        if (typeof tabsRoot.__openUnsavedModal !== 'function') {
          // fallback: si por algún motivo no está el modal global, guardamos y seguimos
          saveDirtyRows(root, productId).then(() => {
            if (typeof tabsRoot.__showProductTab === 'function') tabsRoot.__showProductTab(d.to);
          });
          return;
        }

        tabsRoot.__openUnsavedModal(d.to, {
          message: 'Tenés cambios en Variantes. Guardalos antes de continuar.',
          onSave: async () => {
            await saveDirtyRows(root, productId);
            return true;
          }
        });
      } catch (err) {
        console.error(err);
      }
    });
  }

  // Atributo rápido: inicializar también sobre el DOM ya renderizado (por si no recargamos fragmento)
  try { initAttrQuickCreate(root, productId); } catch (_) {}
  loader(root, productId);
  ensureUnsavedGuard();
}

async function loadSaleFragment(root, productId) {
  const endpointFragment = root.dataset.endpointFragment;
  if (!endpointFragment) return;

  // Anti-cache: después de acciones destructivas (pausar/eliminar) queremos SIEMPRE HTML fresco.
  const url = appUrl(endpointFragment + (endpointFragment.includes('?') ? '&' : '?') + '_t=' + Date.now());

  try {
    const res = await fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store',
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const html = await res.text();
    root.innerHTML = html;

    // Selección (checkboxes) en modo borrador: sin POST por click.
    // Al cargar desde servidor, no hay cambios pendientes.
    root.dataset.vsSelectionDirty = "0";

    // Aplica estado colapsado guardado (si existe)
    syncConfigCollapsedFromStorage(root, productId);

    // Re-bindea todo el comportamiento
    bindSaleUI(root, productId, loadSaleFragment);

    // Formato de dinero ($ + miles) sin ensuciar el payload
    initMoneyInputs(root);

    // Estado inicial (botón generar + guardar)
    syncGenerateWrapFromDom(root);
    syncSaveToolbar(root);
  } catch (err) {
    console.error('No se pudo cargar el fragmento de variantes.', err);
    root.innerHTML = '<div class="av3-type-body av3-text-red-700">No se pudieron cargar las variantes.</div>';
  }
}
