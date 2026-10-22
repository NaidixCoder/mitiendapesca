<?php
$creationOptions = [
    ['create_missing_products', 'Crear productos cuando el SKU no exista', true],
    ['create_missing_brands', 'Crear marcas por nombre', false],
    ['create_missing_categories', 'Crear categorías por nombre', false],
    ['create_missing_suppliers', 'Crear proveedores por nombre', false],
    ['allow_parent_from_variants', 'Crear padre a partir de variantes (sku_padre)', false],
];
$updateGroups = [
    'Operativo y precios' => [
        ['u_cost', 'Costo neto sin IVA', true],
        ['u_price', 'Precio de venta', true],
        ['u_wholesale_overrides', 'Precio mayorista personalizado', false],
        ['u_list_price', 'Precio de lista', false],
        ['u_stock', 'Stock y umbral', true],
        ['u_ship_dims', 'Peso y dimensiones de envío', false],
        ['u_iva', 'IVA del producto padre', false],
    ],
    'Datos del catálogo' => [
        ['u_internal_code', 'Código interno', true],
        ['u_name', 'Nombre del producto', false],
        ['u_brand', 'Marca', false],
        ['u_supplier', 'Proveedor', false],
        ['u_category', 'Categoría', false],
        ['u_desc', 'Descripciones corta y larga', false],
        ['u_publish', 'Estado de publicación', false],
    ],
    'Variantes' => [
        ['u_attrs', 'Atributos de variantes (color, tamaño, etc.)', true],
    ],
];
?>
<section class="av3-ui-card av3-imports__board" aria-labelledby="imports-upload-title">
  <div class="av3-imports__board-head">
    <div>
      <h2 id="imports-upload-title">Importar productos y variantes</h2>
      <p>Seleccioná el archivo y definí qué datos se pueden modificar.</p>
    </div>
    <div class="av3-ui-actions av3-imports__resources">
      <a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/imports/template')) ?>">Descargar plantilla CSV</a>
      <a class="av3-ui-btn av3-ui-btn--small" href="<?= e(url('/admin/imports/export')) ?>">Exportar catálogo</a>
    </div>
  </div>

  <form class="av3-imports__form" method="post" enctype="multipart/form-data" action="<?= e(url('/admin/imports/preview')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="job_type" value="products">
    <input type="hidden" name="delimiter" value="auto">
    <input type="hidden" name="create_missing_products_present" value="1">

    <div class="av3-imports__file-area">
      <div class="av3-imports__section-intro">
        <div>
          <h3>Archivo CSV <span class="av3-imports__required">Obligatorio</span></h3>
          <p>Productos y variantes en un mismo archivo. Las variantes se relacionan mediante <code>sku_padre</code>.</p>
        </div>
      </div>
      <div class="av3-imports__file-box">
        <span class="av3-imports__file-mark" aria-hidden="true">CSV</span>
        <div class="av3-imports__file-content av3-ui-field">
          <label class="av3-imports__file-label" for="import_csv_file">Seleccionar archivo para analizar</label>
          <input id="import_csv_file" type="file" name="file" class="av3-ui-control av3-imports__file-input" accept=".csv,text/csv" required aria-describedby="imports-file-help">
          <p id="imports-file-help" class="av3-imports__hint">CSV UTF-8, máximo 10 MB. La plantilla oficial tiene 32 columnas, separadas por punto y coma (;), y es compatible con Excel.</p>
        </div>
      </div>
    </div>

    <div class="av3-imports__section">
      <div class="av3-imports__section-intro">
        <div>
          <h3>Creación automática</h3>
          <p>Autorizá qué registros nuevos se pueden generar cuando no existan en el catálogo.</p>
        </div>
        <span class="av3-imports__optional">Opcional</span>
      </div>
      <div class="av3-imports__option-grid">
        <?php foreach ($creationOptions as [$field, $text, $checked]): ?>
          <label class="av3-imports__check">
            <input type="checkbox" name="<?= e($field) ?>" value="1" <?= $checked ? 'checked' : '' ?>>
            <span><?= e($text) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="av3-imports__hint">El CUIT no es obligatorio para crear proveedores por nombre. Las entidades no autorizadas no se crearán.</p>
    </div>

    <div class="av3-imports__section">
      <div class="av3-imports__section-intro">
        <div>
          <h3>Campos que se pueden actualizar</h3>
          <p>Los campos desmarcados conservan sus valores actuales.</p>
        </div>
      </div>
      <div class="av3-imports__groups">
        <?php foreach ($updateGroups as $group => $options): ?>
          <fieldset class="av3-imports__group">
            <legend><?= e($group) ?></legend>
            <div class="av3-imports__group-options">
              <?php foreach ($options as [$field, $text, $checked]): ?>
                <label class="av3-imports__check">
                  <input type="checkbox" name="<?= e($field) ?>" value="1" <?= $checked ? 'checked' : '' ?>>
                  <span><?= e($text) ?></span>
                </label>
                <?php if ($field === 'u_stock'): ?>
                  <label class="av3-imports__check av3-imports__check--nested">
                    <input type="checkbox" name="stock_mode_sum" value="1" checked>
                    <span>Sumar stock <small>(desmarcado: reemplazar)</small></span>
                  </label>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endforeach; ?>
      </div>
    </div>

    <details class="av3-imports__disclosure av3-imports__pricing">
      <summary><span><strong>Cálculo automático de precios</strong><small>Usar costo neto, IVA y margen si falta el precio</small></span><span class="av3-imports__chevron" aria-hidden="true"></span></summary>
      <div class="av3-imports__disclosure-content">
        <label class="av3-imports__check">
          <input type="checkbox" name="derive_prices" value="1">
          <span>Derivar precio final automáticamente cuando falte</span>
        </label>
        <p class="av3-imports__hint">El costo del CSV se interpreta sin IVA. Se aplica el IVA y margen configurados, respetando la regla de moneda vigente.</p>
        <div class="av3-imports__price-fields">
          <div class="av3-ui-field">
            <label class="av3-ui-field-label" for="imports-margin">Margen predeterminado (%)</label>
            <input class="av3-ui-control" id="imports-margin" type="number" step="0.01" name="default_margin_pct" placeholder="Ej.: 60">
            <small class="av3-imports__hint">La columna margen_pct tiene prioridad.</small>
          </div>
          <div class="av3-ui-field">
            <label class="av3-ui-field-label" for="imports-iva">IVA predeterminado (%)</label>
            <input class="av3-ui-control" id="imports-iva" type="number" step="0.01" name="default_iva_rate" placeholder="Ej.: 21">
            <small class="av3-imports__hint">Se usa si iva_rate está vacío.</small>
          </div>
          <label class="av3-imports__check av3-imports__check--price">
            <input type="checkbox" name="list_price_equals_price" value="1">
            <span>Completar precio de lista con el precio de venta si falta</span>
          </label>
        </div>
      </div>
    </details>

    <footer class="av3-imports__form-footer">
      <p>Primero se realiza una vista previa. <strong>Analizar no modifica el catálogo.</strong></p>
      <button type="submit" class="av3-ui-btn av3-ui-btn--primary av3-imports__primary-action">Analizar archivo</button>
    </footer>
  </form>
</section>
