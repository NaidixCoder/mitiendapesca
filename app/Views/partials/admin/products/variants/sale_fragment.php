<?php
/** @var int $productId */
/** @var array $variantsCtx */

$data = $variantsCtx ?? [];
$attrs = $data['attributes_catalog_variant'] ?? [];
$pAttrs = $data['variant_attributes'] ?? [];
$valuesByAttr = $data['values_by_attribute'] ?? [];
$selectedByAttr = $data['selected_by_attribute'] ?? [];
$selectionDerived = (bool)($data['selection_derived'] ?? false);



$valueSets = $data['value_sets'] ?? [];
$setsSupported = (bool)($data['sets_supported'] ?? false);
$anySelected = (bool)($data['any_selected'] ?? false);
$variants = $data['variants'] ?? [];
$valuesByVariant = $data['values_by_variant'] ?? [];
$obsoleteIds = $data['obsolete_ids'] ?? [];
$obsoleteWithOrders = (int)($data['obsolete_with_orders'] ?? 0);
$hasObsolete = (bool)($data['has_obsolete'] ?? (is_array($obsoleteIds) && count($obsoleteIds) > 0));

$hasVariants = (bool)($data['has_variants'] ?? (is_array($variants) && count($variants) > 0));

// Map value_id => value text per attribute for summary
$valuesTextByAttr = [];
foreach ($valuesByAttr as $aid => $rows) {
  foreach (($rows ?: []) as $r) {
    $valuesTextByAttr[(int)$aid][(int)($r['id'] ?? 0)] = (string)($r['value'] ?? '');
  }
}

$incompleteIds = (array)($data['incomplete_ids'] ?? []);
$hasIncomplete = (bool)($data['has_incomplete'] ?? (count($incompleteIds) > 0));

$collapsedDefault = $hasVariants ? true : false;
?>
<div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-5 av3-shadow-sm" data-role="vs-fragment">
  <!-- Encabezado compacto (evitar repetición con el wizard) -->
  <div class="vs-head">
    <div class="vs-head__left">
      <div class="vs-head__title">Configuración de variantes</div>
      <div class="vs-head__sub">Opciones de variante → combinaciones → SKU/precios/stock.</div>
    </div>
    <div class="vs-head__right">
      <span class="vs-head__pill">Paso 1 / 2</span>
    </div>
  </div>

  <!-- Meta (para JS) -->
  <div data-role="vs-meta-fragment">
    <div class="av3-hidden" data-role="vs-meta"
       data-has-variants="<?= $hasVariants ? '1' : '0' ?>"
       data-has-incomplete="<?= $hasIncomplete ? '1' : '0' ?>"
       data-incomplete-ids="<?= e((string)($data['incomplete_ids_csv'] ?? implode(',', $incompleteIds))) ?>"></div>
  </div>

    <div data-role="vs-warning-fragment">
    <?php section('partials/admin/products/variants/_warning', get_defined_vars()); ?>
  </div>

  <div data-role="vs-feedback-fragment">
    <?php section('partials/admin/products/variants/_feedback', get_defined_vars()); ?>
  </div>


  <!-- Modal: Importar plantilla -->
  <div class="av3-fixed inset-0 av3-z-50 av3-hidden" data-role="vs-sets-modal" aria-hidden="true">
    <div class="av3-absolute inset-0 av3-bg-slate-900-50" data-role="vs-sets-backdrop"></div>
    <div class="av3-relative av3-mx-auto av3-mt-10 av3-w--min-720px-92vw">
      <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-shadow-xl">
        <div class="av3-pad-x5 av3-pad-y4 av3-border-bottom av3-border-slate-100 av3-flex av3-items-start av3-justify-between av3-gap-3">
          <div class="av3-min-w-0">
            <div class="av3-type-base av3-weight-strong av3-text-slate-900">Importar plantilla</div>
            <div class="av3-space-top-1 av3-type-body av3-text-var-muted">Copia opciones y valores al producto. <b>No borra</b> lo existente.</div>
          </div>
          <button type="button" class="btn-ghost" data-role="vs-sets-close" aria-label="Cerrar">✕</button>
        </div>

        <div class="av3-pad-x5 av3-pad-y4">
          <?php if (!$setsSupported): ?>
            <div class="av3-radius-card av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-x4 av3-pad-y3 av3-type-body av3-text-amber-900">
              La biblioteca de plantillas no está disponible (faltan tablas <code>variant_value_sets</code>).
            </div>
          <?php elseif (empty($valueSets)): ?>
            <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-x4 av3-pad-y3 av3-type-body av3-text-slate-700">
              Todavía no hay plantillas para la <b>categoría/marca</b> de este producto.
            </div>
          <?php else: ?>
            <div class="av3-flow-2" data-role="vs-sets-list">
              <?php foreach ($valueSets as $s):
                $sid = (int)($s['id'] ?? 0);
                $sname = (string)($s['name'] ?? 'Plantilla');
                $isBrand = !empty($s['brand_id']);
                $acnt = (int)($s['attributes_count'] ?? 0);
                $icnt = (int)($s['items_count'] ?? 0);
                if (!$sid) continue;
              ?>
                <div class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-x4 av3-pad-y3 av3-hover-border-slate-300">
                  <div class="av3-min-w-0">
                    <div class="av3-flex av3-items-center av3-gap-2">
                      <div class="av3-type-body av3-weight-strong av3-text-slate-900 av3-truncate"><?= e($sname) ?></div>
                      <?php if ($isBrand): ?>
                        <span class="badge badge--ok">Marca</span>
                      <?php else: ?>
                        <span class="badge">Categoría</span>
                      <?php endif; ?>
                    </div>
                    <div class="av3-space-top-1 av3-type-caption av3-text-var-muted"><?= (int)$acnt ?> opcion(es) · <?= (int)$icnt ?> valor(es)</div>
                  </div>
                  <div class="av3-flex av3-items-center av3-gap-2">
                    <button type="button" class="btn-secondary" data-role="vs-set-import" data-set-id="<?= (int)$sid ?>">Importar</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="av3-pad-x5 av3-pad-y4 av3-border-top av3-border-slate-100 av3-flex av3-items-center av3-justify-end av3-gap-2">
          <button type="button" class="btn-secondary" data-role="vs-sets-close-2">Cerrar</button>
        </div>
      </div>
    </div>
  </div>



  <!-- Modal: Guardar como plantilla (Categoría + Marca) -->
  <div class="av3-fixed inset-0 av3-z-50 av3-hidden" data-role="vs-sets-save-modal" aria-hidden="true">
    <div class="av3-absolute inset-0 av3-bg-slate-900-50" data-role="vs-sets-save-backdrop"></div>
    <div class="av3-relative av3-mx-auto av3-mt-10 av3-w--min-560px-92vw">
      <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-shadow-xl">
        <div class="av3-pad-x5 av3-pad-y4 av3-border-bottom av3-border-slate-100 av3-flex av3-items-start av3-justify-between av3-gap-3">
          <div class="av3-min-w-0">
            <div class="av3-type-base av3-weight-strong av3-text-slate-900">Guardar como plantilla</div>
            <div class="av3-space-top-1 av3-type-body av3-text-var-muted">
              Se guardará para <b>Categoría + Marca</b> (para evitar contaminación).
            </div>
          </div>
          <button type="button" class="btn-ghost" data-role="vs-sets-save-close" aria-label="Cerrar">✕</button>
        </div>

        <div class="av3-pad-x5 av3-pad-y4 av3-flow-4">
          <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-x4 av3-pad-y3 av3-type-body av3-text-slate-700">
            Esta plantilla guardará las <b>opciones de variante</b> y los <b>valores seleccionados</b> actualmente en este producto.
          </div>

          <div>
            <label class="av3-block av3-type-body av3-weight-medium av3-text-slate-800">Nombre de plantilla</label>
            <input type="text"
                   class="av3-space-top-1 av3-width-full av3-radius-card av3-border av3-border-slate-300 av3-pad-x3 av3-pad-y2 av3-type-body focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"
                   maxlength="120"
                   placeholder="Ej: Colores y talles (Marca X)"
                   data-role="vs-set-save-name">
            <div class="av3-space-top-1 av3-type-caption av3-text-var-muted">Máximo 120 caracteres.</div>
          </div>
        </div>

        <div class="av3-pad-x5 av3-pad-y4 av3-border-top av3-border-slate-100 av3-flex av3-items-center av3-justify-end av3-gap-2">
          <button type="button" class="btn-secondary" data-role="vs-sets-save-cancel">Cancelar</button>
          <button type="button" class="btn-primary" data-role="vs-sets-save-submit">Guardar plantilla</button>
        </div>
      </div>
    </div>
  </div>


  <!-- Paso 1/2: Configuración -->
  <div class="av3-space-top-4 av3-radius-xl av3-border av3-border-slate-200 av3-bg-slate-50" data-role="vs-config">
    <div class="vs-section-head av3-pad-x4 av3-pad-y3 av3-border-bottom av3-border-slate-100">
      <div class="av3-flex av3-items-center av3-gap-2">
        <span class="badge">Paso 1</span>
        <div>
          <div class="av3-type-body av3-weight-strong av3-text-slate-900">Opciones de variante y valores</div>
          <div class="av3-type-caption av3-text-var-muted">Seleccioná solo los valores que generan variantes.</div>
        </div>
      </div>
      <div class="av3-flex av3-items-center av3-gap-2">
        <button type="button"
                class="btn-secondary <?= (!$setsSupported || empty($valueSets)) ?'av3-opacity-60 cursor-not-allowed' : '' ?>"
                data-role="vs-set-import-open"
                <?= (!$setsSupported || empty($valueSets)) ? 'disabled aria-disabled="true"' : '' ?>
                title="<?= (!$setsSupported ? 'Biblioteca de plantillas no disponible (faltan tablas).' : (empty($valueSets) ? 'No hay plantillas para esta categoría/marca.' : 'Importar una plantilla de opciones y valores')) ?>">
          Importar plantilla
        </button>
        <button type="button"
                class="btn-secondary <?= (!$setsSupported || !$anySelected) ?'av3-opacity-60 cursor-not-allowed' : '' ?>"
                data-role="vs-set-save-open"
                <?= (!$setsSupported || !$anySelected) ? 'disabled aria-disabled="true"' : '' ?>
                title="<?= (!$setsSupported ? 'Biblioteca de plantillas no disponible (faltan tablas).' : (!$anySelected ? 'Seleccioná al menos un valor para guardar una plantilla.' : 'Guardar esta selección como plantilla (Categoría + Marca).')) ?>">
          Guardar como plantilla
        </button>
        <button type="button" class="<?= $hasVariants ?'' : 'av3-hidden' ?> btn-secondary" data-role="vs-config-toggle"><?= $collapsedDefault ? 'Editar' : 'Listo' ?></button>
      </div>
    </div>

    <?php if ($hasVariants && $selectionDerived): ?>
      <div class="av3-mx-4 av3-space-top-3 av3-radius-card av3-border av3-border-emerald-200 av3-bg-emerald-50 av3-pad-x3 av3-pad-y2 av3-type-body av3-text-emerald-900">
        <b>Listo:</b> detectamos automáticamente los valores usados en tus variantes ya creadas y los marcamos.
        Si querés cambiar combinaciones, ajustá la selección y volvé a generar.
      </div>
    <?php endif; ?>

    <!-- Resumen -->
    <div class="<?= $hasVariants ?'' : 'av3-hidden' ?> av3-pad-x4 av3-pad-y3" data-role="vs-config-summary">
      <?php
      $chips = [];
      foreach (($pAttrs ?: []) as $pa) {
        $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
        if (!$aid) continue;
        $name = (string)($pa['name'] ?? '');
        $sel = $selectedByAttr[$aid] ?? [];
        $texts = [];
        foreach (($sel ?: []) as $vid) {
          $t = $valuesTextByAttr[$aid][(int)$vid] ?? '';
          if ($t !== '') $texts[] = $t;
        }
        if ($texts) {
          $chips[] = '<span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x2 av3-pad-y1 av3-type-caption"><span class="av3-weight-strong av3-mr-1 av3-text-slate-800">'.e($name).':</span><span class="av3-text-var-muted">'.e(implode(', ', $texts)).'</span></span>';
        }
      }
      echo $chips ? '<div class="av3-flex av3-flex-wrap av3-gap-2">'.implode(' ', $chips).'</div>' : '<span class="av3-type-caption av3-text-var-muted">Todavía no seleccionaste valores.</span>';
      ?>
    </div>

    <div class="av3-pad-x4 av3-pad-y4 <?= $collapsedDefault ?'av3-hidden' : '' ?>" data-role="vs-config-body">
      <div>
        <div class="av3-space-top-2 av3-flex av3-items-center av3-gap-2">
          <select class="av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body"
                  data-role="vs-attr-select">
            <option value="">Seleccionar...</option>
             <?php $attrIdsEnProducto = array_flip(array_map(static function($x){ return (int)($x['attribute_id'] ?? $x['id'] ?? 0); }, ($pAttrs ?: []))); ?>
<?php foreach (($attrs ?: []) as $a): $yaAgregado = isset($attrIdsEnProducto[(int)($a['id'] ?? 0)]); ?>
              <option value="<?= (int)($a['id'] ?? 0) ?>" <?= $yaAgregado ? 'disabled' : '' ?>><?= e((string)($a['name'] ?? '')) ?><?= $yaAgregado ? ' (ya agregado)' : '' ?></option>
<?php endforeach; ?>
          </select>
          <button type="button" class="btn-ghost whitespace-nowrap" data-role="vs-attr-quick-open">+ Crear</button>
        </div>
        <div class="av3-space-top-2 av3-type-caption av3-text-var-muted">Al seleccionar una opcion de variante, se agrega automaticamente. Si no existe, creala con "+ Crear".</div>

        <!-- Modal: Crear atributo (rápido) -->
        <div class="av3-fixed inset-0 av3-z-50 av3-hidden" data-role="vs-attr-quick-modal" aria-hidden="true">
          <div class="av3-absolute inset-0 av3-bg-slate-900-50" data-role="vs-attr-quick-backdrop"></div>
          <div class="av3-relative av3-mx-auto av3-mt-10 av3-w--min-720px-92vw">
            <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-shadow-xl">
              <div class="av3-pad-x5 av3-pad-y4 av3-border-bottom av3-border-slate-100 av3-flex av3-items-start av3-justify-between av3-gap-3">
                <div class="av3-min-w-0">
                  <div class="av3-type-base av3-weight-strong av3-text-slate-900">Crear opcion de variante</div>
                  <div class="av3-space-top-1 av3-type-body av3-text-var-muted">Creala y agregala al producto sin salir del editor.</div>
                </div>
                <button type="button" class="btn-ghost" data-role="vs-attr-quick-close" aria-label="Cerrar">✕</button>
              </div>

              <div class="av3-pad-5 av3-flow-4">
                <div>
                  <div class="av3-type-caption av3-weight-strong av3-text-slate-700 uppercase av3-tracking-wide">Nombre</div>
                  <input type="text" class="av3-space-top-2 av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body"
                         data-role="vs-attr-quick-name" placeholder="Ej: Color">
                </div>

                <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
                  <div>
                    <div class="av3-type-caption av3-weight-strong av3-text-slate-700 uppercase av3-tracking-wide">Tipo</div>
                    <select class="av3-space-top-2 av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body"
                            data-role="vs-attr-quick-type">
                      <option value="select">Seleccionable (con valores)</option>
                      <option value="text">Texto libre</option>
                      <option value="number">Número</option>
                    </select>
                    <div class="av3-space-top-2 av3-type-caption av3-text-var-muted">Para variantes, lo mas comun es "Seleccionable".</div>
                  </div>

                  <div class="av3-flex av3-items-center av3-gap-2 av3-pt-6">
                    <label class="av3-inline-flex av3-items-center av3-gap-2 av3-type-body">
                      <input type="checkbox" class="av3-radius-sm av3-border-slate-300" data-role="vs-attr-quick-add-to-product" checked>
                      <span>Agregar al producto al crear</span>
                    </label>
                  </div>
                </div>

                <div data-role="vs-attr-quick-values-wrap">
                  <div class="av3-type-caption av3-weight-strong av3-text-slate-700 uppercase av3-tracking-wide">Valores</div>
                  <div class="av3-space-top-2 av3-flex av3-items-center av3-gap-2">
                    <input type="text" class="av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body"
                           data-role="vs-attr-quick-value" placeholder="Ej: Rojo">
                    <button type="button" class="btn-ghost whitespace-nowrap" data-role="vs-attr-quick-value-add">Agregar</button>
                  </div>
                  <div class="av3-space-top-3 av3-flex av3-flex-wrap av3-gap-2" data-role="vs-attr-quick-values"></div>
                  <input type="hidden" data-role="vs-attr-quick-values-hidden" value="">
                  <div class="av3-space-top-2 av3-type-caption av3-text-var-muted">Tip: podés pegar varios separados por coma.</div>
                </div>

                <div class="av3-type-caption av3-text-var-danger av3-hidden" data-role="vs-attr-quick-error"></div>
              </div>

              <div class="av3-pad-x5 av3-pad-y4 av3-border-top av3-border-slate-100 av3-flex av3-items-center av3-justify-end av3-gap-2">
                <button type="button" class="btn-ghost" data-role="vs-attr-quick-cancel">Cancelar</button>
                <button type="button" class="btn-brand" data-role="vs-attr-quick-save">Guardar opcion</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="av3-space-top-4 av3-flow-3" data-role="vs-attr-list">
        <?php foreach (($pAttrs ?: []) as $pa):
          $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
          if (!$aid) continue;
          $aName = (string)($pa['name'] ?? '');
          $aValues = $valuesByAttr[$aid] ?? [];
          $sel = $selectedByAttr[$aid] ?? [];
          $selSet = array_flip(array_map('intval', $sel ?: []));
        ?>
          <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4" data-role="vs-attr-box" data-attribute-id="<?= (int)$aid ?>" data-attribute-name="<?= e($aName) ?>">
            <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
              <div class="av3-min-w-0">
                <div class="av3-type-body av3-weight-strong av3-text-slate-900"><?= e($aName) ?></div>
                <div class="av3-space-top-1 av3-type-caption av3-text-var-muted">
                  <?php $selCount = is_array($sel) ? count($sel) : 0; ?>
                  <span class="av3-inline-flex av3-items-center av3-gap-1">
                    <span class="av3-inline-block av3-h-1-5 av3-w-1-5 av3-radius-full av3-bg-emerald-500-50"></span>
                    <span><?= (int)$selCount ?> seleccionado(s)</span>
                  </span>
                </div>
              </div>
              <div class="av3-flex av3-items-center av3-gap-2">
                <button type="button" class="btn-secondary" data-role="vs-value-create-open">Agregar valores</button>
                <button type="button" class="btn-ghost btn-ghost-danger" data-role="vs-attr-remove">Quitar</button>
              </div>
            </div>
            <div class="av3-space-top-3 av3-grid av3-grid-cols-1 av3-sm-grid-cols-2 av3-gap-2">
              <?php foreach (($aValues ?: []) as $v):
                $vid = (int)($v['id'] ?? 0);
                $val = (string)($v['value'] ?? '');
                $checked = isset($selSet[$vid]);
              ?>
                <div class="vs-check av3-flex av3-items-center av3-gap-2 av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body av3-hover-bg-slate-50 av3-hover-border-slate-300 av3-transition">
                  <label class="av3-flex av3-items-center av3-gap-2 av3-min-w-0 av3-flex-1">
                    <input type="checkbox" class="accent-[var(--brand)]"
                           data-role="vs-value"
                           value="<?= $vid ?>"
                           <?= $checked ? 'checked' : '' ?>>
                    <span class="av3-truncate"><?= e($val) ?></span>
                  </label>
                  <button type="button"
                          class="av3-shrink-0 av3-inline-flex av3-items-center av3-justify-center av3-radius-control av3-pad-x2 av3-pad-y1 av3-text-slate-400 av3-hover-text-rose-600 av3-hover-bg-rose-50 av3-transition"
                          title="Quitar valor de este producto"
                          aria-label="Quitar valor de este producto"
                          data-role="vs-value-remove"
                          data-attribute-id="<?= (int)$aid ?>"
                          data-value-id="<?= (int)$vid ?>"
                          data-value-label="<?= e($val) ?>">
                    🗑
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="av3-space-top-2 av3-type-caption av3-text-var-muted">Tip: para quitar un valor, destilda su checkbox. "Quitar atributo" elimina la opcion completa.</p>

            <div class="av3-space-top-2 av3-hidden" data-role="vs-value-create">
              <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-3">
                <div class="av3-type-caption av3-weight-strong av3-text-slate-700 uppercase av3-tracking-wide">Agregar valores</div>
                <div class="av3-space-top-1 av3-type-caption av3-text-var-muted">Uno por línea o separados por coma.</div>
                
                <div class="av3-space-top-3">
                  <div class="av3-type-caption av3-weight-medium av3-text-slate-700">Sugerencias rápidas</div>
                  <div class="av3-space-top-1 av3-type-caption av3-text-var-muted">Buscá en plantillas y en tu catálogo (opcional).</div>
                  <input type="text"
                         class="av3-space-top-2 av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body"
                         placeholder="Escribí para buscar…"
                         data-role="vs-value-suggest-input"
                         data-attribute-id="<?= (int)$aid ?>">
                  <div class="av3-space-top-2" data-role="vs-value-suggestions" data-attribute-id="<?= (int)$aid ?>"></div>
                </div>
                <textarea rows="3" class="av3-space-top-2 av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body" data-role="vs-value-create-input" placeholder="Ej: LOR, PAR, REH, SWT"></textarea>
                <div class="av3-space-top-2 av3-flex av3-flex-col av3-gap-2 av3-sm-flex-row av3-sm-items-center av3-sm-justify-between">
                  <div class="av3-type-caption av3-text-var-muted">Se agregan al catalogo de esta opcion y quedan seleccionados para este producto.</div>
                  <div class="av3-flex av3-items-center av3-gap-2">
                    <button type="button" class="btn-secondary" data-role="vs-value-create-cancel">Cancelar</button>
                    <button type="button" class="btn-primary" data-role="vs-value-create-save">Agregar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="av3-space-top-4 av3-flex av3-flex-col av3-gap-2 av3-sm-flex-row av3-sm-items-center av3-sm-justify-between vs-generate" data-role="vs-generate-wrap">
        <div class="av3-type-body av3-text-var-muted">
          <span data-role="vs-combo-count">Selecciona valores para generar variantes.</span>
        </div>
        <button type="button" class="btn-outline" data-role="vs-generate-btn" disabled>Generar combinaciones</button>
      </div>
    </div>
  </div>




    <div data-role="vs-editor-fragment">
    <?php section('partials/admin/products/variants/_editor', get_defined_vars()); ?>
  </div>
</div>
