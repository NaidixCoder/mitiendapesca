<section id="email-tools" class="av3-email-panel av3-email-composer__tools" aria-labelledby="email-tools-heading">
  <header class="av3-email-panel__header">
    <div><h2 id="email-tools-heading">Herramientas</h2><p>Insertá bloques e imágenes y revisá la campaña.</p></div>
    <div class="av3-email-composer__tool-tabs" aria-label="Herramientas de composición">
      <button type="button" data-tools-tab="blocks" class="is-active">Bloques</button>
      <button type="button" data-tools-tab="images">Imágenes</button>
      <button type="button" data-tools-tab="checklist">Checklist</button>
    </div>
  </header>
  <div class="av3-email-composer__tools-body">
    <div id="email-block-actions" class="av3-email-composer__block-actions">
      <div class="av3-email-composer__section-head">
        <div><h3>Orden del contenido</h3><p>Seleccioná una card o bloque dentro del editor para moverlo.</p></div>
        <span class="av3-email-composer__muted">Seleccionado: <strong id="email-block-selected-label">Ninguno</strong></span>
      </div>
      <div class="av3-email-composer__tool-buttons">
        <button type="button" class="av3-ui-btn" data-block-action="up">↑ Subir</button>
        <button type="button" class="av3-ui-btn" data-block-action="down">↓ Bajar</button>
        <button type="button" class="av3-ui-btn" data-block-action="duplicate">Duplicar</button>
        <button type="button" class="av3-ui-btn" data-block-action="delete">Eliminar</button>
      </div>
    </div>

    <div data-tools-panel="blocks" class="av3-email-composer__tool-panel is-hidden">
      <div class="av3-email-composer__section-head"><div><h3>Bloques rápidos</h3><p>Se insertan en el punto de edición.</p></div></div>
      <div class="av3-email-composer__tool-buttons">
        <button type="button" class="av3-ui-btn" data-insert-block="h1">Título</button>
        <button type="button" class="av3-ui-btn" data-insert-block="h2">Subtítulo</button>
        <button type="button" class="av3-ui-btn" data-insert-block="divider">Separador</button>
        <button type="button" class="av3-ui-btn" data-insert-block="highlight">Caja destacada</button>
        <button type="button" class="av3-ui-btn" data-insert-block="two_cols">2 columnas</button>
        <button type="button" class="av3-ui-btn" data-insert-block="card">Card producto</button>
        <button type="button" class="av3-ui-btn" data-insert-block="grid_2">Grilla 2 cards</button>
        <button type="button" class="av3-ui-btn" data-open-card-builder="card">Card (asistente)</button>
        <button type="button" class="av3-ui-btn" data-open-card-builder="grid2">Grilla 2 (asistente)</button>
        <button type="button" class="av3-ui-btn" data-open-card-builder="edit">Editar selección</button>
      </div>
      <p class="av3-email-composer__muted">Las columnas se generan con tablas compatibles con clientes de correo.</p>
    </div>

    <div data-tools-panel="images" class="av3-email-composer__tool-panel is-hidden">
      <div class="av3-email-composer__section-head"><div><h3>Imágenes</h3><p>Ajustá el tamaño e insertá una URL o subí un archivo.</p></div></div>
      <div class="av3-email-composer__image-options">
        <div class="av3-email-field">
          <label for="email-image-preset">Tamaño</label>
          <select id="email-image-preset">
            <option value="600" selected>Ancho 600 (recomendado)</option>
            <option value="300">Ancho 300</option>
            <option value="150">Ancho 150</option>
            <option value="auto">Original (sin ancho fijo)</option>
          </select>
        </div>
        <div class="av3-email-field">
          <label for="email-image-align">Alineación</label>
          <select id="email-image-align">
            <option value="left">Izquierda</option>
            <option value="center" selected>Centrada</option>
            <option value="right">Derecha</option>
          </select>
        </div>
      </div>
      <div class="av3-email-composer__image-sources">
        <div class="av3-email-composer__template-group">
          <label for="email-image-url">Pegar URL de imagen</label>
          <div class="av3-email-composer__control-row">
            <input id="email-image-url" type="url" placeholder="https://...">
            <button type="button" class="av3-ui-btn" id="email-image-insert-url">Insertar</button>
          </div>
        </div>
        <div class="av3-email-composer__template-group">
          <label for="email-image-file">Subir imagen</label>
          <div class="av3-email-composer__control-row">
            <input id="email-image-file" type="file" accept="image/*">
            <button type="button" class="av3-ui-btn" id="email-image-upload">Subir</button>
          </div>
          <p id="email-image-upload-msg" class="av3-email-composer__muted" role="status"></p>
        </div>
      </div>
      <input type="hidden" id="email-upload-url" value="<?= e((string)($links['upload_image'] ?? url('/admin/marketing/email/uploads/image'))) ?>">
      <input type="hidden" id="email-products-search-url" value="<?= e((string)($links['products_search'] ?? url('/admin/marketing/email/products/search'))) ?>">
      <div class="av3-email-composer__image-preview">
        <h3>Vista previa</h3>
        <img id="email-image-preview" class="is-hidden" src="" alt="">
        <span id="email-image-preview-empty" class="av3-email-composer__muted">Todavía no elegiste ninguna imagen.</span>
      </div>
    </div>

    <div data-tools-panel="checklist" class="av3-email-composer__tool-panel is-hidden">
      <div class="av3-email-composer__section-head">
        <div><h3>Antes de enviar</h3><p>Comprobá los datos principales de la campaña.</p></div>
        <span class="av3-email-status">Estado: <strong id="chk-summary">0/4</strong></span>
      </div>
      <details class="av3-email-composer__check-details">
        <summary>Ver comprobaciones</summary>
        <div class="av3-email-composer__check-list">
          <div><span id="chk-subject" class="av3-email-composer__check-icon">•</span><div><strong>Asunto claro</strong><p>Qué gana el cliente.</p></div></div>
          <div><span id="chk-preheader" class="av3-email-composer__check-icon">•</span><div><strong>Texto previo útil</strong><p>Complementa al asunto.</p></div></div>
          <div><span id="chk-cta" class="av3-email-composer__check-icon">•</span><div><strong>Botón con enlace</strong><p>Un destino principal medible.</p></div></div>
          <div><span id="chk-body" class="av3-email-composer__check-icon">•</span><div><strong>Contenido preparado</strong><p>Texto y estructura completos.</p></div></div>
        </div>
      </details>
    </div>
  </div>
</section>
