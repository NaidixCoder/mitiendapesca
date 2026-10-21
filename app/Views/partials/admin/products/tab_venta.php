<?php /** @var array $p */ /** @var array $inv */ /** @var int $id */ /** @var int $variantsCount */ ?>

<?php if ((int)$id <= 0): ?>
  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4">
    <h3 class="av3-type-base av3-weight-strong">Venta</h3>
    <p class="av3-space-top-1 av3-type-body av3-text-var-muted">
      Guardá primero los <b>Básicos</b> para crear el producto. Luego vas a poder configurar SKU, precio y stock.
    </p>
  </div>
<?php return; endif; ?>

<?php
$variantsCount = (int)($variantsCount ?? 0);
$hasVariants = ($variantsCount > 0);
$saleMode = (string)($p['sale_mode'] ?? 'simple');
if ($hasVariants) { $saleMode = 'variants'; }
$sku      = (string)($p['sku'] ?? '');
$barcode  = (string)($p['barcode'] ?? '');
$price    = isset($p['price']) ? (string)$p['price'] : '';
$listPrice= isset($p['list_price']) ? (string)$p['list_price'] : '';
$cost     = isset($p['cost']) ? (string)$p['cost'] : '';
$ivaRate  = isset($p['iva_rate']) ? (string)$p['iva_rate'] : '';
$marginPctRaw = isset($p['margin_pct']) ? (string)$p['margin_pct'] : '';
// Ganancia (%) se guarda como *factor* (ej: 0.35 = 35%, 1.50 = 150%).
// Por compatibilidad, si encontramos valores grandes (>= 10) asumimos que estaban guardados como porcentaje (35) y los normalizamos.
$marginRate = null;
if ($marginPctRaw !== '') {
  $tmp = (float)str_replace(',', '.', $marginPctRaw);
  // Heurística: valores >= 10 casi seguro son porcentaje almacenado (35 => 35%).
  $marginRate = ($tmp >= 10) ? ($tmp / 100.0) : $tmp;
  if ($marginRate < 0) { $marginRate = 0.0; }
}
$marginDisplay = ($marginRate === null) ? '' : rtrim(rtrim(number_format($marginRate * 100.0, 2, '.', ''), '0'), '.');
$stock    = (int)($inv['stock'] ?? 0);
$low      = (int)($inv['low_stock_threshold'] ?? 3);

$saleStatus = $saleStatus ?? null;

$ready = false;
$badgeText = 'Pendiente';
$readyMsg = '';
$checks = [];
$restockReactivatePrompt = ((string)($_GET['restock_reactivate'] ?? '0') === '1');

if (is_array($saleStatus)) {
  $ready = (bool)($saleStatus['ready'] ?? false);
  $badgeText = (string)($saleStatus['badge'] ?? ($ready ? 'Vendible' : 'Pendiente'));
  $readyMsg = (string)($saleStatus['message'] ?? '');
  $checks = is_array($saleStatus['checks'] ?? null) ? $saleStatus['checks'] : [];
} else {
  // Fallback (por si no llega el dato desde el controller)
  if ($saleMode === 'simple') {
    $ready = ($sku !== '' && $price !== '' && (int)$price > 0 && $stock > 0);
    $badgeText = $ready ? 'Vendible' : 'Pendiente';
    $readyMsg = $ready ? 'Producto simple listo para vender.' : 'Completá SKU, precio y stock para poder vender.';
  } else {
    // Variantes: listo para vender si existe al menos UNA variante vendible (SKU + precio + stock).
    $ready = false;
    $checks = [];
    $variants = (is_array($variantsCtx['variants'] ?? null) ? $variantsCtx['variants'] : []);
    foreach ($variants as $v) {
      $vSku = trim((string)($v['sku'] ?? ''));
      $vPrice = (int)($v['price'] ?? 0);
      $vStock = (int)($v['stock'] ?? 0);
      $vActive = (int)($v['is_active'] ?? 1);
      if ($vActive !== 1) { continue; }
      if ($vSku !== '' && $vPrice > 0 && $vStock > 0) { $ready = true; break; }
    }
    $badgeText = $ready ? 'Vendible' : 'Pendiente';
    $readyMsg = $ready
      ? 'Producto con variantes listo para vender.'
      : 'Producto con variantes: completá al menos una variante vendible (SKU, precio y stock).';
  }
}
?>

<?php
// Source of truth precomputado en ProductFormService (ETAPA 4).
$saleTab = is_array($saleTab ?? null) ? $saleTab : [];
if (!empty($saleTab)) {
  $hasVariants = (bool)($saleTab['hasVariants'] ?? $hasVariants);
  $saleMode = (string)($saleTab['saleMode'] ?? $saleMode);
  $sku = (string)($saleTab['sku'] ?? $sku);
  $barcode = (string)($saleTab['barcode'] ?? $barcode);
  $price = (string)($saleTab['price'] ?? $price);
  $listPrice = (string)($saleTab['listPrice'] ?? $listPrice);
  $cost = (string)($saleTab['cost'] ?? $cost);
  $ivaRate = (string)($saleTab['ivaRate'] ?? $ivaRate);
  $marginDisplay = (string)($saleTab['marginDisplay'] ?? $marginDisplay);
  $stock = (int)($saleTab['stock'] ?? $stock);
  $low = (int)($saleTab['low'] ?? $low);
  $ready = (bool)($saleTab['ready'] ?? $ready);
  $badgeText = (string)($saleTab['badgeText'] ?? $badgeText);
  $readyMsg = (string)($saleTab['readyMsg'] ?? $readyMsg);
  $checks = is_array($saleTab['checks'] ?? null) ? $saleTab['checks'] : $checks;
}
?>

<div class="av3-flow-4">
  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4 av3-flex av3-items-start av3-justify-between av3-gap-3">
    <div>
      <h3 class="av3-type-base av3-weight-strong">Estado de venta</h3>
      <p class="av3-space-top-1 av3-type-body av3-text-var-muted"><?= e($readyMsg) ?></p>
      <?php if (!empty($checks)): ?>
        <ul class="av3-space-top-3 av3-flow-1 av3-type-body">
          <?php foreach ($checks as $c): 
            $ok = (bool)($c['ok'] ?? false);
            $lbl = (string)($c['label'] ?? '');
          ?>
            <li class="av3-flex av3-items-center av3-gap-2">
              <span class="av3-inline-flex av3-size-h-5 av3-size-w-5 av3-items-center av3-justify-center av3-radius-full <?= $ok ?'av3-bg-green-100 av3-text-green-900' : 'av3-bg-slate-100 av3-text-slate-600' ?>">
                <?= $ok ? '✓' : '•' ?>
              </span>
              <span class="<?= $ok ?'av3-text-slate-700' : 'av3-text-slate-600' ?>"><?= e($lbl) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

    </div>
    <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-type-caption <?= $ready ?'av3-bg-green-100 av3-text-green-900' : 'av3-bg-amber-100 av3-text-amber-900' ?>">
      <?= e($badgeText) ?>
    </span>
  </div>

  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4">
    <h3 class="av3-type-base av3-weight-strong">Tipo de producto</h3>
    <input type="hidden" id="product-has-variants" value="<?= $hasVariants ? 1 : 0 ?>">
    <div class="av3-space-top-3 av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
      <label class="av3-flex av3-items-start av3-gap-3 av3-pad-3 av3-radius-card av3-border av3-border-slate-200 av3-cursor-pointer">
        <input type="radio" name="sale_mode" value="simple" <?= $saleMode === 'simple' ? 'checked' : '' ?> <?= $hasVariants ? 'disabled' : '' ?>>
        <div>
          <div class="av3-weight-medium">Producto simple</div>
          <div class="av3-type-body av3-text-var-muted">Un solo SKU, un precio y un stock.</div>
          <?php if ($hasVariants): ?>
            <div class="av3-space-top-1 av3-type-caption av3-text-amber-700">Ya hay variantes creadas: no se puede volver a “simple”.</div>
          <?php endif; ?>
        </div>
      </label>

      <label class="av3-flex av3-items-start av3-gap-3 av3-pad-3 av3-radius-card av3-border av3-border-slate-200 av3-cursor-pointer">
        <input type="radio" name="sale_mode" value="variants" <?= $saleMode === 'variants' ? 'checked' : '' ?>>
        <div>
          <div class="av3-weight-medium">Producto con variantes</div>
          <div class="av3-type-body av3-text-var-muted">SKU y stock por color/talle/gramaje.</div>
        </div>
      </label>
    </div>
  </div>

  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4" data-sale-mode="simple" >
    <h3 class="av3-type-base av3-weight-strong">Datos de venta (producto simple)</h3>

    <div class="av3-space-top-4 av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4">
      <label class="av3-block">SKU
        <input class="input av3-width-full" name="sku" value="<?= e($sku) ?>" <?= $saleMode === 'simple' ? 'required' : '' ?>>
      </label>

      <label class="av3-block">Código de barras (GTIN/EAN)
        <input class="input av3-width-full" name="barcode" value="<?= e($barcode) ?>">
      </label>
  <label class="av3-block">IVA
    <select class="input av3-width-full" name="iva_rate" data-role="sale-iva-rate">
      <?php $vr = ($ivaRate==='') ? null : (float)$ivaRate; $vp = ($vr===null) ? '' : (string)round((($vr>1)?$vr:($vr*100)), 2); ?>
      <option value="" <?= $vp==='' ? 'selected' : '' ?>>(usar default)</option>
      <option value="0" <?= $vp==='0' || $vp==='0.00' ? 'selected' : '' ?>>0%</option>
      <option value="10.5" <?= $vp==='10.5' || $vp==='10.50' ? 'selected' : '' ?>>10,5%</option>
      <option value="21" <?= $vp==='21' || $vp==='21.00' ? 'selected' : '' ?>>21%</option>
    </select>
    <p class="av3-space-top-1 av3-type-caption av3-text-var-muted">El cliente ve precio final. Esta alícuota queda guardada para facturación futura.</p>
  </label>


      <label class="av3-block">Precio contado / transferencia
        <input class="input av3-width-full" type="number" name="price" data-role="sale-price" value="<?= e($price) ?>" min="0">
        <p class="av3-space-top-1 av3-type-caption av3-text-var-muted">Es el precio que se muestra para transferencia o pago contado.</p>
      </label>

      <label class="av3-block">Precio lista / financiado
        <input class="input av3-width-full" type="number" name="list_price" value="<?= e($listPrice) ?>" min="0">
        <p class="av3-space-top-1 av3-type-caption av3-text-var-muted">Se usa como precio para tarjeta/cuotas y como base del badge de ahorro.</p>
      </label>

      <label class="av3-block">Costo proveedor (sin IVA)
        <input class="input av3-width-full" type="number" name="cost" data-role="sale-cost" value="<?= e($cost) ?>" min="0">
      </label>

      <label class="av3-block">Ganancia (%)
        <input class="input av3-width-full" type="text" inputmode="decimal" name="margin_pct" data-role="sale-margin-pct" value="<?= e($marginDisplay) ?>" placeholder="Ej: 35 o 150">
        <p class="av3-space-top-1 av3-type-caption av3-text-var-muted">Se usa para recalcular el precio cuando cambia IVA o costo.</p>
      </label>

      <?php
        $costNetoNum = (float)($cost !== '' ? $cost : 0);
        $ivaDec = null;
        if ($ivaRate !== '') {
          $tmp = (float)$ivaRate;
          $ivaDec = ($tmp > 1) ? ($tmp / 100.0) : $tmp;
        }
        $hasCostNeto = ($costNetoNum > 0);
        $hasIva = ($ivaDec !== null);
        $ivaPct = $hasIva ? ((float)$ivaDec * 100.0) : null;
        $costFinalNum = ($hasCostNeto && $hasIva) ? ($costNetoNum * (1.0 + (float)$ivaDec)) : null;
        $ivaImporteNum = ($hasCostNeto && $hasIva) ? ($costNetoNum * (float)$ivaDec) : null;
        $priceNum = (float)($price !== '' ? $price : 0);
        $markupVsFinal = ($costFinalNum && $costFinalNum > 0 && $priceNum > 0) ? (($priceNum / $costFinalNum) - 1.0) : null;
        if (!empty($saleTab)) {
          $hasCostNeto = (bool)($saleTab['hasCostNeto'] ?? $hasCostNeto);
          $hasIva = (bool)($saleTab['hasIva'] ?? $hasIva);
          $ivaPct = isset($saleTab['ivaPct']) ? (float)$saleTab['ivaPct'] : $ivaPct;
          $costFinalNum = isset($saleTab['costFinalNum']) ? (float)$saleTab['costFinalNum'] : $costFinalNum;
          $ivaImporteNum = isset($saleTab['ivaImporteNum']) ? (float)$saleTab['ivaImporteNum'] : $ivaImporteNum;
          $markupVsFinal = isset($saleTab['markupVsFinal']) ? (float)$saleTab['markupVsFinal'] : $markupVsFinal;
        }
      ?>

      <div class="av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-3" data-role="sale-iva-calc">
        <div class="av3-type-caption av3-weight-strong av3-text-slate-600">Cálculo rápido (Regla de Oro IVA)</div>
        <?php if (!$hasCostNeto): ?>
          <div class="av3-space-top-1 av3-type-body av3-text-slate-700">Cargá <b>costo sin IVA</b> para ver el costo final.</div>
        <?php elseif (!$hasIva): ?>
          <div class="av3-space-top-1 av3-type-body av3-text-slate-700">Seleccioná el <b>IVA</b> para calcular el costo final.</div>
        <?php else: ?>
          <div class="av3-space-top-2 av3-inline-flex av3-items-center av3-gap-2 av3-type-caption">
            <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-border av3-border-slate-200 av3-bg-slate-50 av3-text-var-muted">IVA: <span data-role="sale-iva-pct"><?= e(rtrim(rtrim(number_format((float)$ivaPct, 1, '.', ''), '0'), '.')) ?></span>%</span>
            <span class="av3-text-var-muted">Se aplica al costo para obtener el costo final.</span>
          </div>
          <div class="av3-space-top-2 av3-grid av3-grid-cols-1 av3-sm-grid-cols-3 av3-gap-2">
            <div class="av3-radius-card av3-border av3-surface-white av3-pad-x3 av3-pad-y2">
              <div class="av3-type-caption av3-text-slate-500">IVA ($)</div>
              <div class="av3-weight-strong"><span data-role="sale-iva-amount"><?= money((int)round((float)$ivaImporteNum)) ?></span></div>
            </div>
            <div class="av3-radius-card av3-border av3-surface-white av3-pad-x3 av3-pad-y2">
              <div class="av3-type-caption av3-text-slate-500">Costo final (con IVA)</div>
              <div class="av3-weight-strong"><span data-role="sale-cost-final"><?= money((int)round((float)$costFinalNum)) ?></span></div>
            </div>
            <div class="av3-radius-card av3-border av3-surface-white av3-pad-x3 av3-pad-y2">
              <div class="av3-type-caption av3-text-slate-500">Markup vs. costo final</div>
              <div class="av3-weight-strong">
                <?php if ($markupVsFinal === null): ?>
                  <span class="av3-text-slate-500">—</span>
                <?php else: ?>
                  <span data-role="sale-markup-pct"><?= rtrim(rtrim(number_format($markupVsFinal * 100, 1, '.', ''), '0'), '.') ?></span>%
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="av3-space-top-2 av3-type-caption av3-text-slate-500">El cliente ve <b>precio final</b>. Este cálculo te ayuda a validar el precio contra el costo final.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="av3-space-top-4 av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-4">
      <label class="av3-block">Stock
        <input class="input av3-width-full" type="number" name="stock" value="<?= (int)$stock ?>" min="0">
      </label>
      <label class="av3-block">Umbral bajo stock
        <input class="input av3-width-full" type="number" name="low_stock_threshold" value="<?= (int)$low ?>" min="0">
      </label>
      <div class="av3-block">
        <div class="av3-type-body av3-text-var-fg av3-mt-7">Se usa para alertas en Dashboard.</div>
      </div>
    </div>

    <?php if ($restockReactivatePrompt): ?>
      <div class="av3-space-top-3 av3-radius-card av3-border av3-border-amber-300 av3-bg-amber-50 av3-pad-3">
        <p class="av3-type-body av3-text-amber-900">Este producto estaba inactivo y ahora tiene stock. Si querés volver a venderlo, activalo con este guardado.</p>
        <label class="av3-space-top-2 av3-inline-flex av3-items-center av3-gap-2 av3-type-body av3-weight-medium av3-text-amber-900">
          <input type="checkbox" name="confirm_reactivate_product" value="1">
          Activar producto automáticamente
        </label>
      </div>
    <?php endif; ?>
  </div>

  <?php
    $shipDefaults = $shippingDefaults ?? [];
    $defW = (int)($shipDefaults['weight'] ?? 1000);
    $defH = (int)($shipDefaults['height'] ?? 10);
    $defWi = (int)($shipDefaults['width'] ?? 10);
    $defL = (int)($shipDefaults['length'] ?? 10);

    $shipWeight = $p['ship_weight'] ?? null;
    $shipHeight = $p['ship_height'] ?? null;
    $shipWidth  = $p['ship_width'] ?? null;
    $shipLength = $p['ship_length'] ?? null;
    if (!empty($saleTab)) {
      $defW = (int)($saleTab['defW'] ?? $defW);
      $defH = (int)($saleTab['defH'] ?? $defH);
      $defWi = (int)($saleTab['defWi'] ?? $defWi);
      $defL = (int)($saleTab['defL'] ?? $defL);
      $shipWeight = $saleTab['shipWeight'] ?? $shipWeight;
      $shipHeight = $saleTab['shipHeight'] ?? $shipHeight;
      $shipWidth = $saleTab['shipWidth'] ?? $shipWidth;
      $shipLength = $saleTab['shipLength'] ?? $shipLength;
    }
  ?>

  <div class="av3-space-top-6 av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4">
    <div class="av3-flex av3-items-start av3-justify-between av3-gap-3">
      <div>
        <h3 class="av3-type-body av3-weight-strong av3-text-slate-900">Envío: peso y medidas</h3>
        <p class="av3-space-top-1 av3-type-caption av3-text-var-muted">
          Si no completás estos datos, el checkout <b>no se bloquea</b>: se usan los valores por defecto configurados en <b>Ajustes → Envíos</b>.
        </p>
        <p class="av3-space-top-1 av3-type-caption av3-text-var-muted">
          Las variantes pueden sobrescribir estos valores. Si una variante queda vacía, se usa este valor por defecto.
        </p>
      </div>
      <span class="badge badge-muted">Opcional</span>
    </div>

    <div class="av3-space-top-3 av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3">
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Peso (g)</div>
        <input class="input av3-width-full" type="number" min="1" name="ship_weight" value="<?= $shipWeight === null ? '' : (int)$shipWeight ?>" placeholder="<?= (int)$defW ?>">
      </label>
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Alto (cm)</div>
        <input class="input av3-width-full" type="number" min="1" name="ship_height" value="<?= $shipHeight === null ? '' : (int)$shipHeight ?>" placeholder="<?= (int)$defH ?>">
      </label>
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Ancho (cm)</div>
        <input class="input av3-width-full" type="number" min="1" name="ship_width" value="<?= $shipWidth === null ? '' : (int)$shipWidth ?>" placeholder="<?= (int)$defWi ?>">
      </label>
      <label class="av3-block">
        <div class="av3-type-body av3-weight-medium av3-space-bottom-1">Largo (cm)</div>
        <input class="input av3-width-full" type="number" min="1" name="ship_length" value="<?= $shipLength === null ? '' : (int)$shipLength ?>" placeholder="<?= (int)$defL ?>">
      </label>
    </div>
  </div>

<?php
$vc  = $variantsCtx ?? [];
$pAttrs2 = $vc['product_attributes'] ?? [];
$variants2 = $vc['variants'] ?? [];
$valuesByVariant2 = $vc['values_by_variant'] ?? [];

$attrCount2 = is_array($pAttrs2) ? count($pAttrs2) : 0;
$variantCount2 = is_array($variants2) ? count($variants2) : 0;

$requiredIds2 = [];
foreach (($pAttrs2 ?: []) as $pa) {
  $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
  if ($aid && !empty($pa['is_required'])) $requiredIds2[] = $aid;
}
$requiredIds2 = array_values(array_unique(array_filter($requiredIds2)));

$incompleteCount2 = 0;
$vendibleCount2 = 0;

foreach (($variants2 ?: []) as $v) {
  $vid = (int)($v['id'] ?? 0);
  if (!$vid) continue;

  $present = [];
  foreach (($valuesByVariant2[$vid] ?? []) as $vv) {
    $aid = (int)($vv['attribute_id'] ?? 0);
    if ($aid) $present[$aid] = true;
  }

  $missingRequired = false;
  foreach ($requiredIds2 as $rid) {
    if (empty($present[$rid])) { $missingRequired = true; break; }
  }
  if ($missingRequired) $incompleteCount2++;

  $sku = trim((string)($v['sku'] ?? ''));
  $price = (float)($v['price'] ?? 0);
  $stock = isset($v['stock']) ? (int)$v['stock'] : 0;
  $active = isset($v['is_active']) ? (int)$v['is_active'] : 1;

  // Vendible (técnico): completa + activa + sku + precio>0 + stock>=0 (stock=0 permitido; preventa se decide después)
  if (!$missingRequired && $active === 1 && $sku !== '' && $price > 0 && $stock >= 0) $vendibleCount2++;
}
if (!empty($saleTab)) {
  $attrCount2 = (int)($saleTab['attrCount2'] ?? $attrCount2);
  $variantCount2 = (int)($saleTab['variantCount2'] ?? $variantCount2);
  $incompleteCount2 = (int)($saleTab['incompleteCount2'] ?? $incompleteCount2);
  $vendibleCount2 = (int)($saleTab['vendibleCount2'] ?? $vendibleCount2);
}
?>

<div class="av3-space-top-6" data-sale-mode="variants">

  <!-- Resumen liviano (Paso 2) -->
  <div class="av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4">
    <div class="av3-flex av3-flex-col av3-md-flex-row av3-md-items-center av3-md-justify-between av3-gap-3">
      <div class="av3-flow-1">
        <div class="av3-type-base av3-weight-strong av3-text-slate-900">Variantes</div>
        <div class="av3-type-body av3-text-var-muted">
          Configurá opciones de variante y completá SKU + precio + stock por variante.
        </div>

        <div class="av3-space-top-2 av3-flex av3-flex-wrap av3-items-center av3-gap-2 av3-type-caption av3-text-slate-600">
          <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-bg-slate-100">Opciones: <b class="av3-text-slate-900"><?= (int)$attrCount2 ?></b></span>
          <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-bg-slate-100">Variantes: <b class="av3-text-slate-900"><?= (int)$variantCount2 ?></b></span>
          <?php if ($variantCount2 > 0): ?>
            <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-bg-slate-100">Vendibles: <b class="av3-text-slate-900"><?= (int)$vendibleCount2 ?></b></span>
            <span class="av3-pad-x2 av3-pad-y1 av3-radius-full <?= $incompleteCount2 > 0 ?'av3-bg-amber-100 av3-text-amber-900' : 'av3-bg-slate-100 av3-text-slate-600' ?>">
              Incompletas: <b><?= (int)$incompleteCount2 ?></b>
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="av3-flex av3-items-center av3-gap-2">
        <button type="button" class="btn btn-secondary" data-go-tab="variantes">Configurar variantes</button>
      </div>
    </div>

    <?php if ($attrCount2 === 0): ?>
      <div class="av3-space-top-3 av3-type-body av3-text-amber-800 av3-bg-amber-50 av3-border av3-border-amber-200 av3-radius-card av3-pad-3">
        Todavía no configuraste opciones para este producto. Entrá a la pestaña <b>Variantes</b> para comenzar.
      </div>
    <?php endif; ?>

    <?php if ($variantCount2 > 0 && $vendibleCount2 === 0): ?>
      <div class="av3-space-top-3 av3-type-body av3-text-amber-800 av3-bg-amber-50 av3-border av3-border-amber-200 av3-radius-card av3-pad-3">
        Para que el producto quede listo para vender, completá al menos una variante vendible (SKU + precio + stock).
      </div>
    <?php endif; ?>
  </div>


</div>

</div>
