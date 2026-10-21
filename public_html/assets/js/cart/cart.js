// Carrito - auto update por item (sin recarga)
// Regla: no armamos HTML, solo actualizamos valores/estados existentes.

import { setCartCount } from '../ui/cart_feedback.js';

function formatMoneyARS(value) {
  try {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value);
  } catch {
    const n = Number(value) || 0;
    return '$ ' + n.toLocaleString('es-AR');
  }
}

function computeStockHint(stock, qty, requestedQty = null) {
  const s = Number(stock || 0);
  const q = Number(qty || 0);
  const rq = requestedQty === null ? q : Number(requestedQty || 0);

  if (s > 0 && rq > s) {
    return { text: 'Sin stock suficiente para la cantidad elegida.', class: 'cart-item__stock--error' };
  }
  if (s <= 0) {
    return { text: 'Sin stock para esta opcion.', class: 'cart-item__stock--error' };
  }
  if (s === 1) {
    return { text: 'Ultima unidad disponible.', class: 'cart-item__stock--warn' };
  }
  if (s <= 3) {
    return { text: 'Quedan pocas unidades para esta opcion.', class: 'cart-item__stock--warn' };
  }
  return { text: '', class: '' };
}

function setHint(el, hint, force = false) {
  if (!el) return;
  const lockUntil = Number(el.dataset.hintLockUntil || 0);
  if (!force && lockUntil && Date.now() < lockUntil) {
    return;
  }
  const classes = ['cart-item__stock--warn', 'cart-item__stock--error'];
  el.classList.remove(...classes);
  const text = (hint && hint.text) ? String(hint.text) : '';
  const cls = (hint && hint.class) ? String(hint.class) : '';
  el.textContent = text;
  if (cls) el.classList.add(cls);
}

function setLoading(row, isLoading) {
  if (!row) return;
  row.classList.toggle('site-opacity-60', isLoading);
  row.classList.toggle('site-pointer-events-none', isLoading);
}

function startRowBusy(row) {
  if (!row) return () => {};
  setLoading(row, true);
  return () => setLoading(row, false);
}

function flashStockCap(row, { ms = 900 } = {}) {
  if (!row) return;
  row.classList.add('ring-2', 'ring-red-200', 'bg-red-50');
  window.clearTimeout(row.__capT);
  row.__capT = window.setTimeout(() => {
    row.classList.remove('ring-2', 'ring-red-200', 'bg-red-50');
  }, ms);
}

function flashRow(row, { ms = 700 } = {}) {
  if (!row) return;
  row.classList.add('ring-1', 'ring-emerald-200');
  window.setTimeout(() => row.classList.remove('ring-1', 'ring-emerald-200'), ms);
}

async function postForm(url, params) {
  const body = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => body.append(k, v));

  const res = await fetch(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: body.toString(),
    credentials: 'same-origin',
  });

  const data = await res.json().catch(() => null);
  return { ok: res.ok, status: res.status, data };
}

export function init() {
  const form = document.querySelector('[data-cart-form]');
  if (!form) return;

  const csrf = form.querySelector('input[name="_csrf"]')?.value || '';
  const selectedQtyEls = document.querySelectorAll('[data-cart-selected-qty]');
  const financedSubtotalEls = document.querySelectorAll('[data-cart-financed-subtotal]');
  const transferSubtotalEls = document.querySelectorAll('[data-cart-transfer-subtotal]');
  const transferSavingsEls = document.querySelectorAll('[data-cart-transfer-savings]');
  const installmentsCopyEls = document.querySelectorAll('[data-cart-installments-copy]');
  const freeShippingRowEls = document.querySelectorAll('[data-cart-free-shipping-row]');
  const freeShippingLabelEls = document.querySelectorAll('[data-cart-free-shipping-label]');
  const selectAll = document.querySelector('[data-cart-select-all]');
  const checkoutBtn = document.querySelector('[data-cart-checkout-btn]');
  const summaryAnchor = document.querySelector('[data-cart-summary-anchor]');
  const summaryEl = document.querySelector('.cart-summary');
  const summaryRowsEls = document.querySelectorAll('[data-cart-summary-rows]');
  const selectionHelperEls = document.querySelectorAll('[data-cart-selection-helper]');

  const actionUrl = new URL(form.action, window.location.href);
  const actionPath = String(actionUrl.pathname || '');
  const idxCart = actionPath.indexOf('/carrito');
  const basePrefix = idxCart >= 0 ? actionPath.slice(0, idxCart) : '';
  const removeUrl = basePrefix + '/carrito/quitar';
  const selectionUrl = basePrefix + '/carrito/seleccion';
  const cartUrl = basePrefix + '/carrito';

  const syncMobileSummary = () => {
    if (!summaryAnchor || !summaryEl) return;

    if (window.innerWidth >= 1024) {
      summaryEl.classList.remove('cart-summary--floating');
      summaryAnchor.style.minHeight = '';
      return;
    }

    const summaryHeight = summaryEl.offsetHeight || 0;
    if (summaryHeight <= 0) return;

    const anchorRect = summaryAnchor.getBoundingClientRect();
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const shouldFloat = anchorRect.top > (viewportHeight - summaryHeight);

    summaryAnchor.style.minHeight = shouldFloat ? `${summaryHeight}px` : '';
    summaryEl.classList.toggle('cart-summary--floating', shouldFloat);
  };

  const getRows = () => Array.from(form.querySelectorAll('[data-cart-item]'));
  const getSelectableRows = () => getRows().filter((row) => {
    const input = row.querySelector('[data-cart-select-item]');
    return input && !input.disabled;
  });
  const getSelectedRows = () => getSelectableRows().filter((row) => row.querySelector('[data-cart-select-item]')?.checked);
  const getSelectedKeys = () => getSelectedRows().map((row) => String(row.getAttribute('data-key') || '')).filter(Boolean);

  const syncSelectAllState = () => {
    if (!selectAll) return;
    const rows = getSelectableRows();
    const selectedCount = getSelectedRows().length;
    selectAll.checked = rows.length > 0 && selectedCount === rows.length;
    selectAll.indeterminate = selectedCount > 0 && selectedCount < rows.length;
    selectAll.disabled = rows.length === 0;
  };

  const syncCheckoutState = (selectedCount) => {
    if (!checkoutBtn) return;
    const disabled = Number(selectedCount || 0) <= 0;
    checkoutBtn.classList.toggle('cart-summary__btn--disabled', disabled);
    checkoutBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    checkoutBtn.tabIndex = disabled ? -1 : 0;
    summaryRowsEls.forEach((el) => {
      el.classList.toggle('cart-summary__rows--hidden', disabled);
    });
    selectionHelperEls.forEach((el) => {
      el.classList.toggle('cart-summary__helper--hidden', !disabled);
    });
    syncMobileSummary();
  };

  const applySelectedCart = (selectedCart) => {
    if (!selectedCart) return;
    const totalQty = Number(selectedCart.total_qty || 0);
    const itemsCount = Number(selectedCart.items_count || 0);
    selectedQtyEls.forEach((el) => {
      el.textContent = String(totalQty);
    });
    syncCheckoutState(itemsCount);
    syncSelectAllState();
  };

  const applySelectedSummary = (summary) => {
    if (!summary) return;

    const financedSubtotal = Number(summary.financed_subtotal || 0);
    const transferSubtotal = Number(summary.transfer_subtotal || 0);
    const transferSavings = Number(summary.transfer_savings || 0);
    const installmentsCopy = String(summary.installments_copy || '');
    const showFreeShipping = Boolean(summary.show_free_shipping);
    const freeShippingLabel = String(summary.free_shipping_label || 'Gratis');

    financedSubtotalEls.forEach((el) => {
      el.textContent = formatMoneyARS(financedSubtotal);
    });
    transferSubtotalEls.forEach((el) => {
      el.textContent = formatMoneyARS(transferSubtotal);
    });
    transferSavingsEls.forEach((el) => {
      el.textContent = formatMoneyARS(transferSavings);
    });
    installmentsCopyEls.forEach((el) => {
      el.textContent = installmentsCopy;
    });
    freeShippingRowEls.forEach((el) => {
      el.classList.toggle('cart-summary__row--hidden', !showFreeShipping);
    });
    freeShippingLabelEls.forEach((el) => {
      el.textContent = freeShippingLabel;
    });
    window.requestAnimationFrame(syncMobileSummary);
  };

  const applyCartTotals = (cart) => {
    if (!cart) return;
    const totalQty = Number(cart.total_qty || 0);

    setCartCount(totalQty);
  };

  const persistSelection = async () => {
    const body = new URLSearchParams();
    body.append('_csrf', csrf);
    getSelectedKeys().forEach((key) => body.append('selected_keys[]', key));

    try {
      const res = await fetch(selectionUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
        credentials: 'same-origin',
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || !data) throw new Error('selection_failed');
      applySelectedCart(data.selected_cart || null);
      applySelectedSummary(data.selected_summary || null);
    } catch (error) {
      syncSelectAllState();
      syncCheckoutState(getSelectedRows().length);
    }
  };

  const updateOne = async (row, key, qty) => {
    const input = row.querySelector('[data-cart-qty]');
    const hintEl = row.querySelector('[data-cart-stock-msg]');
    const unitPriceEl = row.querySelector('[data-cart-unit-price]');
    const qtyUnitEl = row.querySelector('[data-cart-qty-unit]');
    const listLineEl = row.querySelector('[data-cart-line-list]');
    const transferLineEl = row.querySelector('[data-cart-line-transfer]');

    const stock = Number(row.dataset.stock || 0);
    const requestedQty = Math.max(1, Math.min(999, Number(qty) || 1));
    let safeQty = requestedQty;

    const cappedByClient = stock > 0 && requestedQty > stock;
    if (cappedByClient) {
      safeQty = stock;
      if (input) input.value = String(safeQty);
      flashStockCap(row);
      if (hintEl) {
        hintEl.dataset.hintLockUntil = String(Date.now() + 1200);
        setHint(hintEl, { text: 'Sin stock suficiente para la cantidad elegida.', class: 'cart-item__stock--error' }, true);
        const lockUntilMs = Number(hintEl.dataset.hintLockUntil || 0);
        const waitMs = Math.max(0, lockUntilMs - Date.now());
        window.setTimeout(() => {
          const againUntil = Number(hintEl.dataset.hintLockUntil || 0);
          if (againUntil && Date.now() < againUntil) return;
          const currentStock = Number(row?.dataset?.stock || stock || 0);
          const currentQty = Number(input?.value || safeQty);
          setHint(hintEl, computeStockHint(currentStock, currentQty, null), true);
        }, waitMs + 30);
      }
    }

    try {
      const res = await postForm(form.action, {
        _csrf: csrf,
        _ajax: '1',
        [`qty[${key}]`]: String(safeQty),
      });

      const data = res?.data;
      if (data && !res.ok) {
        setHint(hintEl, { text: String(data.message || 'No se pudo actualizar el carrito.'), class: 'cart-item__stock--error' }, true);
        if (data.selected_cart) {
          applySelectedCart(data.selected_cart);
        }
        if (data.selected_summary) {
          applySelectedSummary(data.selected_summary);
        }
        return;
      }

      if (data && data.item) {
        const newQty = Number(data.item.qty ?? safeQty);
        if (input) {
          input.value = String(newQty);
          input.dataset.lastOkQty = String(newQty);
        }

        if (cappedByClient || newQty !== safeQty || res?.status === 422) {
          flashStockCap(row);
        }

        applyCartTotals(data.cart || null);
        row.dataset.qty = String(newQty);
        row.dataset.lineTotal = String(Number(data.item.line_total || 0));

        if (unitPriceEl && data.item.display_financed_unit_label) {
          unitPriceEl.textContent = String(data.item.display_financed_unit_label);
        }
        if (qtyUnitEl && data.item.display_financed_unit_label) {
          qtyUnitEl.textContent = `x ${String(data.item.display_financed_unit_label)}`;
        }
        if (listLineEl && data.item.display_financed_line_label) {
          listLineEl.textContent = String(data.item.display_financed_line_label);
        }
        if (transferLineEl && data.item.display_transfer_line_label) {
          transferLineEl.textContent = String(data.item.display_transfer_line_label);
        }

        const newStock = Number(data.item.stock ?? row.dataset.stock ?? 0);
        row.dataset.stock = String(newStock);
        const hint = data.item.hint ? data.item.hint : computeStockHint(newStock, newQty, requestedQty);
        setHint(hintEl, hint);

        if (data.selected_cart) {
          applySelectedCart(data.selected_cart);
        }
        if (data.selected_summary) {
          applySelectedSummary(data.selected_summary);
        }
      }

      if (res?.ok) {
        flashRow(row);
      }
    } catch (error) {
      if (input) input.value = String(input.dataset.lastOkQty || input.value || '1');
      setHint(hintEl, { text: 'No se pudo actualizar el carrito.', class: 'cart-item__stock--error' });
    }
  };

  form.querySelectorAll('[data-cart-qty]').forEach((input) => {
    input.dataset.lastOkQty = String(input.value || '1');

    input.addEventListener('change', () => {
      const row = input.closest('[data-cart-item]');
      if (!row) return;
      const key = row.getAttribute('data-key') || '';
      if (!key) return;
      updateOne(row, key, input.value);
    });
  });

  form.querySelectorAll('[data-cart-remove]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const row = btn.closest('[data-cart-item]');
      const pid = Number(btn.getAttribute('data-product-id') || 0);
      const vidRaw = Number(btn.getAttribute('data-variant-id') || 0);
      const vid = vidRaw > 0 ? vidRaw : 0;
      if (!pid) return;

      const stopBusy = startRowBusy(row);
      try {
        const res = await postForm(removeUrl, {
          _csrf: csrf,
          _ajax: '1',
          product_id: String(pid),
          variant_id: String(vid),
        });

        if (!res?.data) throw new Error('remove_failed');
        if (!res.ok) {
          const hintEl = row?.querySelector('[data-cart-stock-msg]');
          setHint(hintEl, { text: String(res.data?.message || 'No se pudo quitar el producto.'), class: 'cart-item__stock--error' }, true);
          return;
        }

        if (row) row.remove();

        const data = res.data;
        applyCartTotals(data.cart || null);
        if (data.selected_cart) {
          applySelectedCart(data.selected_cart);
        }
        if (data.selected_summary) {
          applySelectedSummary(data.selected_summary);
        } else {
          syncSelectAllState();
          syncCheckoutState(getSelectedRows().length);
        }

        const count = Number(data.cart?.items_count ?? -1);
        if (count === 0) {
          window.location.reload();
        }
      } catch (error) {
        window.location.href = cartUrl;
      } finally {
        stopBusy();
      }
    });
  });

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      const checked = selectAll.checked;
      getSelectableRows().forEach((row) => {
        const input = row.querySelector('[data-cart-select-item]');
        if (input) input.checked = checked;
      });
      syncSelectAllState();
      persistSelection();
    });
  }

  form.querySelectorAll('[data-cart-select-item]').forEach((input) => {
    input.addEventListener('change', () => {
      syncSelectAllState();
      persistSelection();
    });
  });

  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', (event) => {
      if (checkoutBtn.getAttribute('aria-disabled') === 'true') {
        event.preventDefault();
      }
    });
  }

  document.querySelectorAll('[data-cart-auto-dismiss]').forEach((el) => {
    const ms = Math.max(0, Number(el.getAttribute('data-cart-auto-dismiss') || 0));
    if (!ms) return;
    window.setTimeout(() => {
      el.remove();
    }, ms);
  });

  document.querySelectorAll('[data-cart-overlay-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const overlay = btn.closest('[data-cart-auto-dismiss]');
      if (overlay) overlay.remove();
    });
  });

  syncSelectAllState();
  syncCheckoutState(getSelectedRows().length);
  syncMobileSummary();
  window.addEventListener('scroll', syncMobileSummary, { passive: true });
  window.addEventListener('resize', syncMobileSummary);
}
