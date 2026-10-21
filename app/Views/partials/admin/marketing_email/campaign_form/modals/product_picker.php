<div id="email-product-picker-modal" class="av3-email-composer__modal av3-email-composer__modal--picker is-hidden" aria-hidden="true">
  <div class="av3-email-composer__backdrop" data-product-picker-close></div>
  <section class="av3-email-composer__dialog av3-email-composer__dialog--picker" role="dialog" aria-modal="true" aria-labelledby="email-product-picker-heading">
    <header class="av3-email-composer__dialog-head">
      <div><h2 id="email-product-picker-heading">Elegir producto del catálogo</h2><p>Buscá por nombre, SKU, slug o ID para completar la card.</p></div>
      <button type="button" class="av3-ui-btn" data-product-picker-close>Cerrar</button>
    </header>
    <div class="av3-email-composer__dialog-body">
      <div class="av3-email-composer__search-row">
        <label for="pp-q" class="av3-email-composer__sr-only">Buscar producto</label>
        <input id="pp-q" type="search" placeholder="Nombre, SKU o slug">
        <button type="button" id="pp-search" class="av3-ui-btn av3-ui-btn--primary">Buscar</button>
      </div>
      <div id="pp-status" class="av3-email-composer__search-status" role="status" aria-live="polite">Escribí y tocá «Buscar».</div>
      <div class="av3-email-composer__product-results" id="pp-results"></div>
    </div>
    <footer class="av3-email-composer__dialog-foot">
      <span class="av3-email-composer__muted">Elegí un producto con imagen para mejorar la presentación del correo.</span>
      <button type="button" class="av3-ui-btn" data-product-picker-close>Cancelar</button>
    </footer>
  </section>
</div>
