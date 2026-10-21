<div class="av3-flow-2">
  <div class="av3-weight-strong">Catalogo</div>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input
      type="checkbox"
      name="catalog_hide_inactive_category_products"
      value="1"
      <?= (($cfg['catalog_hide_inactive_category_products'] ?? '0') === '1') ? 'checked' : '' ?>
    >
    <span>
      <div class="av3-weight-medium">Ocultar productos con categorias inactivas</div>
      <div class="av3-type-body av3-text-var-muted">
        Modo hibrido: al desactivar una categoria, los productos no se desactivan. Pero si esta opcion esta activada,
        no se mostraran en el sitio mientras la categoria este inactiva.
      </div>
    </span>
  </label>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input
      type="checkbox"
      name="catalog_allow_publish_without_images"
      value="1"
      <?= (($cfg['catalog_allow_publish_without_images'] ?? '0') === '1') ? 'checked' : '' ?>
    >
    <span>
      <div class="av3-weight-medium">Mostrar productos sin imagenes en el catalogo</div>
      <div class="av3-type-body av3-text-var-muted">
        Si esta opcion esta activada, las imagenes dejan de ser un requisito para que el producto quede activo y visible en la tienda.
      </div>
    </span>
  </label>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input
      type="checkbox"
      name="catalog_show_empty_categories"
      value="1"
      <?= (($cfg['catalog_show_empty_categories'] ?? '0') === '1') ? 'checked' : '' ?>
    >
    <span>
      <div class="av3-weight-medium">Mostrar categorias sin productos</div>
      <div class="av3-type-body av3-text-var-muted">
        Si esta opcion esta activada, las categorias con 0 productos seguiran visibles en /categorias.
      </div>
    </span>
  </label>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input
      type="checkbox"
      name="catalog_show_empty_brands"
      value="1"
      <?= (($cfg['catalog_show_empty_brands'] ?? '0') === '1') ? 'checked' : '' ?>
    >
    <span>
      <div class="av3-weight-medium">Mostrar marcas sin productos</div>
      <div class="av3-type-body av3-text-var-muted">
        Si esta opcion esta activada, las marcas con 0 productos seguiran visibles en /marcas.
      </div>
    </span>
  </label>

</div>
