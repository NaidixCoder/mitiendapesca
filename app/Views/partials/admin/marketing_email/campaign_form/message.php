<section class="av3-email-panel av3-email-composer__message" aria-labelledby="email-message-heading">
  <header class="av3-email-panel__header av3-email-composer__message-head">
    <div><h2 id="email-message-heading">Mensaje del email</h2><p>Escribí y revisá el contenido antes de programar.</p></div>
    <div class="av3-email-composer__message-actions">
      <div class="av3-email-composer__editor-tabs" aria-label="Modo de edición">
        <button type="button" data-email-editor-tab="visual" class="is-active">Visual</button>
        <button type="button" data-email-editor-tab="html">Ver HTML</button>
      </div>
      <button type="button" class="av3-ui-btn" id="email-preview-open">Vista previa</button>
      <button type="button" class="av3-ui-btn" id="email-send-test-open">Enviar test</button>
      <button type="button" class="av3-ui-btn" id="email-edit-toggle">Editar</button>
    </div>
  </header>
  <div class="av3-email-composer__message-body">
    <div id="email-editor-toolbar" class="av3-email-composer__editor-toolbar" role="toolbar" aria-label="Formato del mensaje">
      <button type="button" data-cmd="bold" title="Negrita"><strong>B</strong></button>
      <button type="button" data-cmd="italic" title="Cursiva"><em>I</em></button>
      <button type="button" data-cmd="underline" title="Subrayado"><u>U</u></button>
      <span class="av3-email-composer__toolbar-separator" aria-hidden="true"></span>
      <button type="button" data-cmd="insertUnorderedList" title="Lista">• Lista</button>
      <button type="button" data-cmd="insertOrderedList" title="Lista numerada">1. Lista</button>
      <span class="av3-email-composer__toolbar-separator" aria-hidden="true"></span>
      <button type="button" data-action="link" title="Insertar enlace">Enlace</button>
      <button type="button" data-action="image" title="Insertar imagen">Imagen</button>
      <button type="button" data-action="button" title="Insertar botón">Botón</button>
      <button type="button" data-action="hr" title="Separador">Separador</button>
      <span class="av3-email-composer__toolbar-separator" aria-hidden="true"></span>
      <button type="button" data-cmd="removeFormat" title="Quitar formato">Quitar formato</button>
      <button type="button" data-cmd="undo" title="Deshacer">↶</button>
      <button type="button" data-cmd="redo" title="Rehacer">↷</button>
    </div>
    <input type="hidden" name="__preview_url" form="save-as-template-form" value="<?= e((string)($links['preview'] ?? url('/admin/marketing/email/campanas/preview'))) ?>">
    <input type="hidden" name="__send_test_url" form="save-as-template-form" value="<?= e((string)($links['send_test'] ?? url('/admin/marketing/email/campanas/send_test'))) ?>">
    <textarea id="body_html" name="body_html" class="is-hidden"><?= e((string)old('body_html', (string)($c['body_html'] ?? ''))) ?></textarea>
    <div id="email-html-toolbar" class="av3-email-composer__html-toolbar is-hidden">
      <div class="av3-email-composer__toolbar-actions">
        <button type="button" class="av3-ui-btn" id="email-html-format" data-html-action="format">Formatear</button>
        <button type="button" class="av3-ui-btn" id="email-html-minify" data-html-action="minify">Minificar</button>
        <button type="button" class="av3-ui-btn" id="email-html-wrap" data-html-action="wrap" aria-pressed="true">Wrap: ON</button>
      </div>
      <span id="email-html-lines">Líneas: 1</span>
    </div>
    <textarea id="body_html_raw" name="body_html_raw" class="is-hidden" rows="14" spellcheck="false"><?= e((string)old('body_html_raw', (string)($c['body_html'] ?? ''))) ?></textarea>
    <div id="email-editor" class="is-locked" contenteditable="false" data-placeholder="Escribí tu email acá…"><?= (string)old('body_html', (string)($c['body_html'] ?? '')) ?></div>
    <p class="av3-email-composer__muted">Los bloques de columnas, cards y grillas usan tablas para verse correctamente en los clientes de email.</p>
  </div>
</section>
