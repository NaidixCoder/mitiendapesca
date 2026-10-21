import { triggerSaveAsTemplate, bindSaveAsTemplateFallback } from './template_actions.js';
import { insertIntoTextarea } from './html_utils.js';
import { setupPreviewModal } from './preview_modal.js';
import { setupFormValidation } from './form_validation.js';
import { setupCardBuilder } from './card_modal.js';
import { setEmailDialogOpen } from './dialog_scroll_lock.js';

// Admin → Marketing → Email → Campañas (form)
// Editor visual (sin dependencias nuevas) + vista previa real (servidor) via /preview
// Mejoras "rápidas y seguras": plantillas, bloques, imágenes (URL + upload seguro), checklist e inbox preview.

export { triggerSaveAsTemplate };

export function init() {
  if (typeof window !== 'undefined' && String(window.location?.search||'').includes('debug_js=1')) {
    console.debug('[email_campaign_editor] init OK');
  }

  const form = document.querySelector('form[action*="/admin/marketing/email/campanas/save"]');
  const editor = document.getElementById('email-editor');
  const toolbar = document.getElementById('email-editor-toolbar');
  const editToggleBtn = document.getElementById('email-edit-toggle');
  const bodyHidden = document.getElementById('body_html');
  const rawArea = document.getElementById('body_html_raw');
  const htmlToolbar = document.getElementById('email-html-toolbar');
  const htmlFormatBtn = document.getElementById('email-html-format');
  const htmlMinifyBtn = document.getElementById('email-html-minify');
  const htmlWrapBtn = document.getElementById('email-html-wrap');
  const htmlLinesEl = document.getElementById('email-html-lines');
  const iframe = document.getElementById('email-preview');
  const previewWrap = document.getElementById('email-preview-wrap');

  // ---------------------------------------------------------------------------
  // Etapa 4 (robustez): "Guardar como plantilla" debe funcionar aunque falle JS.
  // Implementamos fallback HTML (form submit) + mejora: al enviar, copiamos el HTML
  // actual del editor al campo body_html de la plantilla.
  // ---------------------------------------------------------------------------
  const saveAsTplFormEarly = document.getElementById('save-as-template-form');
  bindSaveAsTemplateFallback(saveAsTplFormEarly, () => {
    const bodyInput =
      document.querySelector('input[name="body_html"]') ||
      document.querySelector('textarea[name="body_html"]') ||
      document.getElementById('body_html') ||
      document.getElementById('body_html_raw');
    return (bodyInput && ('value' in bodyInput) ? String(bodyInput.value) : '').trim();
  });

  // Si faltan elementos base del form, no rompemos: simplemente no inicializamos el resto.
  // (El botón de plantilla ya quedó enganchado arriba.)
  if (!form || !bodyHidden || !rawArea || !editor) return;

  // ---------------------------------------------------------------------------
  // UX: Estado vs Fecha/Hora
  // - Borrador: ocultamos/deshabilitamos la fecha para evitar confusión.
  // - Programada: mostramos y la dejamos como requerida.
  // ---------------------------------------------------------------------------
  const statusSelect = document.getElementById('campaign-status') || form.querySelector('select[name="status"]');
  const scheduledWrap = document.getElementById('campaign-scheduled-wrap');
  const scheduledInput = document.getElementById('scheduled_at') || form.querySelector('input[name="scheduled_at"]');
  const scheduledHelp = document.getElementById('scheduled-help');

  const applyScheduleUi = () => {
    if (!statusSelect || !scheduledWrap || !scheduledInput) return;
    const st = String(statusSelect.value || '').trim();

    if (st === 'scheduled') {
      // Restaurar valor previo si existía
      if (!scheduledInput.value && scheduledInput.dataset.prevValue) {
        scheduledInput.value = scheduledInput.dataset.prevValue;
      }
      scheduledWrap.classList.remove('is-hidden');
      scheduledInput.disabled = false;
      scheduledInput.required = true;
      if (scheduledHelp) {
        scheduledHelp.textContent = 'Obligatoria para Programada. Si querés enviar ya, dejá en Borrador y usá “Programar ahora” en el detalle.';
      }
      return;
    }

    // draft (u otros estados futuros): ocultar y limpiar
    scheduledInput.dataset.prevValue = scheduledInput.value || scheduledInput.dataset.prevValue || '';
    scheduledInput.value = '';
    scheduledInput.required = false;
    scheduledInput.disabled = true;
    scheduledWrap.classList.add('is-hidden');
    if (scheduledHelp) {
      scheduledHelp.textContent = 'Si lo dejás vacío, guardá como Borrador y usá “Programar ahora” desde el detalle.';
    }
  };

  if (statusSelect) {
    statusSelect.addEventListener('change', applyScheduleUi);
  }
  applyScheduleUi();

  // Los parámetros de audiencia siguen estando disponibles para el backend;
  // mostramos solo los campos pertinentes al segmento elegido.
  const audienceSelect = form.querySelector('select[name="audience"]');
  const audienceConditions = Array.from(form.querySelectorAll('[data-email-audience-condition]'));
  const applyAudienceUi = () => {
    const selected = audienceSelect?.value || 'all';
    audienceConditions.forEach((field) => {
      field.hidden = field.dataset.emailAudienceCondition !== selected;
    });
  };
  audienceSelect?.addEventListener('change', applyAudienceUi);
  applyAudienceUi();

  const previewUrl = form.querySelector('input[name="__preview_url"]')?.value || '';
  // El preview puede no existir en algunos renders: no bloqueamos toda la pantalla por eso.
  // (Si no hay previewUrl o iframe, solo se deshabilita el preview; el resto sigue.)

  // Campos “inbox / checklist”
  const subjectInput = document.getElementById('campaign-subject') || form.querySelector('input[name="subject"]');
  const preheaderInput = document.getElementById('campaign-preheader') || form.querySelector('input[name="preheader"]');
  const ctaLabelInput = form.querySelector('input[name="cta_label"]');
  const ctaUrlInput = document.getElementById('campaign-cta-url') || form.querySelector('input[name="cta_url"]');

  const inboxSubjectEls = [document.getElementById('email-inbox-subject'), document.getElementById('email-modal-inbox-subject')].filter(Boolean);
  const inboxPreheaderEls = [document.getElementById('email-inbox-preheader'), document.getElementById('email-modal-inbox-preheader')].filter(Boolean);
const chkSubject = document.getElementById('chk-subject');
  const chkPreheader = document.getElementById('chk-preheader');
  const chkCta = document.getElementById('chk-cta');
  const chkBody = document.getElementById('chk-body');
  const chkSummary = document.getElementById('chk-summary');

  // Plantillas / bloques
  const tplSel = document.getElementById('email-template');
  const tplApply = document.getElementById('email-template-apply');

  // Etapa 4: Plantillas guardadas
  const savedTplSel = document.getElementById('saved-template-id');
  const saveAsTplBtn = document.getElementById('save-as-template');
  const saveAsTplForm = document.getElementById('save-as-template-form');
  const savedTplLoadLink = document.getElementById('saved-template-load');
  const applyTplBtn = document.querySelector('[data-confirm-apply-template]');

  // Imágenes
  const imgUrl = document.getElementById('email-image-url');
  const imgInsertUrl = document.getElementById('email-image-insert-url');
  const imgFile = document.getElementById('email-image-file');
  const imgUploadBtn = document.getElementById('email-image-upload');
  const imgUploadMsg = document.getElementById('email-image-upload-msg');
  const uploadUrl = document.getElementById('email-upload-url')?.value || '';
  const imgPreview = document.getElementById('email-image-preview');
  const imgPreviewEmpty = document.getElementById('email-image-preview-empty');
  const imgPreset = document.getElementById('email-image-preset');
  const imgAlign = document.getElementById('email-image-align');

  // Acciones de bloque (mover/duplicar/eliminar)
  const blockSelectedLabel = document.getElementById('email-block-selected-label');
  const blockActionBtns = Array.from(document.querySelectorAll('[data-block-action]'));

  let mode = 'visual'; // 'visual' | 'html'
  let isEditing = false; // escritura habilitada?

  // ----- helpers -----
  const safeUrl = (u) => {
    const s = String(u || '').trim();
    if (!s) return '';
    // permitir http(s), mailto, tel y rutas relativas
    if (/^(https?:\/\/|mailto:|tel:|\/)/i.test(s)) return s;
    return 'https://' + s;
  };

  const escapeHtml = (s) => String(s || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  function getIdFromCampaignUrl() {
    try {
      const u = new URL(location.href);
      const id = parseInt(u.searchParams.get('id') || '0', 10);
      return isNaN(id) ? 0 : id;
    } catch { return 0; }
  }

  // ----- HTML textarea helpers (formatear / minificar / wrap) -----
  let htmlWrapOn = true;

  let didAutoFormatHtmlOnce = false; // auto-formatear al entrar a Ver HTML (1 vez)

  function updateHtmlLines() {
    if (!htmlLinesEl || !rawArea) return;
    const v = String(rawArea.value || '');
    const n = v ? v.split(/\r\n|\r|\n/).length : 1;
    htmlLinesEl.textContent = 'Líneas: ' + n;
  }

  function setHtmlWrap(on) {
    htmlWrapOn = !!on;
    if (!rawArea) return;
    // wrap="soft" usa salto visual; wrap="off" mantiene línea y permite scroll horizontal.
    rawArea.wrap = htmlWrapOn ? 'soft' : 'off';
    rawArea.style.whiteSpace = htmlWrapOn ? 'pre-wrap' : 'pre';
    rawArea.style.overflowX = 'auto';
    if (htmlWrapBtn) {
      htmlWrapBtn.textContent = htmlWrapOn ? 'Wrap: ON' : 'Wrap: OFF';
      htmlWrapBtn.setAttribute('aria-pressed', htmlWrapOn ? 'true' : 'false');
    }
  }

  function formatHtmlSimple(html) {
    const s0 = String(html || '').trim();
    if (!s0) return '';

    // Tokenizar: comentarios / tags / texto
    const tokens = s0.match(/<!--[\s\S]*?-->|<\/?[a-zA-Z][^>]*>|[^<]+/g) || [];
    const voidTags = new Set(['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
    const inlineTags = new Set(['a','span','strong','em','b','i','u','small','sup','sub','code']);
    const singleLineTags = new Set(['li','p','h1','h2','h3','h4','h5','h6','a','span','strong','em','b','i','u']);

    const out = [];
    let indent = 0;

    const normText = (t) => String(t || '').replace(/\s+/g, ' ').trim();

    const getTagName = (tagToken) => {
      const m = /^<\/?\s*([a-zA-Z0-9:_-]+)/.exec(tagToken);
      return m ? m[1].toLowerCase() : '';
    };

    for (let i = 0; i < tokens.length; i++) {
      let tok = tokens[i];

      // Texto
      if (tok[0] !== '<') {
        const text = normText(tok);
        if (text) {
          out.push('  '.repeat(indent) + text);
        }
        continue;
      }

      // Comentarios
      if (tok.startsWith('<!--')) {
        out.push('  '.repeat(indent) + tok.trim());
        continue;
      }

      const isClose = /^<\s*\//.test(tok);
      const name = getTagName(tok);

      if (isClose) {
        indent = Math.max(0, indent - 1);
        out.push('  '.repeat(indent) + tok.trim());
        continue;
      }

      // Apertura / self-close
      const selfClose = /\/>\s*$/.test(tok) || (name && voidTags.has(name));
      const isInline = name && inlineTags.has(name);

      // Caso: <li>Texto</li> (o p/h*)
      if (!selfClose && name && singleLineTags.has(name)) {
        const next = tokens[i + 1];
        const next2 = tokens[i + 2];
        const isNextText = next && next[0] !== '<' && normText(next);
        const isNextCloseSame = next2 && next2[0] === '<' && new RegExp(`^<\\s*\\/\\s*${name}\\b`, 'i').test(next2);
        if (isNextText && isNextCloseSame) {
          out.push('  '.repeat(indent) + tok.trim() + normText(next) + next2.trim());
          i += 2;
          continue;
        }
      }

      out.push('  '.repeat(indent) + tok.trim());

      // Solo aumentar indent para contenedores (no inline)
      if (!selfClose && !isInline) {
        indent += 1;
      }
    }

    return out.join('\n');
  }

  function minifyHtmlSimple(html) {
    // Minificar conservador: solo colapsa espacios entre tags.
    let s = String(html || '').trim();
    if (!s) return '';
    s = s.replace(/>\s+</g, '><');
    return s;
  }


    // ----- selección de bloques para mover/duplicar/eliminar -----
  let selectedBlock = null;

  function getBlockTypeLabel(el) {
    const t = el?.getAttribute?.('data-email-block') || '';
    const map = {
      card: 'Card',
      grid2: 'Grilla 2',
      two_cols: '2 columnas',
      box: 'Caja destacada'
    };
    return map[t] || (t ? t : '—');
  }

  function updateBlockActionButtons() {
    const enabled = !!selectedBlock && mode === 'visual';
    if (blockSelectedLabel) {
      blockSelectedLabel.textContent = selectedBlock ? getBlockTypeLabel(selectedBlock) : 'Ninguno';
    }
    blockActionBtns.forEach((btn) => (btn.disabled = !enabled));
  }


  // ----- edición bloqueada / habilitada -----
  const modActionSelectors = [
    '[data-cmd]',
    '[data-action]',
    '[data-insert-block]',
    '[data-open-card-builder]',
    '#email-template-apply',
    '#email-image-insert-url',
    '#email-image-upload',
    '#email-card-builder-insert',
    '#email-html-format',
    '#email-html-minify',
    '#email-html-wrap',
  ];

  function setEditingEnabled(enabled) {
    isEditing = !!enabled;
    if (editor) {
      editor.setAttribute('contenteditable', isEditing ? 'true' : 'false');
      editor.classList.toggle('is-locked', !isEditing);
    }
    if (rawArea) {
      rawArea.disabled = !isEditing;
      rawArea.classList.toggle('is-dimmed', !isEditing);
    }
    if (editToggleBtn) {
      editToggleBtn.textContent = isEditing ? 'Bloquear' : 'Editar';
      editToggleBtn.classList.toggle('is-active', isEditing);
    }
    // Deshabilitar acciones que modifican cuando está bloqueado
    modActionSelectors.forEach((sel) => {
      document.querySelectorAll(sel).forEach((el) => {
        // no bloquear el propio toggle ni el preview
        if (el === editToggleBtn) return;
        if (el && el.id === 'email-preview-open') return;
        // tabs Visual/HTML y tabs de herramientas siguen habilitados
        if (el && el.hasAttribute && (el.hasAttribute('data-email-editor-tab') || el.hasAttribute('data-tools-tab'))) return;
        if ('disabled' in el) el.disabled = !isEditing;
        el.classList.toggle('is-disabled', !isEditing);
      });
    });
    // Toolbar container
    if (toolbar) {
      toolbar.classList.toggle('is-dimmed', !isEditing);
    }
  }

  // ----- selección visual de bloque -----
  function applySelectionHighlight() {
    try {
      editor?.querySelectorAll?.('.email-block-selected').forEach((n) => n.classList.remove('email-block-selected'));
      if (selectedBlock) selectedBlock.classList.add('email-block-selected');
    } catch {}
  }

  function setSelectedBlock(el) {
    selectedBlock = el || null;
    applySelectionHighlight();
    updateBlockActionButtons();
  }

  function pickBlockFromNode(node) {
    if (!node) return null;
    const el = node.nodeType === 3 ? node.parentElement : node;
    if (!el || !el.closest) return null;
    return el.closest('[data-email-block]');
  }

  function pickBlockFromSelection() {
    try {
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return null;
      return pickBlockFromNode(sel.anchorNode);
    } catch {
      return null;
    }
  }

  function getMovableContainer(block) {
    // Si el bloque está dentro de un contenedor (p/div), movemos el contenedor directo del editor
    let el = block;
    while (el && el.parentElement && el.parentElement !== editor) {
      el = el.parentElement;
    }
    return el || block;
  }

  function moveSelectedBlock(dir) {
    if (!selectedBlock || mode !== 'visual') return;
    const container = getMovableContainer(selectedBlock);
    const parent = container.parentNode;
    if (!parent) return;
    const kids = Array.from(parent.children);
    const i = kids.indexOf(container);
    if (i < 0) return;
    if (dir < 0 && i === 0) return;
    if (dir > 0 && i === kids.length - 1) return;

    if (dir < 0) parent.insertBefore(container, kids[i - 1]);
    else parent.insertBefore(container, kids[i + 1].nextSibling);

    focusEditor();
    syncFromVisual();
    // re-evaluar selección
    setSelectedBlock(pickBlockFromSelection() || pickBlockFromNode(container));
  }

  function duplicateSelectedBlock() {
    if (!selectedBlock || mode !== 'visual') return;
    const container = getMovableContainer(selectedBlock);
    const parent = container.parentNode;
    if (!parent) return;

    const clone = container.cloneNode(true);
    parent.insertBefore(clone, container.nextSibling);

    focusEditor();
    syncFromVisual();

    const newBlock = clone.querySelector?.('[data-email-block]') || (clone.matches?.('[data-email-block]') ? clone : null);
    setSelectedBlock(newBlock);
  }

  function deleteSelectedBlock() {
    if (!selectedBlock || mode !== 'visual') return;
    if (!confirm('¿Eliminar este bloque del email?')) return;

    const container = getMovableContainer(selectedBlock);
    container.remove();

    focusEditor();
    syncFromVisual();
    setSelectedBlock(null);
  }

function focusEditor() {
    try { editor.focus(); } catch {}
  }

  function selectionIsInsideEditor() {
    const sel = window.getSelection ? window.getSelection() : null;
    if (!sel || sel.rangeCount === 0) return false;
    const node = sel.anchorNode;
    if (!node) return false;
    return editor.contains(node);
  }

  function placeCaretAtEnd() {
    try {
      editor.focus();
      const range = document.createRange();
      range.selectNodeContents(editor);
      range.collapse(false);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
      return true;
    } catch {
      return false;
    }
  }

  function insertHtmlVisual(html) {
    focusEditor();
    // Firefox/Chrome: si el foco/selección no está en el editor, insertHTML no hace nada.
    if (!selectionIsInsideEditor()) placeCaretAtEnd();
    try {
      const ok = document.execCommand('insertHTML', false, html);
      if (!ok) throw new Error('insertHTML returned false');
    } catch {
      // Fallback seguro
      editor.innerHTML = (editor.innerHTML || '') + html;
      placeCaretAtEnd();
    }
  }

  
  // Wrapper: inserta HTML según el modo actual (Visual/HTML)
  function insertHtml(html) {
    if (mode === 'html') {
      // Insertar en el textarea (modo HTML)
      if (rawArea) {
        insertIntoTextarea(rawArea, html);
        syncFromHtml();
      }
      return;
    }
    // Insertar en el contenteditable (modo Visual)
    insertHtmlVisual(html);
    syncFromVisual();
  }
  function getBodyHtml() {
    if (mode === 'html') return (rawArea.value || '').trim();
    return (editor.innerHTML || '').trim();
  }

  function setBodyHtml(html) {
    const h = String(html || '');
    bodyHidden.value = h;
    rawArea.value = h;
    editor.innerHTML = h;
    if (mode === 'html') {
      rawArea.classList.remove('is-hidden');
      if (htmlToolbar) htmlToolbar.classList.remove('is-hidden');
      updateHtmlLines();
      setHtmlWrap(true);
      editor.classList.add('is-hidden');
    } else {
      rawArea.classList.add('is-hidden');
      if (htmlToolbar) htmlToolbar.classList.add('is-hidden');
      editor.classList.remove('is-hidden');
    }
  }

  function syncFromVisual() {
    const html = (editor.innerHTML || '').trim();
    bodyHidden.value = html;
    rawArea.value = html;
  }

  function syncFromHtml() {
    const html = (rawArea.value || '').trim();
    bodyHidden.value = html;
    editor.innerHTML = html;
  }

  function stripText(html) {
    const s = String(html || '');
    return s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  }

  function setCheck(el, ok) {
    if (!el) return;
    el.textContent = ok ? 'âœ“' : '•';
    el.classList.toggle('is-complete', ok);
  }

  function updateInboxAndChecklist() {
    const subj = String(subjectInput?.value || '').trim();
    const preh = String(preheaderInput?.value || '').trim();
    const ctaU = String(ctaUrlInput?.value || '').trim();

    inboxSubjectEls.forEach((el) => { el.textContent = subj !== '' ? subj : 'Sin asunto (recomendado completar)'; });
    inboxPreheaderEls.forEach((el) => { el.textContent = preh !== '' ? preh : 'Sin texto de vista previa (opcional, pero ayuda)'; });

    const bodyText = stripText(getBodyHtml());

    const ok1 = subj.length >= 5;
    const ok2 = preh.length >= 10;
    const ok3 = ctaU !== '';
    const ok4 = bodyText.length >= 30;

    setCheck(chkSubject, ok1);
    setCheck(chkPreheader, ok2);
    setCheck(chkCta, ok3);
    setCheck(chkBody, ok4);

    if (chkSummary) chkSummary.textContent = `${[ok1, ok2, ok3, ok4].filter(Boolean).length}/4`;
  }

  function scrollToImagesPanel() {
    if (toolsTabBtns.length) setToolsTab('images');
    if (!imgUrl) return;
    try {
      imgUrl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      imgUrl.focus();
    } catch {}
  }


  function setImagePreview(url) {
    if (!imgPreview || !imgPreviewEmpty) return;
    const u = String(url || '').trim();
    if (!u) {
      imgPreview.classList.add('is-hidden');
      imgPreviewEmpty.classList.remove('is-hidden');
      imgPreview.removeAttribute('src');
      return;
    }
    imgPreviewEmpty.classList.add('is-hidden');
    imgPreview.classList.remove('is-hidden');
    imgPreview.setAttribute('src', u);
  }

  function insertImage(url) {
    const u = safeUrl(url);
    if (!u) return;

    const preset = (imgPreset && imgPreset.value) ? String(imgPreset.value) : '600';
    const align = (imgAlign && imgAlign.value) ? String(imgAlign.value) : 'center';

    const esc = u.replace(/"/g, '&quot;');

    // Email-safe sizing:
    // - width attr for consistent rendering
    // - max-width:100% + height:auto for responsiveness
    let widthAttr = '';
    if (preset && preset !== 'auto') {
      const w = parseInt(preset, 10);
      if (!isNaN(w) && w > 0) widthAttr = ' width="' + w + '"';
    }

    const containerStyle = 'margin:12px 0;text-align:' + (align === 'left' || align === 'right' ? align : 'center') + ';';
    const imgStyle =
      'max-width:100%;height:auto;border:0;outline:none;text-decoration:none;' +
      'border-radius:14px;' +
      (widthAttr ? 'display:inline-block;' : 'display:block;width:100%;');

    const html =
      '<p style="' + containerStyle + '">' +
        '<img src="' + esc + '" alt=""' + widthAttr + ' style="' + imgStyle + '" />' +
      '</p>';

    if (mode !== 'visual') {
      // Inserción en HTML
      insertIntoTextarea(rawArea, html);
      syncFromHtml();
    } else {
      insertHtmlVisual(html);
      syncFromVisual();
    }

    // UX: actualizamos la vista previa del panel de imágenes
    setImagePreview(u);
}

  function ensureCtaDefaults(kind) {
    // No pisamos si ya hay valores.
    if (ctaLabelInput && String(ctaLabelInput.value || '').trim() === '') {
      const map = {
        oferta: 'Ver oferta',
        lanzamiento: 'Ver novedad',
        contenido: 'Ver contenido',
        reactivacion: 'Volver a la tienda',
      };
      ctaLabelInput.value = map[kind] || 'Ver';
    }
    if (ctaUrlInput && String(ctaUrlInput.value || '').trim() === '') {
      const map = {
        oferta: '/productos',
        lanzamiento: '/productos',
        contenido: '/',
        reactivacion: '/productos',
      };
      ctaUrlInput.value = map[kind] || '/';
    }
  }

  function buildTemplate(kind) {
    // IMPORTANT: el CTA “real” lo agrega la plantilla del servidor usando cta_label/cta_url.
    // Acá ponemos contenido que guía, sin depender de HTML complejo.

    if (kind === 'oferta') {
      return (
        '<h2 style="margin:0 0 8px;">ðŸ”¥ Oferta por tiempo limitado</h2>' +
        '<p style="margin:0 0 12px;">Aprovechá hoy: precios especiales en productos seleccionados.</p>' +
        '<ul style="margin:0 0 12px;padding-left:18px;">' +
          '<li>âœ… Envíos a todo el país</li>' +
          '<li>âœ… Stock limitado</li>' +
          '<li>âœ… Comprá en 2 minutos</li>' +
        '</ul>' +
        '<p style="margin:0 0 12px;color:#374151;">ðŸ‘‡ Tocá el botón para ver la oferta.</p>'
      );
    }

    if (kind === 'lanzamiento') {
      return (
        '<h2 style="margin:0 0 8px;">ðŸš€ Lanzamiento</h2>' +
        '<p style="margin:0 0 12px;">Llegó algo nuevo a la tienda. Te dejamos lo más importante:</p>' +
        '<div style="margin:0 0 12px;padding:12px;border-radius:14px;background:#f8fafc;border:1px solid #e5e7eb;">' +
          '<p style="margin:0 0 8px;"><strong>âœ”ï¸ Beneficio 1</strong> — explicalo en una línea.</p>' +
          '<p style="margin:0 0 8px;"><strong>âœ”ï¸ Beneficio 2</strong> — explicalo en una línea.</p>' +
          '<p style="margin:0;"><strong>âœ”ï¸ Beneficio 3</strong> — explicalo en una línea.</p>' +
        '</div>' +
        '<p style="margin:0 0 12px;color:#374151;">ðŸ‘‡ Miralo completo con el botón.</p>'
      );
    }

    if (kind === 'contenido') {
      return (
        '<h2 style="margin:0 0 8px;">ðŸŽ¥ Nuevo contenido</h2>' +
        '<p style="margin:0 0 12px;">Si te gusta la pesca, esto te va a servir. Te dejo el resumen:</p>' +
        '<p style="margin:12px 0;">' +
          '<img src="https://via.placeholder.com/800x450" alt="" style="max-width:100%;height:auto;border-radius:14px;display:block;" />' +
        '</p>' +
        '<ul style="margin:0 0 12px;padding-left:18px;">' +
          '<li>ðŸ“Œ Punto clave 1</li>' +
          '<li>ðŸ“Œ Punto clave 2</li>' +
          '<li>ðŸ“Œ Punto clave 3</li>' +
        '</ul>' +
        '<p style="margin:0 0 12px;color:#374151;">ðŸ‘‡ Tocá el botón para verlo.</p>'
      );
    }

    if (kind === 'reactivacion') {
      return (
        '<h2 style="margin:0 0 8px;">ðŸ‘‹ Te extrañamos</h2>' +
        '<p style="margin:0 0 12px;">Hace un tiempo que no te vemos por la tienda. Te dejo una opción simple:</p>' +
        '<p style="margin:0 0 12px;">âœ… Volvé a explorar novedades y ofertas en un clic.</p>' +
        '<p style="margin:0 0 12px;color:#374151;">ðŸ‘‡ Tocá el botón para volver.</p>'
      );
    }

    return '';
  }

  function insertBlock(kind) {
    // Bloques “email-safe” con estilos inline y estructura simple.
    const blocks = {
      h1: '<h2 style="margin:0 0 8px;">Título</h2>',
      h2: '<h3 style="margin:12px 0 8px;">Subtítulo</h3>',
      divider: '<hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;" />',
      highlight:
        '<div style="margin:12px 0;padding:12px;border-radius:14px;background:#f8fafc;border:1px solid #e5e7eb;">' +
          '<p style="margin:0;"><strong>Dato importante:</strong> escribí acá tu textoâ€¦</p>' +
        '</div>',
      two_cols:
        '<table role="presentation" data-email-block="card" style="width:100%;border-collapse:collapse;margin:12px 0;">' +
          '<tr>' +
            '<td style="width:50%;padding-right:8px;vertical-align:top;">' +
              '<img src="https://via.placeholder.com/520x520" alt="" style="max-width:100%;height:auto;border-radius:14px;display:block;" />' +
            '</td>' +
            '<td style="width:50%;padding-left:8px;vertical-align:top;">' +
              '<h3 style="margin:0 0 8px;">Título</h3>' +
              '<p style="margin:0 0 8px;">Texto corto que explica el beneficio.</p>' +
              '<p style="margin:0;color:#6b7280;font-size:12px;">Tip: mantené esto simple para que se vea bien en todos lados.</p>' +
            '</td>' +
          '</tr>' +
        '</table>',
      card:
        '<table role="presentation" style="width:100%;border-collapse:separate;border-spacing:0;margin:12px 0;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;">' +
          '<tr>' +
            '<td style="padding:0;">' +
              '<img src="https://via.placeholder.com/1200x720" alt="" style="width:100%;max-width:100%;height:auto;display:block;" />' +
            '</td>' +
          '</tr>' +
          '<tr>' +
            '<td style="padding:14px 14px 16px;background:#ffffff;">' +
              '<h3 style="margin:0 0 6px;">Nombre del producto</h3>' +
              '<p style="margin:0 0 10px;color:#6b7280;">Descripción corta del beneficio.</p>' +
              '<p style="margin:0 0 14px;font-weight:700;font-size:18px;">$ 10.000</p>' +
              '<a href="https://tusitio.com/producto" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:12px;font-weight:600;">Ver producto</a>' +
            '</td>' +
          '</tr>' +
        '</table>',
      grid_2:
        '<table role="presentation" data-email-block="grid2" style="width:100%;border-collapse:collapse;margin:12px 0;">' +
          '<tr>' +
            '<td style="width:50%;padding-right:8px;vertical-align:top;">' +
              '<table role="presentation" style="width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;">' +
                '<tr><td style="padding:0;"><img src="https://via.placeholder.com/900x600" alt="" style="width:100%;max-width:100%;height:auto;display:block;" /></td></tr>' +
                '<tr><td style="padding:12px 12px 14px;background:#ffffff;">' +
                  '<p style="margin:0 0 6px;font-weight:700;">Producto 1</p>' +
                  '<p style="margin:0 0 10px;color:#6b7280;font-size:12px;">Descripción corta.</p>' +
                  '<p style="margin:0 0 12px;font-weight:700;">$ 10.000</p>' +
                  '<a href="https://tusitio.com/producto-1" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 12px;border-radius:12px;font-weight:600;font-size:13px;">Ver</a>' +
                '</td></tr>' +
              '</table>' +
            '</td>' +
            '<td style="width:50%;padding-left:8px;vertical-align:top;">' +
              '<table role="presentation" style="width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;">' +
                '<tr><td style="padding:0;"><img src="https://via.placeholder.com/900x600" alt="" style="width:100%;max-width:100%;height:auto;display:block;" /></td></tr>' +
                '<tr><td style="padding:12px 12px 14px;background:#ffffff;">' +
                  '<p style="margin:0 0 6px;font-weight:700;">Producto 2</p>' +
                  '<p style="margin:0 0 10px;color:#6b7280;font-size:12px;">Descripción corta.</p>' +
                  '<p style="margin:0 0 12px;font-weight:700;">$ 15.000</p>' +
                  '<a href="https://tusitio.com/producto-2" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 12px;border-radius:12px;font-weight:600;font-size:13px;">Ver</a>' +
                '</td></tr>' +
              '</table>' +
            '</td>' +
          '</tr>' +
        '</table>',
    };

    const html = blocks[kind];
    if (!html) return;

    if (mode !== 'visual') {
      insertIntoTextarea(rawArea, html);
      syncFromHtml();
    } else {
      insertHtmlVisual(html);
      syncFromVisual();
    }

    updateInboxAndChecklist();
    updateBlockActionButtons();
  }

  // ----- Tabs: Visual / HTML -----
  const tabBtns = Array.from(document.querySelectorAll('[data-email-editor-tab]'));
  const toolsTabBtns = Array.from(document.querySelectorAll('[data-tools-tab]'));
  const toolsPanels = Array.from(document.querySelectorAll('[data-tools-panel]'));

  // ----- Tabs: Herramientas (Plantillas / Bloques / Imágenes / Checklist) -----
  function setToolsTab(name) {
    const tab = String(name || '').trim();
    if (!tab) return;

    toolsTabBtns.forEach((b) => {
      const on = b.getAttribute('data-tools-tab') === tab;
      b.classList.toggle('is-active', on);
    });

    toolsPanels.forEach((p) => {
      const show = p.getAttribute('data-tools-panel') === tab;
      p.classList.toggle('is-hidden', !show);
    });
  }

  toolsTabBtns.forEach((b) => {
    b.addEventListener('click', (e) => {
      e.preventDefault();
      setToolsTab(b.getAttribute('data-tools-tab'));
    });
  });

  // Default: Bloques (las plantillas están arriba del editor)
  if (toolsTabBtns.length && toolsPanels.length) setToolsTab('blocks');
  function setMode(next) {
    mode = next === 'html' ? 'html' : 'visual';

    tabBtns.forEach((b) => {
      const isActive = b.getAttribute('data-email-editor-tab') === mode;
      b.classList.toggle('is-active', isActive);
    });

    if (mode === 'html') {
      syncFromVisual();
      rawArea.classList.remove('is-hidden');
      if (htmlToolbar) htmlToolbar.classList.remove('is-hidden');
      if (!didAutoFormatHtmlOnce) {
        const pretty = formatHtmlSimple(rawArea.value || '');
        rawArea.value = pretty;
        bodyHidden.value = pretty;
        didAutoFormatHtmlOnce = true;
      }
      updateHtmlLines();
      setHtmlWrap(htmlWrapOn);
      editor.classList.add('is-hidden');
      rawArea.focus();
    } else {
      syncFromHtml();
      rawArea.classList.add('is-hidden');
      if (htmlToolbar) htmlToolbar.classList.add('is-hidden');
      editor.classList.remove('is-hidden');
      focusEditor();
    }

    updateInboxAndChecklist();
    updateBlockActionButtons();
  }

  tabBtns.forEach((b) => {
    b.addEventListener('click', () => setMode(b.getAttribute('data-email-editor-tab')));
  });

  
  // Toggle edición (bloqueada por defecto)
  if (editToggleBtn) {
    editToggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      setEditingEnabled(!isEditing);
      if (isEditing) focusEditor();
    });
  }
  // Default: edición bloqueada
  setEditingEnabled(false);
  updateHtmlLines();
  setHtmlWrap(true);

// ----- Toolbar (execCommand) + acciones -----
  editor.addEventListener('click', (ev) => {
    // En modo bloqueado, clic selecciona bloque (sin escribir)
    if (!isEditing) {
      const b0 = ev.target?.closest?.('[data-email-block]') || pickBlockFromNode(ev.target);
      if (b0) setSelectedBlock(b0);
      return;
    }
    focusEditor();
    const b = ev.target?.closest?.('[data-email-block]') || pickBlockFromSelection();
    if (b) setSelectedBlock(b);
  });

  editor.addEventListener('mouseup', () => setSelectedBlock(pickBlockFromSelection()));
  editor.addEventListener('keyup', () => setSelectedBlock(pickBlockFromSelection()));

  // Acciones: mover/duplicar/eliminar bloque seleccionado
  blockActionBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const a = btn.getAttribute('data-block-action');
      if (a === 'up') moveSelectedBlock(-1);
      else if (a === 'down') moveSelectedBlock(1);
      else if (a === 'duplicate') duplicateSelectedBlock();
      else if (a === 'delete') deleteSelectedBlock();
    });
  });

  updateBlockActionButtons();

  document.addEventListener('click', (ev) => {
    const btn = ev.target?.closest?.('[data-cmd],[data-action]');
    if (!btn) return;
    if (!form.contains(btn)) return;

    ev.preventDefault();

    const cmd = btn.getAttribute('data-cmd');
    const action = btn.getAttribute('data-action');

    if (cmd) {
      if (mode !== 'visual') setMode('visual');
      focusEditor();
      if (!selectionIsInsideEditor()) placeCaretAtEnd();
      try { document.execCommand(cmd, false, null); } catch {}
      syncFromVisual();
      updateInboxAndChecklist();
      return;
    }

    if (action === 'link') {
      const url = safeUrl(prompt('Pegá el link (URL):', 'https://'));
      if (!url) return;

      // Si hay texto seleccionado en visual, lo linkeamos. Si no hay selección, insertamos un link con texto.
      if (mode === 'html') {
        const text = (prompt('Texto del link:', 'Ver más') || 'Ver más').trim();
        const html = '<a href="' + url.replace(/"/g, '&quot;') + '">' + escapeHtml(text) + '</a>';
        insertIntoTextarea(rawArea, html);
        syncFromHtml();
        updateInboxAndChecklist();
        return;
      }

      if (mode !== 'visual') setMode('visual');
      focusEditor();

      const sel = window.getSelection ? window.getSelection() : null;
      const selectedText = sel && sel.rangeCount ? String(sel.toString() || '').trim() : '';
      if (selectedText) {
        try {
          // createLink requiere selección dentro del editor
          if (!selectionIsInsideEditor()) placeCaretAtEnd();
          document.execCommand('createLink', false, url);
        } catch {
          insertHtmlVisual('<a href="' + url.replace(/"/g, '&quot;') + '">' + escapeHtml(selectedText) + '</a>');
        }
      } else {
        const text = (prompt('Texto del link:', 'Ver más') || 'Ver más').trim();
        insertHtmlVisual('<a href="' + url.replace(/"/g, '&quot;') + '">' + escapeHtml(text) + '</a>');
      }

      syncFromVisual();
      updateInboxAndChecklist();
      return;
    }

    if (action === 'image') {
      // Nuevo: priorizamos el panel de imágenes.
      if (imgUrl) {
        scrollToImagesPanel();
        return;
      }
      if (mode !== 'visual') setMode('visual');
      const url = safeUrl(prompt('Link de la imagen (URL):', 'https://'));
      if (!url) return;
      insertImage(url);
      return;
    }

    if (action === 'button') {
      if (mode !== 'visual') setMode('visual');
      const label = (prompt('Texto del botón:', 'Ver') || '').trim();
      const url = safeUrl(prompt('Link del botón (URL):', '/productos'));
      if (!label || !url) return;
      insertHtmlVisual(
        '<p style="margin:12px 0;">' +
          '<a href="' + url.replace(/"/g, '&quot;') + '" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;">' +
            escapeHtml(label) +
          '</a>' +
        '</p>'
      );
      syncFromVisual();
      updateInboxAndChecklist();
      return;
    }

    if (action === 'hr') {
      insertBlock('divider');
    }
  });

  // ----- Plantillas -----
  if (tplApply && tplSel) {
    tplApply.addEventListener('click', () => {
      const kind = String(tplSel.value || '').trim();
      if (!kind) return;

      const current = stripText(getBodyHtml());
      if (current.length > 0) {
        const ok = confirm('Esto va a reemplazar el contenido actual del email. ¿Querés continuar?');
        if (!ok) return;
      }

      const html = buildTemplate(kind);
      if (!html) return;

      ensureCtaDefaults(kind);
      setBodyHtml(html);

      // Mantener placeholder si queda vacío
      if ((editor.innerHTML || '').trim() === '') editor.innerHTML = '';

      updateInboxAndChecklist();
    });
  }

  // ----- Etapa 4: Plantillas guardadas -----
  function getSelectedSavedTemplateId() {
    const v = String(savedTplSel?.value || '').trim();
    const n = parseInt(v, 10);
    return Number.isFinite(n) && n > 0 ? n : 0;
  }

  function syncSavedTemplateTargets() {
    const tid = getSelectedSavedTemplateId();

    // Cargar en campaña nueva (GET)
    if (savedTplLoadLink) {
      if (tid > 0) {
        savedTplLoadLink.setAttribute('href', '/admin/marketing/email/campanas/new?template_id=' + tid);
        savedTplLoadLink.classList.remove('is-disabled');
      } else {
        savedTplLoadLink.setAttribute('href', '#');
        savedTplLoadLink.classList.add('is-disabled');
      }
    }

    // Aplicar en campaña existente (POST)
    if (applyTplBtn) {
      const hidden = applyTplBtn.closest('form')?.querySelector?.('input[data-fill="template_id"]');
      if (hidden) hidden.value = tid ? String(tid) : '';
      applyTplBtn.disabled = !tid;
      applyTplBtn.classList.toggle('is-disabled', !tid);
    }
  }

  if (savedTplSel) {
    savedTplSel.addEventListener('change', syncSavedTemplateTargets);
    syncSavedTemplateTargets();
  }

  if (applyTplBtn) {
    applyTplBtn.addEventListener('click', (e) => {
      const tid = getSelectedSavedTemplateId();
      if (!tid) {
        e.preventDefault();
        return;
      }

      const current = stripText(getBodyHtml());
      if (current.length > 0) {
        const ok = confirm('Esto va a reemplazar el contenido actual del email. ¿Querés continuar?');
        if (!ok) {
          e.preventDefault();
          return;
        }
      }
    });
  }

  // ----- Bloques rápidos -----
  document.addEventListener('click', (ev) => {
    const b = ev.target?.closest?.('[data-insert-block]');
    if (!b) return;
    if (!form.contains(b)) return;
    ev.preventDefault();

    const kind = b.getAttribute('data-insert-block');
    if (!kind) return;
    insertBlock(kind);
  });

  // ----- Imágenes: URL + upload seguro -----
  if (imgUrl) {
    imgUrl.addEventListener('input', () => {
      const u = safeUrl(imgUrl.value);
      setImagePreview(u);
    });
  }

  if (imgInsertUrl && imgUrl) {
    imgInsertUrl.addEventListener('click', () => {
      const u = safeUrl(imgUrl.value);
      if (!u) return;
      insertImage(u);
    });
  }

  if (imgUploadBtn && imgFile && uploadUrl) {
    imgUploadBtn.addEventListener('click', async () => {
      const file = imgFile.files && imgFile.files[0];
      if (!file) {
        if (imgUploadMsg) imgUploadMsg.textContent = 'Elegí una imagen para subir.';
        return;
      }

      if (imgUploadMsg) imgUploadMsg.textContent = 'Subiendoâ€¦';
      imgUploadBtn.disabled = true;

      try {
        const csrf = form.querySelector('input[name="csrf"]')?.value || '';
        const fd = new FormData();
        if (csrf) fd.append('csrf', csrf);
        fd.append('image', file);

        const res = await fetch(uploadUrl, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' },
        });

        const data = await res.json().catch(() => null);
        if (!data || !data.ok || !data.url) {
          const msg = (data && (data.error || data.message)) ? String(data.error || data.message) : 'No se pudo subir la imagen.';
          if (imgUploadMsg) imgUploadMsg.textContent = msg;
          return;
        }

        if (imgUploadMsg) imgUploadMsg.textContent = 'Listo âœ…';
        if (imgUrl) imgUrl.value = data.url;
        setImagePreview(data.url);

        // Mejor UX: insertamos la imagen automáticamente.
        insertImage(data.url);
      } catch (e) {
        console.warn('[email image upload]', e);
        if (imgUploadMsg) imgUploadMsg.textContent = 'Error al subir. Probá de nuevo.';
      } finally {
        imgUploadBtn.disabled = false;
      }
    });
  }
  setupPreviewModal({
    form,
    previewUrl,
    iframe,
    previewWrap,
    getMode: () => mode,
    syncFromHtml,
    syncFromVisual,
    updateInboxAndChecklist,
  });

  // ----- Enviar test -----
  const sendTestOpenBtn = document.getElementById('email-send-test-open');
  const sendTestModal = document.getElementById('email-send-test-modal');
  const sendTestTo = document.getElementById('email-send-test-to');
  const sendTestSendBtn = document.getElementById('email-send-test-send');
  const sendTestStatus = document.getElementById('email-send-test-status');
  const sendTestUrl = form.querySelector('input[name="__send_test_url"]')?.value || '';

  function setSendTestStatus(msg, isError = false) {
    if (!sendTestStatus) return;
    sendTestStatus.textContent = msg || '';
    sendTestStatus.classList.toggle('is-error', !!isError);
    sendTestStatus.classList.toggle('is-success', !isError);
  }

  function openSendTestModal() {
    if (!sendTestModal) return;
    setSendTestStatus('', false);
    sendTestModal.classList.remove('is-hidden');
    sendTestModal.setAttribute('aria-hidden', 'false');
    setEmailDialogOpen(sendTestModal, true);
    setTimeout(() => sendTestTo?.focus(), 0);
  }

  function closeSendTestModal() {
    if (!sendTestModal) return;
    sendTestModal.classList.add('is-hidden');
    sendTestModal.setAttribute('aria-hidden', 'true');
    setEmailDialogOpen(sendTestModal, false);
  }

  sendTestOpenBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    openSendTestModal();
  });

  if (sendTestModal) {
    sendTestModal.querySelectorAll('[data-send-test-close]').forEach((el) => el.addEventListener('click', (e) => {
      e.preventDefault();
      closeSendTestModal();
    }));

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !sendTestModal.classList.contains('is-hidden')) closeSendTestModal();
    });
  }

  sendTestSendBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    if (!sendTestUrl) {
      setSendTestStatus('No se pudo enviar: falta URL de test.', true);
      return;
    }

    const to = (sendTestTo?.value || '').trim();
    if (!to || !to.includes('@')) {
      setSendTestStatus('Ingresá un email válido.', true);
      return;
    }

    const payload = {
      to,
      subject: (subjectInput?.value || '').trim(),
      preheader: (preheaderInput?.value || '').trim(),
      body_html: getBodyHtml(),
      html: getBodyHtml(),
    };

    // CSRF: el proyecto usa csrf_field() => <input name="csrf" value="...">.
    // Permitimos alias por compatibilidad.
    const csrf = (
      form.querySelector('input[name="csrf"]')?.value ||
      form.querySelector('input[name="_csrf"]')?.value ||
      form.querySelector('input[name="csrf_token"]')?.value ||
      ''
    );
    setSendTestStatus('Enviando testâ€¦', false);
    if (sendTestSendBtn) sendTestSendBtn.disabled = true;

    try {
      const res = await fetch(sendTestUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrf ? { 'X-CSRF-Token': csrf } : {}),
        },
        body: JSON.stringify(payload),
      });

      const json = await res.json().catch(() => ({}));
      if (!res.ok || !json?.ok) {
        setSendTestStatus(json?.error || 'No se pudo enviar el test.', true);
        return;
      }

      setSendTestStatus('Test enviado. Revisá tu bandeja (y spam).', false);
      // no cerramos automáticamente; el usuario puede enviar de nuevo
    } catch (err) {
      console.warn('[send test]', err);
      setSendTestStatus('Error al enviar el test.', true);
    } finally {
      if (sendTestSendBtn) sendTestSendBtn.disabled = false;
    }
  });

  // ----- Events -----
  editor.addEventListener('input', () => {
    syncFromVisual();
    updateInboxAndChecklist();
    updateBlockActionButtons();
  });
  rawArea.addEventListener('input', () => {
    syncFromHtml();
    updateHtmlLines();
    updateInboxAndChecklist();
    updateBlockActionButtons();
  });


  // Botones del modo HTML
  htmlFormatBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    if (!rawArea) return;
    const before = rawArea.value;
    const after = formatHtmlSimple(before);
    rawArea.value = after;
    syncFromHtml();
    updateHtmlLines();
    updateInboxAndChecklist();
  });

  htmlMinifyBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    if (!rawArea) return;
    const before = rawArea.value;
    const after = minifyHtmlSimple(before);
    rawArea.value = after;
    syncFromHtml();
    updateHtmlLines();
    updateInboxAndChecklist();
  });

  htmlWrapBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    setHtmlWrap(!htmlWrapOn);
  });

  subjectInput?.addEventListener('input', () => { updateInboxAndChecklist(); });
  preheaderInput?.addEventListener('input', () => { updateInboxAndChecklist(); });
  ctaLabelInput?.addEventListener('input', () => { updateInboxAndChecklist(); });
  ctaUrlInput?.addEventListener('input', () => { updateInboxAndChecklist(); });

  
  setupCardBuilder({
    form,
    editor,
    uploadUrl,
    safeUrl,
    insertHtml,
    syncFromVisual,
    updateInboxAndChecklist,
  });

  // --- Validacion UX (borde rojo sutil) ---
  // El back valida (p.ej. asunto/nombre obligatorios). Si el flash/toast no aparece,
  // el usuario siente que "guardó" pero al recargar vuelve lo anterior.
  // Marcamos los campos faltantes con un borde rojo sutil y evitamos el submit.
  // Usamos estilos inline para no depender de que Tailwind haya "visto" las clases
  // (JIT/safelist) y asegurar que el feedback se vea siempre.
  setupFormValidation({
    form,
    editor,
    rawArea,
    bodyHidden,
    getMode: () => mode,
    syncFromHtml,
    syncFromVisual,
  });

  // Primer render
  if ((editor.innerHTML || '').trim() === '') editor.innerHTML = '';
  syncFromVisual();
  updateInboxAndChecklist();
}








