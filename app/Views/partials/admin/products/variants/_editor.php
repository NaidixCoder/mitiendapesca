<?php
// Editor de variantes (Paso 2)
// Server-render (sin HTML desde JS). JS solo para comportamiento.

if (!function_exists('pv_compact_label')) {
  function pv_compact_label(int $vid, array $pAttrs, array $valuesByVariant): string {
    $vals = $valuesByVariant[$vid] ?? [];
    $map = [];
    foreach (($vals ?: []) as $v) {
      $aid = (int)($v['attribute_id'] ?? 0);
      if ($aid) $map[$aid] = (string)($v['value'] ?? '');
    }
    $out = [];
    foreach (($pAttrs ?: []) as $pa) {
      $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
      if (!$aid) continue;
      $t = trim((string)($map[$aid] ?? ''));
      if ($t !== '') $out[] = $t;
    }
    if (!$out && $vals) {
      foreach ($vals as $v) {
        $t = trim((string)($v['value'] ?? ''));
        if ($t !== '') $out[] = $t;
      }
    }
    return implode(' - ', $out);
  }
}
?>

<?php
$compactByVariant = is_array(($variantsCtx['compact_label_by_variant'] ?? null)) ? $variantsCtx['compact_label_by_variant'] : [];
$variantTagCatalog = is_array(($variantsCtx['variant_tag_catalog'] ?? null)) ? $variantsCtx['variant_tag_catalog'] : [];
$variantTagsByVariant = is_array(($variantsCtx['variant_tags_by_variant'] ?? null)) ? $variantsCtx['variant_tags_by_variant'] : [];
$variantTagSummaryByVariant = is_array(($variantsCtx['variant_tag_summary_by_variant'] ?? null)) ? $variantsCtx['variant_tag_summary_by_variant'] : [];
// --- IVA heredado del producto (Regla de Oro IVA)
// _editor.php puede ser incluido desde distintos fragmentos, así que definimos defaults robustos.
$__ivaRate = 0.0;
if (isset($productIvaRate) && $productIvaRate !== null) {
  $__ivaRate = (float)$productIvaRate;
} elseif (isset($variantsCtx) && is_array($variantsCtx) && array_key_exists('product_iva_rate', $variantsCtx) && $variantsCtx['product_iva_rate'] !== null) {
  $__ivaRate = (float)$variantsCtx['product_iva_rate'];
} elseif (isset($product) && is_array($product) && array_key_exists('iva_rate', $product) && $product['iva_rate'] !== null) {
  $__ivaRate = (float)$product['iva_rate'];
}
$productIvaRate = $__ivaRate;

// Normalizamos: en DB guardamos factor (0.21), pero por compat puede venir porcentaje (21).
$__ivaFactor = ($productIvaRate > 1.0) ? ($productIvaRate / 100.0) : $productIvaRate;

if (!isset($productIvaPct) || $productIvaPct === null) {
  $productIvaPct = $__ivaFactor * 100.0;
} else {
  $productIvaPct = (float)$productIvaPct;
}

$productIvaRate = $__ivaFactor; // a partir de acá usamos SIEMPRE factor (0.21 / 0.105)

?>

<?php
// --- Ganancia (%) global (margen) - se usa como default para variantes
$__marginRaw = null;
if (isset($productMarginPct) && $productMarginPct !== null && $productMarginPct !== '') {
  $__marginRaw = $productMarginPct;
} elseif (isset($variantsCtx) && is_array($variantsCtx) && array_key_exists('product_margin_pct', $variantsCtx) && $variantsCtx['product_margin_pct'] !== null && $variantsCtx['product_margin_pct'] !== '') {
  $__marginRaw = $variantsCtx['product_margin_pct'];
} elseif (isset($product) && is_array($product) && array_key_exists('margin_pct', $product) && $product['margin_pct'] !== null && $product['margin_pct'] !== '') {
  $__marginRaw = $product['margin_pct'];
}

$__marginNum = null;
try { if ($__marginRaw !== null && $__marginRaw !== '') $__marginNum = (float)$__marginRaw; } catch (\Throwable $e) { $__marginNum = null; }

// Normalizamos: en DB queremos factor (0.35 / 1.5), pero por compat puede venir porcentaje (35 / 150)
$__marginFactor = null;
if ($__marginNum !== null) {
  $__marginFactor = ($__marginNum > 2.0) ? ($__marginNum / 100.0) : $__marginNum;
  if ($__marginFactor < 0) $__marginFactor = 0.0;
}

$marginDisplay = ($__marginFactor === null) ? '' : rtrim(rtrim(number_format($__marginFactor * 100.0, 2, '.', ''), '0'), '.');
?>

<div class="av3-space-top-4 av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-5 av3-shadow-sm pv-editor" data-role="vs-editor">
  <div class="av3-flex av3-flex-col av3-gap-2 av3-sm-flex-row av3-sm-items-center av3-sm-justify-between">
    <div class="av3-flex av3-items-center av3-gap-2">
      <span class="badge">Paso 2</span>
      <div>
        <h4 class="av3-type-body av3-weight-strong av3-text-slate-900">Completar y guardar</h4>
        <div class="av3-type-caption av3-text-var-muted">Editá SKU, precios, costo y stock por variante.</div>
  <div class="av3-space-top-2 av3-inline-flex av3-items-center av3-gap-2 av3-type-caption">
    <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-border av3-border-slate-200 av3-bg-slate-50 av3-text-var-muted" data-role="vs-iva-chip">IVA del producto: <?= e(rtrim(rtrim(number_format($productIvaPct, 1, '.', ''), '0'), '.')) ?>%</span>
    <span class="av3-text-var-muted">Se aplica a todas las variantes (heredado).</span>
  </div>

<div class="av3-space-top-2 av3-flex av3-flex-col av3-gap-2 av3-sm-flex-row av3-sm-items-center av3-sm-justify-between">
  <div class="av3-type-caption av3-text-var-muted">
    Podés cambiar el IVA del producto. Al guardar, se recalculan precios manteniendo margen/markup.
  </div>
  <div class="av3-flex av3-items-center av3-gap-2">
<?php
  // En esta pantalla el IVA del producto siempre es una de las alícuotas soportadas.
  // Evitamos "default" vacío porque el endpoint JSON requiere un valor y genera confusión.
  $vr = (float)$productIvaRate; // ya viene normalizado como factor (0.21)
  $vp = (string)round((($vr > 1) ? $vr : ($vr * 100)), 2);
?>
    <select class="input av3-w--140px" data-role="vs-iva-select">
      <option value="0" <?= $vp==='0' || $vp==='0.00' ? 'selected' : '' ?>>0%</option>
      <option value="10.5" <?= $vp==='10.5' || $vp==='10.50' ? 'selected' : '' ?>>10,5%</option>
      <option value="21" <?= $vp==='21' || $vp==='21.00' ? 'selected' : '' ?>>21%</option>
    </select>
    <button type="button" class="av3-pad-x3 av3-pad-y2 av3-radius-card av3-bg-black av3-text-white av3-type-body av3-hover-bg-slate-800" data-role="vs-iva-save">
      Guardar IVA
    </button>
  </div>
</div>

      </div>
    </div>
  </div>

  <?php if (!$hasVariants): ?>
    <div class="av3-space-top-4 av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-5">
      <div class="av3-type-body av3-weight-medium av3-text-slate-800">Todavía no hay variantes generadas</div>
      <p class="av3-space-top-1 av3-type-body av3-text-var-muted">Elegí opciones de variante y valores, luego presioná <b>Generar combinaciones</b>.</p>
      <div class="av3-space-top-3 av3-flex av3-flex-wrap av3-gap-2">
        <button type="button" class="btn-primary" data-role="vs-config-open">Configurar opciones</button>
      </div>
    </div>
  <?php else: ?>

    <!-- Acciones masivas (compacto tipo barra) -->
    <div class="av3-space-top-4 pv-bulk" data-role="vs-bulk">
      <div class="pv-bulk__bar">
        <div class="pv-bulk__title">
          <span class="badge badge-info">Acciones masivas</span>
          <span class="pv-bulk__hint">Completá una vez y aplicá. “Solo vacías” no pisa valores ya cargados.</span>
        </div>

        <div class="pv-bulk__actions">
          <button type="button" class="btn-outline" data-role="vs-bulk-apply-empty">Solo vacías</button>
          <button type="button" class="btn-primary" data-role="vs-bulk-apply-all">Aplicar a todas</button>
        </div>
      </div>

      <?php
        $bulkFields = [
          ['field' => 'cost', 'label' => 'Costo (sin IVA)', 'step' => '0.01', 'placeholder' => 'Ej: 699.99'],
          ['field' => 'margin_pct', 'label' => 'Ganancia (%)', 'step' => '', 'placeholder' => 'Ej: 35 o 150'],
          ['field' => 'price', 'label' => 'Precio', 'step' => '0.01', 'placeholder' => 'Ej: 999.99'],
          ['field' => 'list_price', 'label' => 'Precio lista', 'step' => '0.01', 'placeholder' => 'Ej: 1299.99'],
          ['field' => 'stock', 'label' => 'Stock', 'step' => '1', 'placeholder' => 'Ej: 10'],
          ['field' => 'low_stock_threshold', 'label' => 'Umbral', 'step' => '1', 'placeholder' => 'Ej: 3'],
        ];
        
      ?>

      <div class="pv-bulk__fields av3-grid av3-grid-cols-2 av3-sm-grid-cols-3 av3-lg-grid-cols-6 av3-gap-3 av3-space-top-3">
        <?php foreach ($bulkFields as $bf):
          $isMoney = in_array($bf['field'], ['price','list_price','cost'], true);
          $isPct = ($bf['field'] === 'margin_pct');
        ?>
          <label class="pv-bulk__field av3-width-full av3-max-w--180px">
            <span class="pv-bulk__label"><?= e($bf['label']) ?></span>
            <input
              class="pv-input pv-input--slim <?= $isMoney ?'pv-money' : 'pv-num' ?> no-spin"
              type="<?= ($isMoney || $isPct) ? 'text' : 'number' ?>"
              inputmode="<?= ($isMoney || $isPct) ? 'decimal' : 'numeric' ?>"
              step="<?= (!$isMoney && !$isPct) ? e($bf['step']) : '' ?>"
              placeholder="<?= e($bf['placeholder']) ?>"
              <?php if ($isPct): ?>value="<?= e($marginDisplay) ?>"<?php endif; ?>
              data-role="vs-bulk-value"
              data-field="<?= e($bf['field']) ?>"
            >
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Desktop: tabla -->
    <div class="av3-space-top-4 av3-hidden av3-md-block pv-table-wrap">
      <!--
        Importante: NO usar av3-overflow-hidden en el contenedor de la tabla.
        Si no, el menú de acciones (â‹¯) queda recortado dentro de la tabla.
      -->
      <div class="pv-table-shell av3-radius-card av3-border av3-border-slate-200 av3-surface-white">
        <!-- Scroll lateral: la tabla puede crecer mucho. Ojo: el menú â‹¯ se portaliza a <body> (JS) para no quedar recortado. -->
        <div class="pv-table-scroll av3-overflow-x-auto">
          <table class="av3-width-full av3-min-w--1200px av3-type-body table-fixed pv-table pv-table--premium">
          <thead>
            <tr class="av3-type-caption av3-text-slate-600">
              <th class="av3-text-left av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--120px">Variante</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--135px">SKU</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--130px">Costo (sin IVA)</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--110px">Ganancia</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--120px">Precio</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--130px">Precio lista</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--85px">Stock</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--85px">Umbral</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--95px">Peso (g)</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--85px">Alto</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--85px">Ancho</th>
              <th class="av3-text-center av3-font-normal av3-py-2_5 av3-pad-x2 av3-w--85px">Largo</th>
            </tr>
          </thead>
        <tbody>
          <?php foreach ($variants as $v):
            $vid = (int)($v['id'] ?? 0);
            if (!$vid) continue;
            $label = (string)($compactByVariant[$vid] ?? pv_compact_label($vid, $pAttrs, $valuesByVariant));
            $variantTagIds = array_values(array_unique(array_map('intval', (array)($variantTagsByVariant[$vid] ?? []))));
            $variantTagSummary = is_array($variantTagSummaryByVariant[$vid] ?? null) ? $variantTagSummaryByVariant[$vid] : ['count' => 0, 'text' => '', 'labels' => []];
            $sku = (string)($v['sku'] ?? '');
            $price = ($v['price'] ?? null);
            $list = ($v['list_price'] ?? null);
            $cost = ($v['cost'] ?? null);
            $mRaw = ($v['margin_pct'] ?? null);
            $mPct = '';
            if ($mRaw !== null && $mRaw !== '') {
              $mNum = (float)$mRaw;
              $mFactor = ($mNum > 2.0) ? ($mNum / 100.0) : $mNum;
              $mPct = rtrim(rtrim(number_format($mFactor * 100.0, 2, '.', ''), '0'), '.');
            }
            $stock = (int)($v['stock'] ?? 0);
            $low = (int)($v['low_stock_threshold'] ?? 3);
            $active = (int)($v['is_active'] ?? 1);
            $sw = isset($v['ship_weight']) ? (int)$v['ship_weight'] : 0;
            $sh = isset($v['ship_height']) ? (int)$v['ship_height'] : 0;
            $swi = isset($v['ship_width'])  ? (int)$v['ship_width']  : 0;
            $sl = isset($v['ship_length']) ? (int)$v['ship_length'] : 0;
            $orders = (int)($v['orders_count'] ?? 0);
            $isIncomplete = in_array($vid, $incompleteIds, true);
$isObsolete = (int)($v['is_obsolete'] ?? 0) === 1;
            $canDelete = $orders <= 0;
          ?>
            <tr class="av3-border-top av3-border-slate-100" data-role="vs-row" data-variant-id="<?= $vid ?>" data-orig-tag-ids="<?= e(implode(',', $variantTagIds)) ?>">
              <td class="av3-py-2_5 av3-pad-x2 av3-text-slate-800 pv-variant-cell">
                <div class="av3-flex av3-items-start av3-justify-between av3-gap-2">
                  <div class="av3-min-w-0">
                    <?php
                      // No truncamos: mostramos 2 líneas (más premium y legible).
                      $parts = preg_split('/\s*-\s*/', (string)$label);
                      $main = trim((string)array_shift($parts));
                      $sub = trim(implode(' · ', array_filter(array_map('trim', $parts))));
                    ?>
                    <div class="pv-variant-label" title="<?= e($label) ?>">
                      <div class="pv-variant-label__main"><?= e($main) ?></div>
                      <?php if ($sub !== ''): ?>
                        <div class="pv-variant-label__sub"><?= e($sub) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="av3-space-top-1 av3-flex av3-flex-wrap av3-gap-1">
                      <?php if ($active === 0): ?>
                        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-600">Pausada</span>
                      <?php endif; ?>
                      <?php if ($orders > 0): ?>
                        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-700">Con pedidos</span>
                      <?php endif; ?>
                      <?php if ($isIncomplete): ?>
                        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-amber-800">Incompleta</span>
                      <?php endif; ?>
<?php if ($isObsolete): ?>
                        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-700">Sobrante</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="av3-relative av3-shrink-0">
                    <button type="button" class="av3-radius-control av3-pad-x2 av3-pad-y1 av3-text-slate-500 av3-hover-bg-slate-100" data-role="vs-row-actions-toggle" aria-label="Acciones">â‹¯</button>
                    <div class="av3-hidden av3-absolute right-0 av3-space-top-1 av3-w-44 av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-shadow-lg av3-p-1 av3-z-50" data-role="vs-row-menu">
                      <button type="button" class="av3-width-full av3-text-left av3-radius-control av3-pad-x3 av3-pad-y2 av3-type-body av3-hover-bg-slate-50" data-role="vs-row-action-toggle" data-next-active="<?= $active ? '0' : '1' ?>">
                        <?= $active ? 'Pausar' : 'Activar' ?>
                      </button>
                      <button type="button" class="av3-width-full av3-text-left av3-radius-control av3-pad-x3 av3-pad-y2 av3-type-body av3-hover-bg-slate-50 av3-text-red-700 <?= $canDelete ?'' : 'av3-opacity-50 cursor-not-allowed' ?>" data-role="vs-row-action-delete" data-can-delete="<?= $canDelete ? '1' : '0' ?>">
                        Eliminarâ€¦
                      </button>
                    </div>
                  </div>
                </div>
              </td>
              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-sku av3-width-full" data-field="sku" value="<?= e($sku) ?>" data-orig="<?= e($sku) ?>" placeholder="SKU proveedor">
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-money" data-field="cost" inputmode="decimal" type="text" value="<?= e((string)($cost ?? '')) ?>" data-orig="<?= e((string)($cost ?? '')) ?>" placeholder="$">
                <?php $cf = null; if ($productIvaRate > 0 && is_numeric($cost)) { $cf = (float)$cost * (1 + $productIvaRate); } ?>
                <div class="av3-type-micro av3-text-var-muted av3-space-top-1" data-role="vs-cost-hint">IVA: <?= e(rtrim(rtrim(number_format($productIvaPct, 1, '.', ''), '0'), '.')) ?>% · Final: <?= $cf === null ? '—' : e(number_format($cf, 2, '.', '')) ?></div>
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="margin_pct" inputmode="decimal" type="text" value="<?= e((string)($mPct ?? '')) ?>" data-orig="<?= e((string)($mPct ?? '')) ?>" placeholder="Ej: 35">
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-money" data-field="price" inputmode="decimal" type="text" value="<?= e((string)($price ?? '')) ?>" data-orig="<?= e((string)($price ?? '')) ?>" placeholder="$">
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-money" data-field="list_price" inputmode="decimal" type="text" value="<?= e((string)($list ?? '')) ?>" data-orig="<?= e((string)($list ?? '')) ?>" placeholder="$">
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="stock" inputmode="numeric" type="number" step="1" value="<?= (int)$stock ?>" data-orig="<?= (int)$stock ?>">
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="low_stock_threshold" inputmode="numeric" type="number" step="1" value="<?= (int)$low ?>" data-orig="<?= (int)$low ?>">
              </td>

              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="ship_weight" inputmode="numeric" type="number" min="1" step="1" value="<?= $sw > 0 ? (int)$sw : '' ?>" data-orig="<?= $sw > 0 ? (int)$sw : '' ?>" placeholder="(auto)">
              </td>
              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="ship_height" inputmode="numeric" type="number" min="1" step="1" value="<?= $sh > 0 ? (int)$sh : '' ?>" data-orig="<?= $sh > 0 ? (int)$sh : '' ?>" placeholder="(auto)">
              </td>
              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="ship_width" inputmode="numeric" type="number" min="1" step="1" value="<?= $swi > 0 ? (int)$swi : '' ?>" data-orig="<?= $swi > 0 ? (int)$swi : '' ?>" placeholder="(auto)">
              </td>
              <td class="av3-pad-y2 av3-pad-x2">
                <input class="pv-input pv-num no-spin" data-field="ship_length" inputmode="numeric" type="number" min="1" step="1" value="<?= $sl > 0 ? (int)$sl : '' ?>" data-orig="<?= $sl > 0 ? (int)$sl : '' ?>" placeholder="(auto)">
              </td>
            </tr>
            <?php if ($variantTagCatalog !== []): ?>
            <tr class="av3-border-top av3-border-slate-50">
              <td colspan="12" class="av3-pad-x2 av3-pb-3 av3-pt-0">
                <details class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50-70">
                  <summary class="av3-flex av3-cursor-pointer list-none av3-items-center av3-justify-between av3-gap-3 av3-pad-x3 av3-pad-y2 av3-type-body">
                    <span class="av3-weight-medium av3-text-slate-800">Tags de catalogo</span>
                    <span class="av3-type-caption av3-text-var-muted">
                      <?= (int)($variantTagSummary['count'] ?? 0) > 0 ? e((string)($variantTagSummary['text'] ?? '')) : 'Sin tags especificos' ?>
                    </span>
                  </summary>
                  <div class="av3-grid av3-gap-3 av3-border-top av3-border-slate-200 av3-pad-x3 av3-pad-y3 av3-md-grid-cols-2">
                    <?php foreach ($variantTagCatalog as $group): ?>
                      <?php $groupName = (string)($group['name'] ?? 'Grupo'); $groupTags = is_array($group['tags'] ?? null) ? $group['tags'] : []; ?>
                      <div class="av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-3">
                        <div class="av3-type-caption av3-weight-strong uppercase av3-tracking-wide av3-text-slate-700"><?= e($groupName) ?></div>
                        <div class="av3-space-top-2 av3-grid av3-gap-2">
                          <?php foreach ($groupTags as $tag): ?>
                            <?php $tagId = (int)($tag['id'] ?? 0); if ($tagId <= 0) continue; ?>
                            <label class="av3-flex av3-items-center av3-gap-2 av3-type-body av3-text-slate-800">
                              <input type="checkbox" data-role="vs-variant-tag" value="<?= $tagId ?>" <?= in_array($tagId, $variantTagIds, true) ? 'checked' : '' ?>>
                              <span><?= e((string)($tag['name'] ?? '')) ?></span>
                            </label>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </details>
              </td>
            </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Mobile: cards -->
    <div class="av3-space-top-4 av3-md-hidden av3-flow-3">
      <?php foreach ($variants as $v):
        $vid = (int)($v['id'] ?? 0);
        if (!$vid) continue;
        $label = (string)($compactByVariant[$vid] ?? pv_compact_label($vid, $pAttrs, $valuesByVariant));
        $variantTagIds = array_values(array_unique(array_map('intval', (array)($variantTagsByVariant[$vid] ?? []))));
        $variantTagSummary = is_array($variantTagSummaryByVariant[$vid] ?? null) ? $variantTagSummaryByVariant[$vid] : ['count' => 0, 'text' => '', 'labels' => []];
        $sku = (string)($v['sku'] ?? '');
        $price = ($v['price'] ?? null);
        $list = ($v['list_price'] ?? null);
        $cost = ($v['cost'] ?? null);
        $mRaw = ($v['margin_pct'] ?? null);
        $mPct = '';
        if ($mRaw !== null && $mRaw !== '') {
          $mNum = (float)$mRaw;
          $mFactor = ($mNum > 2.0) ? ($mNum / 100.0) : $mNum;
          $mPct = rtrim(rtrim(number_format($mFactor * 100.0, 2, '.', ''), '0'), '.');
        }
        $stock = (int)($v['stock'] ?? 0);
        $low = (int)($v['low_stock_threshold'] ?? 3);
        $active = (int)($v['is_active'] ?? 1);
        $sw = isset($v['ship_weight']) ? (int)$v['ship_weight'] : 0;
        $sh = isset($v['ship_height']) ? (int)$v['ship_height'] : 0;
        $swi = isset($v['ship_width'])  ? (int)$v['ship_width']  : 0;
        $sl = isset($v['ship_length']) ? (int)$v['ship_length'] : 0;
        $orders = (int)($v['orders_count'] ?? 0);
        $isIncomplete = in_array($vid, $incompleteIds, true);
$isObsolete = (int)($v['is_obsolete'] ?? 0) === 1;
        $canDelete = $orders <= 0;
      ?>
        <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-3" data-role="vs-row" data-variant-id="<?= $vid ?>" data-orig-tag-ids="<?= e(implode(',', $variantTagIds)) ?>">
          <div class="av3-flex av3-items-start av3-justify-between av3-gap-2">
            <div class="av3-min-w-0">
              <?php
                $parts = preg_split('/\s*-\s*/', (string)$label);
                $main = trim((string)array_shift($parts));
                $sub = trim(implode(' · ', array_filter(array_map('trim', $parts))));
              ?>
              <div class="pv-variant-label" title="<?= e($label) ?>">
                <div class="pv-variant-label__main"><?= e($main) ?></div>
                <?php if ($sub !== ''): ?>
                  <div class="pv-variant-label__sub"><?= e($sub) ?></div>
                <?php endif; ?>
              </div>
              <div class="av3-space-top-1 av3-flex av3-flex-wrap av3-gap-1">
                <?php if ($active === 0): ?>
                  <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-600">Pausada</span>
                <?php endif; ?>
                <?php if ($orders > 0): ?>
                  <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-700">Con pedidos</span>
                <?php endif; ?>
                <?php if ($isIncomplete): ?>
                  <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-amber-800">Incompleta</span>
                <?php endif; ?>
<?php if ($isObsolete): ?>
                        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-700">Sobrante</span>
                      <?php endif; ?>
              </div>
            </div>
            <div class="av3-relative av3-shrink-0">
              <button type="button" class="av3-radius-control av3-pad-x2 av3-pad-y1 av3-text-slate-500 av3-hover-bg-slate-100" data-role="vs-row-actions-toggle" aria-label="Acciones">â‹¯</button>
              <div class="av3-hidden av3-absolute right-0 av3-space-top-1 av3-w-44 av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-shadow-lg av3-p-1 av3-z-50" data-role="vs-row-menu">
                <button type="button" class="av3-width-full av3-text-left av3-radius-control av3-pad-x3 av3-pad-y2 av3-type-body av3-hover-bg-slate-50" data-role="vs-row-action-toggle" data-next-active="<?= $active ? '0' : '1' ?>">
                  <?= $active ? 'Pausar' : 'Activar' ?>
                </button>
                <button type="button" class="av3-width-full av3-text-left av3-radius-control av3-pad-x3 av3-pad-y2 av3-type-body av3-hover-bg-slate-50 av3-text-red-700 <?= $canDelete ?'' : 'av3-opacity-50 cursor-not-allowed' ?>" data-role="vs-row-action-delete" data-can-delete="<?= $canDelete ? '1' : '0' ?>">
                  Eliminarâ€¦
                </button>
              </div>
            </div>
          </div>
          <div class="av3-space-top-2 av3-grid av3-grid-cols-2 av3-gap-2">
            <div class="av3-col-span-2">
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">SKU</div>
              <input class="pv-input pv-sku" data-field="sku" value="<?= e($sku) ?>" data-orig="<?= e($sku) ?>" placeholder="SKU proveedor">
            </div>
            <div>
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">Precio</div>
              <input class="pv-input pv-money" data-field="price" inputmode="decimal" type="text" value="<?= e((string)($price ?? '')) ?>" data-orig="<?= e((string)($price ?? '')) ?>" placeholder="$">
            </div>
            <div>
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">Precio lista</div>
              <input class="pv-input pv-money" data-field="list_price" inputmode="decimal" type="text" value="<?= e((string)($list ?? '')) ?>" data-orig="<?= e((string)($list ?? '')) ?>" placeholder="$">
            </div>
            <div>
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">Costo (sin IVA)</div>
              <input class="pv-input pv-money" data-field="cost" inputmode="decimal" type="text" value="<?= e((string)($cost ?? '')) ?>" data-orig="<?= e((string)($cost ?? '')) ?>" placeholder="$">
                <input type="hidden" data-field="margin_pct" value="<?= e((string)($mPct ?? '')) ?>" data-orig="<?= e((string)($mPct ?? '')) ?>">
              <?php $cf = null; if ($productIvaRate > 0 && is_numeric($cost)) { $cf = (float)$cost * (1 + $productIvaRate); } ?>
              <div class="av3-type-micro av3-text-var-muted av3-space-top-1" data-role="vs-cost-hint">IVA: <?= e(rtrim(rtrim(number_format($productIvaPct, 1, '.', ''), '0'), '.')) ?>% · Final: <?= $cf === null ? '—' : e(number_format($cf, 2, '.', '')) ?></div>
            </div>
            <div>
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">Stock</div>
              <input class="pv-input pv-num no-spin" data-field="stock" inputmode="numeric" type="number" step="1" value="<?= (int)$stock ?>" data-orig="<?= (int)$stock ?>">
            </div>
            <div>
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">Umbral</div>
              <input class="pv-input pv-num no-spin" data-field="low_stock_threshold" inputmode="numeric" type="number" step="1" value="<?= (int)$low ?>" data-orig="<?= (int)$low ?>">
              <input type="hidden" data-field="is_active" value="<?= (int)$active ?>" data-orig="<?= (int)$active ?>">
            </div>

            <div class="av3-col-span-2">
              <div class="av3-type-caption av3-text-var-muted av3-space-bottom-1">Envío (opcional) — la variante puede sobrescribir al producto</div>
              <div class="av3-grid av3-grid-cols-4 av3-gap-2">
                <input class="pv-input pv-num no-spin" data-field="ship_weight" inputmode="numeric" type="number" min="1" step="1" value="<?= $sw > 0 ? (int)$sw : '' ?>" data-orig="<?= $sw > 0 ? (int)$sw : '' ?>" placeholder="Peso">
                <input class="pv-input pv-num no-spin" data-field="ship_height" inputmode="numeric" type="number" min="1" step="1" value="<?= $sh > 0 ? (int)$sh : '' ?>" data-orig="<?= $sh > 0 ? (int)$sh : '' ?>" placeholder="Alto">
                <input class="pv-input pv-num no-spin" data-field="ship_width" inputmode="numeric" type="number" min="1" step="1" value="<?= $swi > 0 ? (int)$swi : '' ?>" data-orig="<?= $swi > 0 ? (int)$swi : '' ?>" placeholder="Ancho">
                <input class="pv-input pv-num no-spin" data-field="ship_length" inputmode="numeric" type="number" min="1" step="1" value="<?= $sl > 0 ? (int)$sl : '' ?>" data-orig="<?= $sl > 0 ? (int)$sl : '' ?>" placeholder="Largo">
              </div>
              <div class="av3-type-micro av3-text-var-muted av3-space-top-1">Si lo dejás vacío, se usa el envío del producto o los valores por defecto de Ajustes.</div>
            </div>
          </div>
          <?php if ($variantTagCatalog !== []): ?>
            <details class="av3-space-top-3 av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50-70">
              <summary class="av3-flex av3-cursor-pointer list-none av3-items-center av3-justify-between av3-gap-3 av3-pad-x3 av3-pad-y2 av3-type-body">
                <span class="av3-weight-medium av3-text-slate-800">Tags de catalogo</span>
                <span class="av3-type-caption av3-text-var-muted">
                  <?= (int)($variantTagSummary['count'] ?? 0) > 0 ? e((string)($variantTagSummary['text'] ?? '')) : 'Sin tags especificos' ?>
                </span>
              </summary>
              <div class="av3-grid av3-gap-3 av3-border-top av3-border-slate-200 av3-pad-x3 av3-pad-y3">
                <?php foreach ($variantTagCatalog as $group): ?>
                  <?php $groupName = (string)($group['name'] ?? 'Grupo'); $groupTags = is_array($group['tags'] ?? null) ? $group['tags'] : []; ?>
                  <div class="av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-3">
                    <div class="av3-type-caption av3-weight-strong uppercase av3-tracking-wide av3-text-slate-700"><?= e($groupName) ?></div>
                    <div class="av3-space-top-2 av3-grid av3-gap-2">
                      <?php foreach ($groupTags as $tag): ?>
                        <?php $tagId = (int)($tag['id'] ?? 0); if ($tagId <= 0) continue; ?>
                        <label class="av3-flex av3-items-center av3-gap-2 av3-type-body av3-text-slate-800">
                          <input type="checkbox" data-role="vs-variant-tag" value="<?= $tagId ?>" <?= in_array($tagId, $variantTagIds, true) ? 'checked' : '' ?>>
                          <span><?= e((string)($tag['name'] ?? '')) ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </details>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Barra de guardado (sticky) -->
    <div class="av3-space-top-4 av3-sticky bottom-0 av3-z-10 -mx-5 av3-pad-x5 av3-pb-3 av3-pt-3 av3-border-top av3-border-slate-100 av3-bg-white-90 backdrop-blur pv-savebar" data-role="vs-savebar" data-dirty="0">
      <div class="av3-flex av3-flex-col av3-gap-2 av3-sm-flex-row av3-sm-items-center av3-sm-justify-between">
        <div class="av3-type-body av3-text-slate-700" aria-live="polite">
          Cambios sin guardar: <span class="pv-dirty-pill" data-role="vs-dirty-count" data-dirty="0">0</span>
        </div>
        <div class="av3-flex av3-items-center av3-gap-2">
          <button type="button" class="btn-primary pv-save-btn" disabled data-role="vs-save-all">Guardar variantes</button>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

