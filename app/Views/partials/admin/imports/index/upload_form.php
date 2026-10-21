<section class="av3-card">
  <div class="av3-pad-4 av3-md-pad-6 av3-flow-5">

    <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-4">
      <div class="av3-flex av3-items-start av3-gap-3">
        <div class="av3-mt-0_5 av3-text-slate-600">??</div>
        <div>
          <p class="av3-weight-medium av3-text-slate-900">Importaci�n �nica (simples + variantes)</p>
          <p class="av3-type-body av3-text-slate-600 av3-space-top-1">
            Ideal para actualizar <b>precios</b>, <b>stock</b> y <b>variantes</b> sin tocar el resto del contenido.
            El <b>slug</b> se genera internamente (no va en el CSV).
          </p>
        </div>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data" action="<?= url('/admin/imports/preview') ?>" class="av3-flow-6">
      <?= csrf_field() ?>

      <div class="av3-radius-xl av3-border av3-border-slate-200 av3-pad-4 av3-md-pad-5 av3-flow-4">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
          <h2 class="av3-type-base av3-weight-strong">Archivo (CSV o Excel)</h2>
          <div class="av3-flex av3-items-center av3-gap-2">
            <a class="btn-secondary" href="<?= url('/admin/imports/template') ?>">Descargar plantilla oficial (CSV)</a>
            <a class="btn-secondary" href="<?= url('/admin/imports/export') ?>">Exportar productos actuales</a>
          </div>
        </div>

        <input type="hidden" name="job_type" value="products">
        <input type="hidden" name="delimiter" value="auto">
        <p class="av3-type-body av3-text-slate-600">Un solo archivo: filas <b>producto</b> y filas <b>variante</b> (con <b>sku_padre</b>).</p>

        <div class="av3-radius-xl av3-border-2 av3-border-dashed av3-border-slate-200 av3-bg-slate-50 av3-pad-4 av3-md-pad-5">
          <div class="av3-flex av3-flex-col av3-md-flex-row av3-md-items-center av3-md-justify-between av3-gap-3">
            <div>
              <div class="av3-weight-medium av3-text-slate-900">Seleccion� el archivo CSV</div>
              <div class="av3-type-body av3-text-slate-600 av3-space-top-1">Formato recomendado: <b>UTF-8</b>. Plantilla lista para Excel.</div>
            </div>
            <div class="av3-md-min-w--320px"><input id="import_csv_file" type="file" name="file" class="input av3-width-full" accept=".csv,text/csv" required></div>
          </div>
        </div>
      </div>

      <div class="av3-radius-xl av3-border av3-border-slate-200 av3-pad-4 av3-md-pad-5 av3-flow-3">
        <div>
          <h2 class="av3-type-base av3-weight-strong">Creaci�n autom�tica (opcional)</h2>
          <p class="av3-type-body av3-text-slate-600 av3-space-top-1">Si una marca/categor�a no existe y no activ�s estas opciones, la importaci�n <b>falla</b> (no se aplica nada).</p>
        </div>

        <div class="av3-grid av3-md-grid-cols-2 av3-gap-2">
          <input type="hidden" name="create_missing_products_present" value="1">
          <label class="av3-inline-flex av3-items-start av3-gap-2 av3-md-col-span-2"><input type="checkbox" name="create_missing_products" value="1" class="av3-space-top-1" checked><span><b>Crear producto</b> si no existe el c�digo (SKU)</span></label>
          <label class="av3-inline-flex av3-items-start av3-gap-2"><input type="checkbox" name="create_missing_brands" value="1" class="av3-space-top-1"><span><b>Crear marcas</b> si no existen (por nombre)</span></label>
          <label class="av3-inline-flex av3-items-start av3-gap-2"><input type="checkbox" name="create_missing_suppliers" value="1"><span><b>Crear proveedores</b> si no existen (por nombre)</span></label>
          <label class="av3-inline-flex av3-items-start av3-gap-2"><input type="checkbox" name="create_missing_categories" value="1" class="av3-space-top-1"><span><b>Crear categor�as</b> si no existen (por nombre)</span></label>
          <label class="av3-inline-flex av3-items-start av3-gap-2 av3-md-col-span-2"><input type="checkbox" name="allow_parent_from_variants" value="1" class="av3-space-top-1"><span><b>Crear producto padre</b> desde filas <b>variante</b> (usa <code>sku_padre</code>)</span></label>
        </div>
      </div>

      <div class="av3-radius-xl av3-border av3-border-slate-200 av3-pad-4 av3-md-pad-5 av3-flow-3">
        <div>
          <h2 class="av3-type-base av3-weight-strong">Qu� se va a actualizar</h2>
          <p class="av3-type-body av3-text-slate-600 av3-space-top-1">?? Solo se modificar�n los campos seleccionados. Lo dem�s <b>no se toca</b>.</p>
        </div>

        <div class="av3-grid av3-md-grid-cols-3 av3-gap-4">
          <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
            <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Operativo</div>
            <div class="av3-flow-2">
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_cost" value="1" checked> <span>Costo</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_price" value="1" checked> <span>Precio</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_wholesale_overrides" value="1"> <span>Precio mayorista (override)</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_list_price" value="1"> <span>Precio lista</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_stock" value="1" checked> <span>Stock + umbral</span></label>
              <div class="av3-ml-6 -mt-1 av3-type-body av3-text-slate-600">
                <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="stock_mode_sum" value="1" checked><span>Sumar stock (si desmarc�s, <b>reemplaza</b> el stock)</span></label>
                <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_ship_dims" value="1"> <span>Dimensiones de env�o (peso y medidas)</span></label>
              </div>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_iva" value="1"> <span>IVA (solo producto padre)</span></label>
            </div>
          </div>

          <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
            <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Cat�logo</div>
            <div class="av3-flow-2">
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_internal_code" value="1" checked> <span>C�digo interno</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_name" value="1"> <span>Nombre / t�tulo</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_brand" value="1"> <span>Marca</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_supplier" value="1"> <span>Proveedor</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_category" value="1"> <span>Categor�a</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_desc" value="1"> <span>Descripciones (corta/larga)</span></label>
              <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_publish" value="1"> <span>Publicaci�n (activo)</span></label>
            </div>
          </div>

          <div class="av3-radius-card av3-border av3-border-slate-200 av3-pad-3">
            <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Variantes</div>
            <div class="av3-flow-2"><label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="u_attrs" value="1" checked> <span>Atributos de variantes (Color/Tama�o, etc.)</span></label></div>
            <p class="help av3-space-top-2">Si import�s variantes, esto evita �sobrantes� al recrear relaciones atributo/valor.</p>
          </div>
        </div>
      </div>

      <details class="av3-radius-xl av3-border av3-border-slate-200 av3-pad-4 av3-md-pad-5">
        <summary class="av3-cursor-pointer av3-select-none"><span class="av3-type-base av3-weight-strong">C�lculo autom�tico de precios (opcional)</span><span class="av3-type-body av3-text-slate-600 av3-ml-2">Derivar costo/precio cuando falten</span></summary>
        <div class="av3-space-top-4 av3-flow-3">
          <p class="help">Si tu proveedor te da <b>costo sin IVA</b> + <b>IVA</b>, pod�s derivar el precio final con un margen. Si no, carg� precio directamente.</p>
          <label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="derive_prices" value="1"><span>Derivar <b>precio final</b> autom�ticamente cuando falte (usa costo sin IVA + IVA + margen)</span></label>
          <div class="av3-grid av3-md-grid-cols-3 av3-gap-3">
            <div><label class="label">Margen por defecto (%)</label><input class="input" type="number" step="0.01" name="default_margin_pct" placeholder="Ej: 60"><p class="help">Si una fila trae <code>margen_pct</code>, gana esa.</p></div>
            <div><label class="label">IVA por defecto (%)</label><input class="input" type="number" step="0.01" name="default_iva_rate" placeholder="Ej: 21"><p class="help">Se usa si la fila no trae <code>iva_rate</code>.</p></div>
            <div class="av3-flex av3-items-end"><label class="av3-inline-flex av3-items-center av3-gap-2"><input type="checkbox" name="list_price_equals_price" value="1"><span>Si falta <b>precio_lista</b>, copiar desde <b>precio</b></span></label></div>
          </div>
        </div>
      </details>

      <div class="av3-flex av3-items-center av3-justify-end av3-gap-3"><button class="btn-primary" type="submit">Importar CSV</button></div>
    </form>
  </div>
</section>
