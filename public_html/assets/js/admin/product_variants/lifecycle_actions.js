import { startAction, warnScoped, successScoped, errorScoped } from './notify.js';

export function bindLifecycleActions(ctx) {
  const {
    root,
    on,
    fb,
    enqueue,
    postForm,
    reload,
    productId,
    toInt,
    normalizeErrMessage,
  } = ctx;

  root.querySelectorAll('[data-role="vs-notice-close"]').forEach((btn) => {
    on(btn, 'click', (e) => {
      e.preventDefault();
      root.querySelectorAll('[data-role="vs-warning"], [data-role="vs-obsolete-warning"]').forEach((box) => {
        box.classList.add('av3-hidden');
      });
    });
  });

  on(root.querySelector('[data-role="vs-pause-incomplete"]'), 'click', async (e) => {
    e.preventDefault();
    const btn = e.currentTarget;
    const idsRaw = btn?.dataset?.ids || '';
    const ids = idsRaw.split(',').map((x) => toInt(x)).filter((n) => n > 0);
    const endpointToggle = root.dataset.endpointVariantToggle;
    if (!endpointToggle || ids.length === 0) return;

    const ok = await fb.confirm({
      title: 'Desactivar incompletas',
      text: 'Se van a pausar las variantes incompletas para evitar ventas con datos faltantes. Podes reactivarlas cuando las completes.',
      okText: 'Desactivar',
      tone: 'primary',
    });
    if (!ok) return;

    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Desactivando...';

    let fail = 0;
    for (const vid of ids) {
      try {
        await postForm(endpointToggle, { product_id: productId, variant_id: vid, is_active: 0 });
      } catch (err) {
        console.error(err);
        fail++;
      }
    }

    const action = startAction('pause-incomplete');
    if (fail === 0) successScoped(action, `Variantes incompletas desactivadas (${ids.length}).`);
    else warnScoped(action, 'Desactivacion parcial: algunas variantes no se pudieron desactivar.');

    btn.textContent = original;
    await reload();
  });

  on(root.querySelector('[data-role="vs-delete-incomplete"]'), 'click', async (e) => {
    e.preventDefault();
    const btn = e.currentTarget;
    if (!btn) return;

    const endpoint = root.dataset.endpointDeleteIncomplete;
    if (!endpoint) return;

    const confirmText = await fb.confirm({
      title: 'Eliminar incompletas',
      text: 'Esta accion elimina variantes incompletas.\n\nSi alguna tiene pedidos, NO se elimina: se pausa automaticamente.\n\nEs destructiva y no se puede deshacer.',
      okText: 'Eliminar',
      tone: 'danger',
      requireInput: {
        label: 'Confirmacion',
        placeholder: 'Escribi ELIMINAR',
        hint: 'Para eliminar, escribi ELIMINAR en mayusculas.',
        expected: 'ELIMINAR',
      },
    });
    if (confirmText !== 'ELIMINAR') return;

    const original = btn.textContent || 'Eliminar';
    btn.disabled = true;
    btn.textContent = 'Eliminando...';
    const action = startAction('delete-incomplete');

    await enqueue(async () => {
      try {
        const res = await postForm(endpoint, { product_id: productId, confirm: confirmText.trim() });
        const del = toInt(res?.data?.deleted ?? 0);
        const pau = toInt(res?.data?.paused ?? 0);
        if (del === 0 && pau === 0) warnScoped(action, 'No habia variantes incompletas para eliminar.');
        else {
          const extra = pau > 0 ? ' (con pedidos -> pausadas)' : '';
          successScoped(action, `Listo: eliminadas ${del}. Pausadas ${pau}${extra}.`);
        }
        await reload();
      } catch (err) {
        console.error(err);
        const msg = (err?.data && err.data.message) ? err.data.message : 'No se pudieron eliminar.';
        errorScoped(action, msg);
      } finally {
        btn.disabled = false;
        btn.textContent = original;
      }
    });
  });

  on(root.querySelector('[data-role="vs-pause-obsolete"]'), 'click', async (e) => {
    e.preventDefault();
    const endpoint = root.dataset.endpointCleanupObsolete;
    if (!endpoint) return;

    const ok = await fb.confirm({
      title: 'Pausar sobrantes',
      text: 'Se van a pausar las variantes sobrantes (ya no corresponden a los valores actuales).',
      okText: 'Pausar',
      tone: 'danger',
    });
    if (!ok) return;

    const btn = e.target.closest('button');
    const original = btn?.textContent || 'Pausar sobrantes';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Pausando...';
    }
    const action = startAction('pause-obsolete');

    await enqueue(async () => {
      try {
        const res = await postForm(endpoint, { product_id: productId, mode: 'pause' });
        successScoped(action, (res && res.message) ? res.message : 'Accion completada.');
        await reload();
      } catch (err) {
        console.error(err);
        const msg = (err?.data && err.data.message) ? err.data.message : 'No se pudieron pausar.';
        errorScoped(action, msg);
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = original;
        }
      }
    });
  });

  on(root.querySelector('[data-role="vs-delete-obsolete"]'), 'click', async (e) => {
    e.preventDefault();
    const endpoint = root.dataset.endpointCleanupObsolete;
    if (!endpoint) return;

    const confirmText = await fb.confirm({
      title: 'Eliminar sobrantes',
      text: 'Esta accion elimina variantes sobrantes SIN pedidos. Las que tengan pedidos se van a pausar. Es destructiva y no se puede deshacer.',
      okText: 'Eliminar',
      tone: 'danger',
      requireInput: {
        label: 'Confirmacion',
        placeholder: 'Escribi ELIMINAR',
        hint: 'Para eliminar, escribi ELIMINAR en mayusculas.',
        expected: 'ELIMINAR',
      },
    });
    if (confirmText !== 'ELIMINAR') return;

    const btn = e.target.closest('button');
    const original = btn?.textContent || 'Eliminar sobrantes';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Eliminando...';
    }
    const action = startAction('delete-obsolete');

    await enqueue(async () => {
      try {
        const res = await postForm(endpoint, { product_id: productId, mode: 'delete', confirm: 'ELIMINAR' });
        successScoped(action, (res && res.message) ? res.message : 'Accion completada.');
        await reload();
      } catch (err) {
        console.error(err);
        const msg = (err?.data && err.data.message) ? err.data.message : 'No se pudieron eliminar.';
        errorScoped(action, msg);
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = original;
        }
      }
    });
  });

  const endpointVariantToggle = root.dataset.endpointVariantToggle;
  const endpointVariantDelete = root.dataset.endpointVariantDelete;
  const menuHome = new WeakMap();

  const restoreMenuHome = (menu) => {
    const home = menuHome.get(menu);
    if (!home) return;
    try {
      menu.style.position = '';
      menu.style.top = '';
      menu.style.left = '';
      menu.style.right = '';
      menu.style.bottom = '';
      menu.style.zIndex = '';
      menu.style.marginTop = '';
      menu.style.width = '';
      if (home.parent) {
        if (home.nextSibling && home.nextSibling.parentNode === home.parent) {
          home.parent.insertBefore(menu, home.nextSibling);
        } else {
          home.parent.appendChild(menu);
        }
      }
    } catch (_) {}
    menuHome.delete(menu);
  };

  const closeRowMenus = () => {
    document.querySelectorAll('[data-role="vs-row-menu"]').forEach((m) => {
      m.classList.add('av3-hidden');
      restoreMenuHome(m);
    });
  };

  const openMenuAtButton = (menu, btn) => {
    if (!menuHome.has(menu)) {
      menuHome.set(menu, { parent: menu.parentNode, nextSibling: menu.nextSibling });
    }
    try {
      const row = btn.closest('[data-role="vs-row"]');
      const variantId = toInt(row?.dataset?.variantId);
      if (variantId > 0) menu.dataset.variantId = String(variantId);
    } catch (_) {}

    if (menu.parentNode !== document.body) document.body.appendChild(menu);
    menu.style.position = 'fixed';
    menu.style.zIndex = '9999';
    menu.style.marginTop = '0';
    menu.classList.remove('av3-hidden');
    menu.style.top = '-9999px';
    menu.style.left = '-9999px';

    const rect = btn.getBoundingClientRect();
    const w = menu.offsetWidth || 176;
    const gap = 6;
    let left = rect.right - w;
    let top = rect.bottom + gap;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    if (left < 8) left = 8;
    if (left + w > vw - 8) left = Math.max(8, vw - w - 8);
    const h = menu.offsetHeight || 0;
    if (h && top + h > vh - 8) {
      top = Math.max(8, rect.top - h - gap);
    }

    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
  };

  on(document, 'click', (e) => {
    const insidePortalMenu = !!e.target.closest('[data-role="vs-row-menu"]');
    if (!root.contains(e.target) && !insidePortalMenu) closeRowMenus();
  });

  const handleRowToggle = async (variantId, nextActive) => {
    if (!endpointVariantToggle || !variantId) return;
    closeRowMenus();
    const action = startAction('toggle-row');
    try {
      await postForm(endpointVariantToggle, { product_id: productId, variant_id: variantId, is_active: nextActive ? 1 : 0 });
      successScoped(action, nextActive ? 'Variante activada.' : 'Variante pausada.');
      await reload();
    } catch (err) {
      console.error(err);
      errorScoped(action, normalizeErrMessage(err, 'No se pudo actualizar la variante.'));
    }
  };

  const handleRowDelete = async (variantId, canDelete) => {
    if (!endpointVariantDelete || !variantId) return;
    if (!canDelete) {
      warnScoped(startAction('delete-row-blocked'), 'No se puede eliminar: esta variante tiene pedidos. Podes pausarla.');
      closeRowMenus();
      return;
    }

    closeRowMenus();
    const confirmText = await fb.confirm({
      title: 'Eliminar variante',
      text: 'Esta accion elimina la variante. Es destructiva y no se puede deshacer.',
      okText: 'Eliminar',
      tone: 'danger',
      requireInput: {
        label: 'Confirmacion',
        placeholder: 'Escribi ELIMINAR',
        hint: 'Para eliminar, escribi ELIMINAR en mayusculas.',
        expected: 'ELIMINAR',
      },
    });
    if (confirmText !== 'ELIMINAR') return;
    const action = startAction('delete-row');

    try {
      const res = await postForm(endpointVariantDelete, { product_id: productId, variant_id: variantId, confirm: confirmText.trim() });
      const rowAction = res?.data?.action || '';
      if (rowAction === 'paused') successScoped(action, 'No se pudo eliminar porque tiene pedidos. Se pauso la variante.');
      else successScoped(action, 'Variante eliminada.');
      await reload();
    } catch (err) {
      console.error(err);
      const msg = (err?.data && err.data.message) ? err.data.message : 'No se pudo eliminar.';
      errorScoped(action, msg);
    }
  };

  on(document, 'click', async (e) => {
    if (root.contains(e.target)) return;
    const actionToggle = e.target.closest('[data-role="vs-row-action-toggle"]');
    if (actionToggle) {
      e.preventDefault();
      e.stopPropagation();
      const menu = actionToggle.closest('[data-role="vs-row-menu"]');
      const variantId = toInt(menu?.dataset?.variantId || 0);
      const nextActive = toInt(actionToggle?.dataset?.nextActive);
      await handleRowToggle(variantId, nextActive);
      return;
    }

    const actionDelete = e.target.closest('[data-role="vs-row-action-delete"]');
    if (actionDelete) {
      e.preventDefault();
      e.stopPropagation();
      const menu = actionDelete.closest('[data-role="vs-row-menu"]');
      const variantId = toInt(menu?.dataset?.variantId || 0);
      const canDelete = (actionDelete.dataset.canDelete || '0') === '1';
      await handleRowDelete(variantId, canDelete);
      return;
    }
  });

  try {
    window.addEventListener('scroll', closeRowMenus, true);
    window.addEventListener('resize', closeRowMenus);
  } catch (_) {}

  on(root, 'click', async (e) => {
    const toggleBtn = e.target.closest('[data-role="vs-row-actions-toggle"]');
    if (toggleBtn) {
      e.preventDefault();
      e.stopPropagation();
      const row = toggleBtn.closest('[data-role="vs-row"]');
      if (!row) return;
      const menu = row.querySelector('[data-role="vs-row-menu"]');
      if (!menu) return;
      document.querySelectorAll('[data-role="vs-row-menu"]').forEach((m) => {
        if (m !== menu) m.classList.add('av3-hidden');
      });
      if (menu.classList.contains('av3-hidden')) {
        openMenuAtButton(menu, toggleBtn);
      } else {
        menu.classList.add('av3-hidden');
        restoreMenuHome(menu);
      }
      return;
    }

    const actionToggle = e.target.closest('[data-role="vs-row-action-toggle"]');
    if (actionToggle) {
      e.preventDefault();
      e.stopPropagation();
      const row = actionToggle.closest('[data-role="vs-row"]');
      const menu = actionToggle.closest('[data-role="vs-row-menu"]');
      const variantId = toInt(row?.dataset?.variantId || menu?.dataset?.variantId || 0);
      const nextActive = toInt(actionToggle?.dataset?.nextActive);
      await handleRowToggle(variantId, nextActive);
      return;
    }

    const actionDelete = e.target.closest('[data-role="vs-row-action-delete"]');
    if (actionDelete) {
      e.preventDefault();
      e.stopPropagation();
      const row = actionDelete.closest('[data-role="vs-row"]');
      const menu = actionDelete.closest('[data-role="vs-row-menu"]');
      const variantId = toInt(row?.dataset?.variantId || menu?.dataset?.variantId || 0);
      const canDelete = (actionDelete.dataset.canDelete || '0') === '1';
      await handleRowDelete(variantId, canDelete);
      return;
    }

    closeRowMenus();
  });
}
