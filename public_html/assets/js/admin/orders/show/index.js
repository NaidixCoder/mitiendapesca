import { pushToast } from '../../../core/flash.js';
import { confirmModal } from '../../core/confirm_modal.js';
import { showProcessing, showProcessingResult, hideProcessing } from '../../core/processing_overlay.js';
import { bindCopyButtons } from './copy.js';
import { bindFiscalRequestRevalidate, bindFiscalEmitFacturaA } from './fiscal.js';
import { bindShippingForm, bindCorreoTrack, bindCorreoImport, bindMarkPaid } from './shipping.js';
import { bindArcaActions } from './arca.js';

function csrfToken() {
  const el = document.querySelector('meta[name="csrf-token"]');
  return el ? (el.getAttribute('content') || '') : '';
}

async function postForm(url, form) {
  const fd = new FormData(form);
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': csrfToken(),
    },
    body: fd,
    credentials: 'same-origin',
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data && data.message ? data.message : 'No se pudo completar la acción.';
    throw new Error(msg);
  }
  return data;
}

function debugEnabled() {
  try {
    const qs = new URLSearchParams(window.location.search || '');
    if (qs.get('debug') === '1') return true;
    return window.localStorage && window.localStorage.getItem('tp_debug_orders') === '1';
  } catch (e) { return false; }
}

function setDebugLine(text) {
  const el = document.getElementById('order-status-debug');
  if (!el) return;
  if (!debugEnabled()) { el.classList.add('av3-hidden'); return; }
  el.textContent = text;
  el.classList.remove('av3-hidden');
}

function extractOptionValuesFromHtml(optionsHtml) {
  try {
    const tmp = document.createElement('select');
    tmp.innerHTML = String(optionsHtml || '');
    return Array.from(tmp.querySelectorAll('option')).map(o => o.value);
  } catch (e) { return []; }
}

function getArcaCtx() {
  const el = document.getElementById('arca-box-inner');
  const ctx = {
    autoEmit: false,
    trigger: 'paid',
    hasInvoice: false,
    invoiceStatus: '',
    hasCreditNote: false,
    orderStatus: '',
    invoiceLabel: 'Factura',
    creditLabel: 'Nota de Crédito',
  };
  if (!el) return ctx;
  ctx.autoEmit = String(el.dataset.arcaAutoEmit || '0') === '1';
  ctx.trigger = String(el.dataset.arcaTrigger || 'paid');
  ctx.hasInvoice = String(el.dataset.hasInvoice || '0') === '1';
  ctx.invoiceStatus = String(el.dataset.invoiceStatus || '');
  ctx.hasCreditNote = String(el.dataset.hasCreditNote || '0') === '1';
  ctx.orderStatus = String(el.dataset.orderStatus || '');
  ctx.invoiceLabel = String(el.dataset.invoiceLabel || 'Factura');
  ctx.creditLabel = String(el.dataset.creditLabel || 'Nota de Crédito');
  return ctx;
}

function isUnpaid(orderStatus) {
  return ['pending','pending_payment','payment_failed','payment_expired'].includes(String(orderStatus || ''));
}

function updateShippingOptions(unpaid) {
  const sel = document.querySelector('select[name="shipping_status"]');
  if (!sel) return;

  const advanced = new Set(['despachado','en_transito','en_sucursal','entregado']);
  [...sel.options].forEach((opt) => {
    if (advanced.has(opt.value)) opt.disabled = !!unpaid;
  });

  const warn = document.getElementById('shipping-unpaid-warn');
  const tip  = document.getElementById('shipping-paid-tip');
  if (warn) warn.classList.toggle('av3-hidden', !unpaid);
  if (tip)  tip.classList.toggle('av3-hidden', unpaid);
}

async function postJSON(url, payload) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': csrfToken(),
    },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data && data.message ? data.message : 'No se pudo completar la acción.';
    throw new Error(msg);
  }
  return data;
}

function replaceArcaBox(arcaHtml) {
  if (!arcaHtml) return;
  const box = document.getElementById('arca-box');
  if (!box) return;
  box.innerHTML = arcaHtml;
  // Al reemplazar HTML, hay que re-bindear acciones.
  bindArcaActions({
    postJSON,
    pushToast,
    confirmModal,
    showProcessing,
    hideProcessing,
    getArcaCtx,
    replaceArcaBox,
    showArcaInline,
  });
}

function showOrderInline(type, text) {
  const wrap = document.getElementById('order-inline');
  if (!wrap) return;
  wrap.textContent = String(text || '');
  wrap.classList.remove('av3-hidden');
  wrap.classList.remove('av3-border-emerald-200','av3-bg-emerald-50','av3-text-emerald-800','av3-border-amber-200','av3-bg-amber-50','av3-text-amber-800','av3-border-rose-200','av3-bg-rose-50','av3-text-rose-800','av3-border-slate-200','av3-bg-slate-50','av3-text-slate-800');
  if (type === 'ok') wrap.classList.add('av3-border-emerald-200','av3-bg-emerald-50','av3-text-emerald-800');
  else if (type === 'warn') wrap.classList.add('av3-border-amber-200','av3-bg-amber-50','av3-text-amber-800');
  else if (type === 'error') wrap.classList.add('av3-border-rose-200','av3-bg-rose-50','av3-text-rose-800');
  else wrap.classList.add('av3-border-slate-200','av3-bg-slate-50','av3-text-slate-800');
}

function showArcaInline(type, text) {
  const wrap = document.querySelector('#arca-box #arca-inline');
  if (!wrap) return;
  wrap.textContent = String(text || '');
  wrap.classList.remove('av3-hidden');

  // clases simples (sin depender de tokens globales)
  wrap.classList.remove('av3-border-emerald-200','av3-bg-emerald-50','av3-text-emerald-800','av3-border-amber-200','av3-bg-amber-50','av3-text-amber-800','av3-border-rose-200','av3-bg-rose-50','av3-text-rose-800','av3-border-slate-200','av3-bg-slate-50','av3-text-slate-800');
  if (type === 'ok') wrap.classList.add('av3-border-emerald-200','av3-bg-emerald-50','av3-text-emerald-800');
  else if (type === 'warn') wrap.classList.add('av3-border-amber-200','av3-bg-amber-50','av3-text-amber-800');
  else if (type === 'error') wrap.classList.add('av3-border-rose-200','av3-bg-rose-50','av3-text-rose-800');
  else wrap.classList.add('av3-border-slate-200','av3-bg-slate-50','av3-text-slate-800');
}

function bindOrderStatus() {
  const sel = document.getElementById('order-status');
  const badge = document.getElementById('status-badge');
  const applyBtn = document.getElementById('order-status-apply');
  const orderIdEl = document.getElementById('order-id');
  const orderId = orderIdEl ? parseInt(orderIdEl.value, 10) : 0;

  if (!sel || !badge || !applyBtn || !orderId) return;

  let current = sel.dataset.current || sel.value;

  function syncApplyState() {
    const next = sel.value;
    applyBtn.disabled = !next || next === current;
  }

  // solo habilita el botón; NO dispara la acción automáticamente
  sel.addEventListener('change', () => {
    syncApplyState();
  });

  applyBtn.addEventListener('click', async () => {
    const next = sel.value;
    if (!next || next === current) {
      syncApplyState();
      return;
    }

    // Modal inteligente (según estado/config real) para evitar ofrecer emisión cuando no aplica.
    const ctx = getArcaCtx();
    const prev = current;
    const paidLike = ['paid','processing','packed','shipped','delivered'];

    let skipArcaAuto = false;
    let skipArcaNc = false;
    let showProc = false;
    let procMeta = null;

    // --- Enviado (trigger shipped) ---
    if (next === 'shipped') {
      const hasAnyComprobante = ctx.invoiceStatus && ctx.invoiceStatus !== '';
      const canOfferEmit = ctx.autoEmit && ctx.trigger === 'shipped' && paidLike.includes(prev) && !ctx.hasInvoice && !hasAnyComprobante;

      if (canOfferEmit) {
        const ans = await confirmModal({
          title: 'Confirmar despacho',
          subtitle: 'Se puede emitir comprobante',
          body: `Vas a marcar el pedido como “Enviado”.

Se emitirá: ${ctx.invoiceLabel}.

Según tus Ajustes (trigger: Enviado), este paso puede emitir automáticamente el comprobante.

Elegí una opción:`,
          okText: 'Aplicar y emitir (' + ctx.invoiceLabel + ')',
          altText: 'Solo cambiar estado',
          cancelText: 'Cancelar',
        });
        if (ans === 'cancel') return;
        if (ans === 'alt') skipArcaAuto = true;
        if (ans === 'ok') {
          showProc = true;
          procMeta = { title: 'Procesando emisión…', text: 'Emitiendo ' + ctx.invoiceLabel + ' en ARCA. No cierres esta ventana.' };
        }
      } else {
        const reason = (!paidLike.includes(prev))
          ? ('Este pedido no figura como pagado (estado actual: “' + prev + '”).')
          : (ctx.hasInvoice ? 'Este pedido ya tiene factura emitida.' : (hasAnyComprobante ? ('Ya existe un comprobante en estado “' + (ctx.invoiceStatus || 'N/D') + '”.') : ''));
        const ans = await confirmModal({
          title: 'Confirmar cambio de estado',
          subtitle: 'Despacho',
          body: 'Se actualizará el estado del pedido a “Enviado”.\n\nNo se emitirá: ' + ctx.invoiceLabel + ' en este paso. ' + (reason ? ('\n\nMotivo: ' + reason) : ''),
          okText: 'Aplicar cambios',
          altText: null,
          cancelText: 'Cancelar',
        });
        if (ans !== 'ok') return;
        // no overlay: solo cambio de estado
      }
    }

    // --- Estados que pueden disparar NC ---
    if (['cancelled','refunded','chargeback'].includes(next)) {
      const canOfferNc = ctx.hasInvoice && !ctx.hasCreditNote;
      if (canOfferNc) {
        const ans = await confirmModal({
          title: 'Confirmar cambio de estado',
          subtitle: 'Se puede emitir comprobante',
          body: `Este estado puede generar un comprobante de anulación.

Se emitirá: ${ctx.creditLabel}.

¿Qué querés hacer?`,
          okText: 'Aplicar y emitir (' + ctx.creditLabel + ')',
          altText: 'Solo cambiar estado',
          cancelText: 'Cancelar',
        });
        if (ans === 'cancel') return;
        if (ans === 'alt') skipArcaNc = true;
        if (ans === 'ok') {
          showProc = true;
          procMeta = { title: 'Procesando emisión…', text: 'Emitiendo ' + ctx.creditLabel + ' en ARCA. No cierres esta ventana.' };
        }
      } else {
        const reason = (!ctx.hasInvoice)
          ? 'No hay factura emitida asociada a este pedido.'
          : (ctx.hasCreditNote ? 'Este pedido ya tiene Nota de Crédito emitida.' : '');
        const ans = await confirmModal({
          title: 'Confirmar cambio de estado',
          subtitle: 'Operación',
          body: 'Se actualizará el estado del pedido.\n\nNo se emitirá: ' + ctx.creditLabel + ' en este paso. ' + (reason ? ('\n\nMotivo: ' + reason) : ''),
          okText: 'Aplicar cambios',
          altText: null,
          cancelText: 'Cancelar',
        });
        if (ans !== 'ok') return;
      }
    }
    if (!showProc) {
      showProc = true;
      procMeta = { title: 'Aplicando cambios…', text: 'Actualizando el estado del pedido. Un momento…' };
    }

sel.disabled = true;
    applyBtn.disabled = true;

    try {
      if (showProc && procMeta) showProcessing({ title: procMeta.title, text: procMeta.text });

      const data = await postJSON(window.__APP_BASE_URL + '/admin/orders/status', {
        id: orderId,
        status: next,
        ...(skipArcaAuto ? { skip_arca_auto: true } : {}),
        ...(skipArcaNc ? { skip_arca_nc: true } : {}),
      });
      const r = data.result || {};
      current = r.next || next;
      sel.dataset.current = current;

      if (data.status_options_html) {
        sel.innerHTML = String(data.status_options_html);
        sel.value = current;
      }

      // debug (solo dev): estado + opciones
      if (debugEnabled()) {
        const optVals = extractOptionValuesFromHtml(data.status_options_html || '');
        const from = 'AJAX';
        const arca = data.arca_html ? 'arca_html:1' : 'arca_html:0';
        setDebugLine(`current=${current} | options=[${optVals.join(',')}] | ${from} | ${arca}`);
      }

      // actualizar badge
      if (r.badge_class) badge.className = r.badge_class;
      badge.textContent = r.next_label || current;

      // coherencia shipping
      updateShippingOptions(isUnpaid(current));

      // ARCA: Nota de Crédito automática (best-effort)
      if (data.arca_nc) {
        const nc = data.arca_nc;
        if (nc.ok && !nc.skipped) {
          pushToast({ type: 'ok', text: nc.msg || 'Nota de Crédito emitida.' });
        } else if (!nc.ok) {
          pushToast({ type: 'warn', text: (nc.msg ? ('NC: ' + nc.msg) : 'No se pudo emitir la Nota de Crédito.') });
        }
      }

      // ARCA: auto-emisión al despachar (best-effort)
      if (data.arca_auto) {
        const a = data.arca_auto;
        if (a.ok && !a.skipped) {
          pushToast({ type: 'ok', text: a.msg || 'Factura emitida.' });
        } else if (a.ok && a.skipped) {
          pushToast({ type: 'warn', text: a.msg || 'Auto-emisión omitida.' });
        } else if (!a.ok) {
          pushToast({ type: 'warn', text: a.msg ? ('ARCA: ' + a.msg) : 'No se pudo auto-emitir.' });
        }
      }

      // Rehidratar bloque ARCA sin F5
      if (data.arca_html) {
        replaceArcaBox(data.arca_html);
      }

      pushToast({ type: 'ok', text: 'Estado del pedido actualizado.' });
      // Actualizar bloque Pago (texto + botón) sin recargar
      const payText = document.getElementById('payment-status-text');
      if (payText && r.next_label) payText.textContent = r.next_label;
      const markPaidWrap = document.getElementById('mark-paid-wrap');
      if (markPaidWrap && current !== 'pending_payment') markPaidWrap.classList.add('av3-hidden');

      // Fallback inline: el estado del pedido se comunica arriba (no dentro de ARCA)
      showOrderInline('ok', 'Estado del pedido actualizado.');

      // Si hubo operación ARCA, mostramos fallback dentro del bloque ARCA
      const arcaMsgParts = [];
      if (data.arca_auto) {
        const a = data.arca_auto;
        if (a.ok && !a.skipped) arcaMsgParts.push(a.msg || 'Factura emitida.');
        else if (a.ok && a.skipped) arcaMsgParts.push(a.msg || 'Auto-emisión omitida.');
        else if (!a.ok) arcaMsgParts.push(a.msg ? ('ARCA: ' + a.msg) : 'No se pudo auto-emitir.');
      }
      if (data.arca_nc) {
        const nc = data.arca_nc;
        if (nc.ok && !nc.skipped) arcaMsgParts.push(nc.msg || 'Nota de Crédito emitida.');
        else if (!nc.ok) arcaMsgParts.push(nc.msg ? ('NC: ' + nc.msg) : 'No se pudo emitir la Nota de Crédito.');
      }
      if (arcaMsgParts.length) {
        showArcaInline('ok', arcaMsgParts.join(' · '));
      }
    } catch (e) {
      // revertir
      sel.value = current;
      pushToast({ type: 'error', text: e.message || 'No se pudo actualizar el estado.' });
      showOrderInline('error', e.message || 'No se pudo actualizar el estado.');
    } finally {
      if (showProc) hideProcessing();

      sel.disabled = false;
      syncApplyState();
    }
  });

  // estado inicial
  syncApplyState();
}

export function init() {
  // base url global (head.php ya tiene meta canonical; guardamos base para usar rutas sin hardcode)
  const metaBase = document.querySelector('meta[name="app-base-url"]');
  window.__APP_BASE_URL = metaBase ? (metaBase.getAttribute('content') || '') : '';

  // init bindings
  bindOrderStatus();
  bindCopyButtons(pushToast);
  bindShippingForm({ postJSON, pushToast });
  bindCorreoTrack({ postJSON, pushToast, showProcessing, hideProcessing, showProcessingResult });
  bindCorreoImport({ postJSON, pushToast, confirmModal, showProcessing, hideProcessing, showProcessingResult });
  bindMarkPaid({ postJSON, pushToast, confirmModal, updateShippingOptions });
  bindArcaActions({
    postJSON,
    pushToast,
    confirmModal,
    showProcessing,
    hideProcessing,
    getArcaCtx,
    replaceArcaBox,
    showArcaInline,
  });
  bindFiscalRequestRevalidate({ postForm, showProcessing, showProcessingResult, hideProcessing, pushToast });
  bindFiscalEmitFacturaA({ postForm, showProcessing, showProcessingResult, hideProcessing, pushToast });

  // initial coherence on load
  const sel = document.getElementById('order-status');
  updateShippingOptions(isUnpaid(sel ? (sel.dataset.current || sel.value) : ''));

  // debug initial
  if (debugEnabled() && sel) {
    const optVals = Array.from(sel.querySelectorAll('option')).map(o => o.value);
    const current = sel.dataset.current || sel.value;
    setDebugLine(`current=${current} | options=[${optVals.join(',')}] | LOAD`);
  }
}

// compat mínima temporal
export const initOrdersShow = init;


