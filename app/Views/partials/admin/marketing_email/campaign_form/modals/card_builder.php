<div id="email-card-builder-modal" class="av3-email-composer__modal av3-email-composer__modal--card is-hidden" aria-hidden="true">
  <div class="av3-email-composer__backdrop" data-card-modal-close></div>
  <section class="av3-email-composer__dialog av3-email-composer__dialog--card" role="dialog" aria-modal="true" aria-labelledby="email-card-builder-heading">
    <header class="av3-email-composer__dialog-head">
      <div>
        <h2 id="email-card-builder-heading">Asistente de cards</h2>
        <p>Creá bloques de productos compatibles con clientes de email.</p>
      </div>
      <button type="button" class="av3-ui-btn" data-card-modal-close>Cerrar</button>
    </header>

    <div class="av3-email-composer__dialog-body">
      <div class="av3-email-composer__tabs" aria-label="Tipo de bloque">
        <button type="button" data-card-builder-tab="card" class="is-active">Card</button>
        <button type="button" data-card-builder-tab="grid2">Grilla 2</button>
      </div>

      <section class="av3-email-composer__card-panel" data-card-builder-panel="card" aria-label="Card de producto">
        <div class="av3-email-composer__card-head">
          <h3>Card de producto</h3>
          <button type="button" class="av3-ui-btn" data-open-product-picker="card">Elegir del catálogo</button>
        </div>
        <div class="av3-email-composer__card-grid">
          <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
            <label for="cb-card-size">Tamaño de card e imagen</label>
            <select id="cb-card-size">
              <option value="normal" selected>Normal</option>
              <option value="compact">Compacta</option>
              <option value="large">Grande</option>
            </select>
          </div>
          <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
            <label for="cb-card-img">Imagen (URL)</label>
            <div class="av3-email-composer__card-file-row">
              <input id="cb-card-img" type="url" placeholder="https://...">
              <input id="cb-card-upload" type="file" accept="image/*" class="is-hidden">
              <button type="button" class="av3-ui-btn" data-cb-upload-btn="card">Subir imagen</button>
            </div>
            <small id="cb-card-upload-msg" role="status"></small>
          </div>
          <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
            <label for="cb-card-title">Título</label>
            <input id="cb-card-title" type="text" placeholder="Nombre del producto">
          </div>
          <div class="av3-email-composer__card-field">
            <label for="cb-card-price">Precio</label>
            <input id="cb-card-price" type="text" placeholder="10000">
          </div>
          <div class="av3-email-composer__card-field">
            <label for="cb-card-list-price">Precio de lista</label>
            <input id="cb-card-list-price" type="text" placeholder="12000">
          </div>
          <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
            <label for="cb-card-discount">Descuento calculado</label>
            <input id="cb-card-discount" type="text" readonly placeholder="—">
          </div>
          <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
            <label for="cb-card-desc">Descripción</label>
            <textarea id="cb-card-desc" rows="2" placeholder="Descripción corta"></textarea>
          </div>
          <div class="av3-email-composer__card-field">
            <label for="cb-card-cta-text">Texto del botón</label>
            <input id="cb-card-cta-text" type="text" placeholder="Ver">
          </div>
          <div class="av3-email-composer__card-field">
            <label for="cb-card-cta-url">Enlace del botón</label>
            <input id="cb-card-cta-url" type="url" placeholder="https://tusitio.com/producto">
          </div>
        </div>
      </section>

      <section class="av3-email-composer__card-panel is-hidden" data-card-builder-panel="grid2" aria-label="Grilla de dos productos">
        <div class="av3-email-composer__card-field">
          <label for="cb-grid2-size">Tamaño de cards e imágenes</label>
          <select id="cb-grid2-size">
            <option value="normal" selected>Normal</option>
            <option value="compact">Compacta</option>
            <option value="large">Grande</option>
          </select>
        </div>
        <div class="av3-email-composer__card-products">
          <fieldset class="av3-email-composer__card-product">
            <legend>Producto 1</legend>
            <div class="av3-email-composer__card-picker-action"><button type="button" class="av3-ui-btn" data-open-product-picker="g1">Elegir del catálogo</button></div>
            <div class="av3-email-composer__card-grid">
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g1-img">Imagen (URL)</label>
                <div class="av3-email-composer__card-file-row">
                  <input id="cb-g1-img" type="url" placeholder="https://...">
                  <input id="cb-g1-upload" type="file" accept="image/*" class="is-hidden">
                  <button type="button" class="av3-ui-btn" data-cb-upload-btn="g1">Subir</button>
                </div>
                <small id="cb-g1-upload-msg" role="status"></small>
              </div>
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g1-title">Título</label><input id="cb-g1-title" type="text" placeholder="Producto 1">
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g1-price">Precio</label><input id="cb-g1-price" type="text" placeholder="10000">
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g1-list-price">Precio de lista</label><input id="cb-g1-list-price" type="text" placeholder="12000">
              </div>
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g1-discount">Descuento calculado</label><input id="cb-g1-discount" type="text" readonly placeholder="—">
              </div>
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g1-desc">Descripción</label><textarea id="cb-g1-desc" rows="2" placeholder="Descripción corta"></textarea>
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g1-cta-text">Texto del botón</label><input id="cb-g1-cta-text" type="text" placeholder="Ver">
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g1-cta-url">Enlace del botón</label><input id="cb-g1-cta-url" type="url" placeholder="https://tusitio.com/producto">
              </div>
            </div>
          </fieldset>
          <fieldset class="av3-email-composer__card-product">
            <legend>Producto 2</legend>
            <div class="av3-email-composer__card-picker-action"><button type="button" class="av3-ui-btn" data-open-product-picker="g2">Elegir del catálogo</button></div>
            <div class="av3-email-composer__card-grid">
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g2-img">Imagen (URL)</label>
                <div class="av3-email-composer__card-file-row">
                  <input id="cb-g2-img" type="url" placeholder="https://...">
                  <input id="cb-g2-upload" type="file" accept="image/*" class="is-hidden">
                  <button type="button" class="av3-ui-btn" data-cb-upload-btn="g2">Subir</button>
                </div>
                <small id="cb-g2-upload-msg" role="status"></small>
              </div>
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g2-title">Título</label><input id="cb-g2-title" type="text" placeholder="Producto 2">
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g2-price">Precio</label><input id="cb-g2-price" type="text" placeholder="15000">
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g2-list-price">Precio de lista</label><input id="cb-g2-list-price" type="text" placeholder="18000">
              </div>
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g2-discount">Descuento calculado</label><input id="cb-g2-discount" type="text" readonly placeholder="—">
              </div>
              <div class="av3-email-composer__card-field av3-email-composer__card-field--full">
                <label for="cb-g2-desc">Descripción</label><textarea id="cb-g2-desc" rows="2" placeholder="Descripción corta"></textarea>
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g2-cta-text">Texto del botón</label><input id="cb-g2-cta-text" type="text" placeholder="Ver">
              </div>
              <div class="av3-email-composer__card-field">
                <label for="cb-g2-cta-url">Enlace del botón</label><input id="cb-g2-cta-url" type="url" placeholder="https://tusitio.com/producto">
              </div>
            </div>
          </fieldset>
        </div>
      </section>
    </div>

    <footer class="av3-email-composer__dialog-foot">
      <span class="av3-email-composer__muted">Para editar un bloque existente, colocá el cursor dentro de la card y elegí «Editar selección».</span>
      <button type="button" class="av3-ui-btn" data-card-modal-close>Cancelar</button>
      <button type="button" id="cb-apply" class="av3-ui-btn av3-ui-btn--primary">Insertar bloque</button>
    </footer>
  </section>
</div>
