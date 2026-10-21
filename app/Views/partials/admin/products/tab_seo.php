<?php
/**
 * Admin > Productos > Tab SEO
 *
 * Variables esperadas (desde admin/products/form.php):
 * - $p (array) producto
 * - $seoAudit (array|null) auditoría SEO (score/checklist/preview)
 * - $seoSettings (array) settings avanzados (robots/canonical/social/schema)
 * - $seoBlocks (array) bullets/specs/faq
 */

$product = $p ?? ($product ?? []);
$audit = $seoAudit ?? null;
$seoSettings = is_array($seoSettings ?? null) ? $seoSettings : [];
$seoBlocks = is_array($seoBlocks ?? null) ? $seoBlocks : ['bullets'=>[], 'specs'=>[], 'faq'=>[]];

$robots = trim((string)($seoSettings['robots'] ?? '')) ?: 'index,follow';
$seoIndex = (stripos($robots, 'noindex') !== false) ? 'noindex' : 'index';
$seoFollow = (stripos($robots, 'nofollow') !== false) ? 'nofollow' : 'follow';

$seoCanonical = (string)($seoSettings['canonical_override'] ?? '');
$seoOgTitle = (string)($seoSettings['og_title'] ?? '');
$seoOgDesc  = (string)($seoSettings['og_description'] ?? '');
$seoOgImage = (string)($seoSettings['og_image'] ?? '');
$seoTwitter = (string)($seoSettings['twitter_card'] ?? '');
$seoSchema  = (string)($seoSettings['schema_override_jsonld'] ?? '');

$name = trim((string)($product['name'] ?? ''));
$slug = trim((string)($product['slug'] ?? ''));

$metaTitle = (string)($product['meta_title'] ?? '');
$metaDesc  = (string)($product['meta_description'] ?? '');

$lenTitle = mb_strlen((string)$metaTitle);
$lenDesc  = mb_strlen((string)$metaDesc);

$score = (int)($audit['score'] ?? 0);
$previewUrl = (string)($audit['preview']['url'] ?? '');
$previewTitle = (string)($audit['preview']['title'] ?? '');
$previewDesc = (string)($audit['preview']['description'] ?? '');

$hasCover = false;
foreach ((array)($audit['items'] ?? []) as $it) {
  if (($it['key'] ?? '') === 'imagen' && (($it['status'] ?? '') === 'ok')) { $hasCover = true; break; }
}
$hasPrice = ((float)($audit['pricing']['low'] ?? 0)) > 0;

$base = function_exists('base_url') ? rtrim((string)base_url('/'), '/') : '';
if ($previewUrl === '' && $slug !== '') {
  $previewUrl = ($base !== '' ? $base : '') . '/producto?slug=' . rawurlencode($slug);
}
if ($previewTitle === '') {
  $previewTitle = $metaTitle !== '' ? $metaTitle : ($name !== '' ? ($name . ' | Mi Pesca & Aventura') : 'Producto | Mi Pesca & Aventura');
}
if ($previewDesc === '') {
  $previewDesc = $metaDesc !== '' ? $metaDesc : ($name !== '' ? $name : 'Producto');
}

$scorePill = $score >= 85 ? 'av3-bg-emerald-600' : ($score >= 65 ? 'av3-bg-amber-500' : 'av3-bg-rose-600');

$scoreConfig = [
  'siteName' => 'Mi Pesca & Aventura',
  'baseUrl' => ($base !== '' ? $base : ''),
  'productName' => $name,
  'slug' => $slug,
  'hasCover' => $hasCover,
  'hasPrice' => $hasPrice,
  'initialScore' => $score,
];
$seoTab = is_array($seoTab ?? null) ? $seoTab : [];
$seoChecks = [];
if (!empty($seoTab)) {
  $robots = (string)($seoTab['robots'] ?? $robots);
  $seoIndex = (string)($seoTab['seoIndex'] ?? $seoIndex);
  $seoFollow = (string)($seoTab['seoFollow'] ?? $seoFollow);
  $seoCanonical = (string)($seoTab['seoCanonical'] ?? $seoCanonical);
  $seoOgTitle = (string)($seoTab['seoOgTitle'] ?? $seoOgTitle);
  $seoOgDesc = (string)($seoTab['seoOgDesc'] ?? $seoOgDesc);
  $seoOgImage = (string)($seoTab['seoOgImage'] ?? $seoOgImage);
  $seoTwitter = (string)($seoTab['seoTwitter'] ?? $seoTwitter);
  $seoSchema = (string)($seoTab['seoSchema'] ?? $seoSchema);
  $name = (string)($seoTab['name'] ?? $name);
  $slug = (string)($seoTab['slug'] ?? $slug);
  $metaTitle = (string)($seoTab['metaTitle'] ?? $metaTitle);
  $metaDesc = (string)($seoTab['metaDesc'] ?? $metaDesc);
  $lenTitle = (int)($seoTab['lenTitle'] ?? $lenTitle);
  $lenDesc = (int)($seoTab['lenDesc'] ?? $lenDesc);
  $score = (int)($seoTab['score'] ?? $score);
  $previewUrl = (string)($seoTab['previewUrl'] ?? $previewUrl);
  $previewTitle = (string)($seoTab['previewTitle'] ?? $previewTitle);
  $previewDesc = (string)($seoTab['previewDesc'] ?? $previewDesc);
  $hasCover = (bool)($seoTab['hasCover'] ?? $hasCover);
  $hasPrice = (bool)($seoTab['hasPrice'] ?? $hasPrice);
  $scorePill = (string)($seoTab['scorePill'] ?? $scorePill);
  $scoreConfig = is_array($seoTab['scoreConfig'] ?? null) ? $seoTab['scoreConfig'] : $scoreConfig;
  $seoChecks = is_array($seoTab['checks'] ?? null) ? $seoTab['checks'] : [];
  $seoBlocks = [
    'bullets' => is_array($seoTab['bullets'] ?? null) ? $seoTab['bullets'] : ($seoBlocks['bullets'] ?? []),
    'specs' => is_array($seoTab['specs'] ?? null) ? $seoTab['specs'] : ($seoBlocks['specs'] ?? []),
    'faq' => is_array($seoTab['faq'] ?? null) ? $seoTab['faq'] : ($seoBlocks['faq'] ?? []),
  ];
}
?>

<div class="av3-seo-editor av3-flow-6">
  <!-- Estado SEO (score + checklist + preview SERP) -->
  <div class="av3-seo-editor__panel" data-seo-score data-seo-score-config="<?= htmlspecialchars(json_encode($scoreConfig, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>">
    <div class="av3-flex av3-items-start av3-justify-between av3-gap-4">
      <div>
        <div class="av3-type-body av3-weight-strong">Estado SEO</div>
        <div class="av3-type-caption av3-text-gray-500 av3-space-top-1">Score, checklist y vista previa tipo Google (se actualiza al tipear).</div>
      </div>
      <div class="av3-flex av3-items-center av3-gap-2">
        <div class="av3-pad-x3 av3-pad-y1 av3-radius-full av3-text-white av3-type-body av3-weight-strong <?= $scorePill ?>" data-seo-score-pill>
          <span data-seo-score-value><?= (int)$score ?></span>/100
        </div>
      </div>
    </div>

    <div class="av3-space-top-3 av3-size-h-2 av3-radius-full av3-bg-gray-100 av3-overflow-hidden" aria-hidden="true">
      <div class="av3-size-h-2 av3-radius-full av3-bg-emerald-600" data-seo-score-bar style="width: <?= max(0, min(100, (int)$score)) ?>%;"></div>
    </div>

    <div class="av3-space-top-4 av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4">
      <!-- Preview SERP -->
      <div class="av3-radius-card av3-border av3-border-gray-100 av3-pad-4 av3-surface-white">
        <div class="av3-type-caption av3-text-gray-500 av3-space-bottom-2">Vista previa estimada</div>
        <div class="av3-type-body av3-text-emerald-700 av3-seo-editor__url" data-seo-serp-url><?= htmlspecialchars($previewUrl) ?></div>
        <div class="av3-type-lg av3-weight-strong av3-text-blue-800 av3-leading-snug av3-space-top-1" data-seo-serp-title><?= htmlspecialchars($previewTitle) ?></div>
        <div class="av3-type-body av3-text-gray-600 av3-space-top-1" data-seo-serp-desc><?= htmlspecialchars($previewDesc) ?></div>
        <div class="av3-space-top-2 av3-type-micro av3-text-gray-400">*Vista estimada. Google puede reescribir el snippet.</div>
      </div>

      <!-- Checklist -->
      <div class="av3-radius-card av3-border av3-border-gray-100 av3-pad-4 av3-surface-white">
        <div class="av3-type-caption av3-text-gray-500 av3-space-bottom-2">Checklist</div>
        <ul class="av3-flow-2">
          <?php
            $checks = [
              ['key'=>'titulo', 'label'=>'Título del producto', 'ok'=>($name !== ''), 'detail'=>($name !== '' ? 'OK' : 'Falta nombre.')],
              ['key'=>'slug', 'label'=>'URL (slug)', 'ok'=>($slug !== ''), 'detail'=>($slug !== '' ? 'OK' : 'Falta slug.')],
              ['key'=>'meta_title', 'label'=>'Meta title (25–60)', 'ok'=>($metaTitle !== '' && $lenTitle >= 25 && $lenTitle <= 60), 'detail'=>($metaTitle === '' ? 'Falta.' : ($lenTitle > 60 ? 'Muy largo.' : ($lenTitle < 25 ? 'Muy corto.' : 'OK')))],
              ['key'=>'meta_desc', 'label'=>'Meta description (70–160)', 'ok'=>($metaDesc !== '' && $lenDesc >= 70 && $lenDesc <= 160), 'detail'=>($metaDesc === '' ? 'Falta.' : ($lenDesc > 160 ? 'Muy larga.' : ($lenDesc < 70 ? 'Muy corta.' : 'OK')))],
              ['key'=>'imagen', 'label'=>'Imagen principal (portada)', 'ok'=>$hasCover, 'detail'=>($hasCover ? 'OK' : 'No hay portada.')],
              ['key'=>'precio', 'label'=>'Precio mayor a 0', 'ok'=>$hasPrice, 'detail'=>($hasPrice ? 'OK' : 'Precio en 0.')],
            ];
          ?>
          <?php foreach ($checks as $c): ?>
            <?php $dot = $c['ok'] ? 'av3-bg-emerald-600' : 'av3-bg-amber-500'; ?>
            <li class="av3-flex av3-items-start av3-gap-3" data-seo-check-row="<?= e($c['key']) ?>">
              <span class="av3-space-top-1 av3-inline-block av3-h-2-5 av3-w-2-5 av3-radius-full <?= $dot ?>" data-seo-check-dot="<?= e($c['key']) ?>"></span>
              <div class="av3-min-w-0">
                <div class="av3-type-body av3-weight-medium"><?= e($c['label']) ?></div>
                <div class="av3-type-caption av3-text-gray-500" data-seo-check-detail="<?= e($c['key']) ?>"><?= e($c['detail']) ?></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Campos SEO básicos -->
  <div class="av3-seo-editor__panel">
    <div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4">
      <div>
        <label class="av3-type-caption av3-weight-strong av3-tracking-wide av3-text-gray-600">Título SEO</label>
        <input
          class="av3-space-top-1 av3-width-full av3-radius-card av3-border-gray-200"
          name="meta_title"
          id="seo-meta-title"
          maxlength="70"
          value="<?= htmlspecialchars($metaTitle) ?>"
          placeholder="Ej: Bendy Paleta 1 - Señuelo Gozio | Mi Pesca"
        />
        <div class="av3-space-top-1 av3-type-caption av3-text-gray-500">Recomendado: 25–60 caracteres. Actual: <span data-seo-title-len><?= (int)$lenTitle ?></span>.</div>
      </div>

      <div>
        <label class="av3-type-caption av3-weight-strong av3-tracking-wide av3-text-gray-600">Descripción SEO</label>
        <textarea
          class="av3-space-top-1 av3-width-full av3-radius-card av3-border-gray-200"
          name="meta_description"
          id="seo-meta-desc"
          maxlength="160"
          rows="4"
          placeholder="Una descripción clara, con beneficio y palabras clave."><?= htmlspecialchars($metaDesc) ?></textarea>
        <div class="av3-space-top-1 av3-type-caption av3-text-gray-500">Recomendado: 70–160 caracteres. Actual: <span data-seo-desc-len><?= (int)$lenDesc ?></span>.</div>
      </div>
    </div>

    <div class="av3-space-top-3 av3-type-caption av3-text-gray-500">
      La URL pública usa el <strong>slug</strong>. Sumá detalles (modelo/medidas/color) para diferenciar y mejorar el CTR.
    </div>
  </div>

  <!-- SEO avanzado + contenido SEO -->
  <div class="av3-seo-editor__panel av3-seo-editor__content">
    <details class="av3-seo-editor__settings">
      <summary>Configuración avanzada <span>Indexación, canonical, redes sociales y datos estructurados</span></summary>
      <div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-6 av3-space-top-5">
      <div class="av3-flow-5">
        <div>
          <label class="av3-block av3-type-caption av3-weight-strong av3-text-gray-600">Indexación (robots)</label>
          <div class="av3-grid av3-grid-cols-1 av3-sm-grid-cols-2 av3-gap-3 av3-space-top-2">
            <div>
              <label class="av3-block av3-type-micro av3-text-gray-500 av3-space-bottom-1">Indexar</label>
              <select name="seo_index" class="av3-width-full av3-radius-card av3-border-gray-200">
                <option value="index" <?= $seoIndex === 'index' ? 'selected' : '' ?>>Indexar</option>
                <option value="noindex" <?= $seoIndex === 'noindex' ? 'selected' : '' ?>>No indexar</option>
              </select>
            </div>
            <div>
              <label class="av3-block av3-type-micro av3-text-gray-500 av3-space-bottom-1">Seguir enlaces</label>
              <select name="seo_follow" class="av3-width-full av3-radius-card av3-border-gray-200">
                <option value="follow" <?= $seoFollow === 'follow' ? 'selected' : '' ?>>Follow</option>
                <option value="nofollow" <?= $seoFollow === 'nofollow' ? 'selected' : '' ?>>Nofollow</option>
              </select>
            </div>
          </div>
          <p class="av3-type-caption av3-text-gray-500 av3-space-top-2">Tip: en borradores, productos agotados o duplicados podés usar <b>noindex</b>.</p>
        </div>

        <div>
          <label class="av3-block av3-type-caption av3-weight-strong av3-text-gray-600">Canonical (override)</label>
          <input name="seo_canonical_override" value="<?= e($seoCanonical) ?>"
                 placeholder="Vacío = automático. Ej: /producto?slug=..."
                 class="av3-space-top-2 av3-width-full av3-radius-card av3-border-gray-200" />
          <p class="av3-type-caption av3-text-gray-500 av3-space-top-2">Si lo dejás vacío, usamos la URL del producto.</p>
        </div>

        <div>
          <label class="av3-block av3-type-caption av3-weight-strong av3-text-gray-600">Twitter Card</label>
          <select name="seo_twitter_card" class="av3-space-top-2 av3-width-full av3-radius-card av3-border-gray-200">
            <option value="" <?= $seoTwitter === '' ? 'selected' : '' ?>>Automático (recomendado)</option>
            <option value="summary_large_image" <?= $seoTwitter === 'summary_large_image' ? 'selected' : '' ?>>summary_large_image</option>
            <option value="summary" <?= $seoTwitter === 'summary' ? 'selected' : '' ?>>summary</option>
          </select>
          <p class="av3-type-caption av3-text-gray-500 av3-space-top-2">Normalmente conviene <b>summary_large_image</b>.</p>
        </div>
      </div>

      <div class="av3-flow-5">
        <div>
          <label class="av3-block av3-type-caption av3-weight-strong av3-text-gray-600">Open Graph (Facebook / WhatsApp)</label>
          <div class="av3-space-top-2 av3-flow-3">
            <input name="seo_og_title" value="<?= e($seoOgTitle) ?>"
                   placeholder="Título OG (vacío = usar Meta title)"
                   class="av3-width-full av3-radius-card av3-border-gray-200" />
            <textarea name="seo_og_description" rows="3"
                      placeholder="Descripción OG (vacío = usar Meta description)"
                      class="av3-width-full av3-radius-card av3-border-gray-200"><?= e($seoOgDesc) ?></textarea>
            <input name="seo_og_image" value="<?= e($seoOgImage) ?>"
                   placeholder="URL de imagen OG (vacío = portada del producto)"
                   class="av3-width-full av3-radius-card av3-border-gray-200" />
          </div>
          <p class="av3-type-caption av3-text-gray-500 av3-space-top-2">Si no cargás nada, se usa la portada del producto y los meta.</p>
        </div>

        <div>
          <label class="av3-block av3-type-caption av3-weight-strong av3-text-gray-600">Schema override (JSON-LD)</label>
          <textarea name="seo_schema_override_jsonld" rows="7"
                    placeholder="Pegá acá un JSON-LD válido. Vacío = automático."
                    class="av3-space-top-2 av3-width-full av3-radius-card av3-border-gray-200 av3-font-mono av3-type-caption"><?= e($seoSchema) ?></textarea>
          <p class="av3-type-caption av3-text-gray-500 av3-space-top-2">Solo si querés controlar el schema a mano (modo avanzado).</p>
        </div>
      </div>
      </div>
    </details>

    <?php
      $blocks = $seoBlocks;
      $bullets = is_array($blocks['bullets'] ?? null) ? $blocks['bullets'] : [];
      $specs = is_array($blocks['specs'] ?? null) ? $blocks['specs'] : [];
      $faq = is_array($blocks['faq'] ?? null) ? $blocks['faq'] : [];

      // UX: mostrar al menos 1 fila vacía
      if (empty($bullets)) $bullets = [''];
      if (empty($specs)) $specs = [['label'=>'', 'value'=>'']];
      if (empty($faq)) $faq = [['q'=>'', 'a'=>'']];
    ?>

    <div class="av3-seo-editor__blocks" data-seo-blocks>
      <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
        <div>
          <div class="av3-type-body av3-weight-strong">Contenido adicional del producto</div>
          <div class="av3-type-caption av3-text-gray-500 av3-mt-0_5">Contenido útil para el comprador. No garantiza resultados enriquecidos en Google.</div>
        </div>
        <span class="av3-type-micro av3-text-gray-500">Se guardará solo lo completo</span>
      </div>

      <div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-3 av3-gap-5 av3-space-top-4">
        <!-- Bullets -->
        <div class="av3-radius-xl av3-border av3-border-gray-100 av3-bg-var-surface av3-pad-4">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div>
              <div class="av3-type-caption av3-weight-strong av3-text-gray-700">Puntos clave</div>
              <div class="av3-type-micro av3-text-gray-500">Ej: "Anzuelo reforzado"</div>
            </div>
            <button type="button" class="av3-pad-x3 av3-pad-y1half av3-radius-card av3-border av3-border-gray-200 av3-type-caption av3-hover-border-gray-300" data-seo-add="bullet">+ Agregar</button>
          </div>

          <div class="av3-space-top-3 av3-flow-2" data-seo-list="bullets">
            <?php foreach ($bullets as $b): ?>
              <div class="av3-flex av3-items-center av3-gap-2" data-seo-row draggable="true">
                <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-surface-white av3-text-gray-500 av3-hover-text-gray-700 av3-hover-border-gray-300" title="Reordenar" aria-label="Reordenar" data-seo-handle>â‹®â‹®</button>
                <input name="seo_bullets[]" value="<?= e((string)$b) ?>" class="av3-width-full av3-radius-card av3-border-gray-200" placeholder="Punto clave" />
                <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-text-gray-500 av3-hover-text-var-danger av3-hover-border--color-var---danger-40" title="Quitar" data-seo-remove>×</button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Specs -->
        <div class="av3-radius-xl av3-border av3-border-gray-100 av3-bg-var-surface av3-pad-4">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div>
              <div class="av3-type-caption av3-weight-strong av3-text-gray-700">Especificaciones</div>
              <div class="av3-type-micro av3-text-gray-500">Ej: Largo → 6.6\"</div>
            </div>
            <button type="button" class="av3-pad-x3 av3-pad-y1half av3-radius-card av3-border av3-border-gray-200 av3-type-caption av3-hover-border-gray-300" data-seo-add="spec">+ Agregar</button>
          </div>

          <div class="av3-space-top-3 av3-flow-2" data-seo-list="specs">
            <?php foreach ($specs as $s): ?>
              <div class="av3-grid av3-grid-cols-12 av3-gap-2 av3-items-center" data-seo-row draggable="true">
                <div class="av3-col-span-1 av3-flex av3-justify-center">
                  <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-surface-white av3-text-gray-500 av3-hover-text-gray-700 av3-hover-border-gray-300" title="Reordenar" aria-label="Reordenar" data-seo-handle>â‹®â‹®</button>
                </div>
                <input name="seo_specs_label[]" value="<?= e((string)($s['label'] ?? '')) ?>" class="av3-col-span-4 av3-radius-card av3-border-gray-200" placeholder="Atributo" />
                <input name="seo_specs_value[]" value="<?= e((string)($s['value'] ?? '')) ?>" class="av3-col-span-6 av3-radius-card av3-border-gray-200" placeholder="Valor" />
                <button type="button" class="av3-col-span-1 av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-text-gray-500 av3-hover-text-var-danger av3-hover-border--color-var---danger-40" title="Quitar" data-seo-remove>×</button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- FAQ -->
        <div class="av3-radius-xl av3-border av3-border-gray-100 av3-bg-var-surface av3-pad-4">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div>
              <div class="av3-type-caption av3-weight-strong av3-text-gray-700">FAQ (Preguntas frecuentes)</div>
              <div class="av3-type-micro av3-text-gray-500">Las preguntas ayudan al comprador, sin garantizar resultados enriquecidos</div>
            </div>
            <button type="button" class="av3-pad-x3 av3-pad-y1half av3-radius-card av3-border av3-border-gray-200 av3-type-caption av3-hover-border-gray-300" data-seo-add="faq">+ Agregar</button>
          </div>

          <div class="av3-space-top-3 av3-flow-3" data-seo-list="faq">
            <?php foreach ($faq as $f): ?>
              <div class="av3-radius-card av3-border av3-border-gray-100 av3-pad-3" data-seo-row draggable="true">
                <div class="av3-flex av3-items-center av3-gap-2">
                  <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-surface-white av3-text-gray-500 av3-hover-text-gray-700 av3-hover-border-gray-300" title="Reordenar" aria-label="Reordenar" data-seo-handle>â‹®â‹®</button>
                  <input name="seo_faq_q[]" value="<?= e((string)($f['q'] ?? '')) ?>" class="av3-width-full av3-radius-card av3-border-gray-200" placeholder="Pregunta" />
                  <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-text-gray-500 av3-hover-text-var-danger av3-hover-border--color-var---danger-40" title="Quitar" data-seo-remove>×</button>
                </div>
                <textarea name="seo_faq_a[]" rows="3" class="av3-space-top-2 av3-width-full av3-radius-card av3-border-gray-200" placeholder="Respuesta"><?= e((string)($f['a'] ?? '')) ?></textarea>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Vista previa en vivo (Ficha de producto) -->
      <div class="av3-space-top-5 av3-radius-xl av3-border av3-border-gray-100 av3-surface-white av3-pad-4" data-seo-live-preview>
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
          <div>
            <div class="av3-type-caption av3-weight-strong av3-text-gray-700">Vista previa en vivo</div>
            <div class="av3-type-micro av3-text-gray-500">Así se verá este contenido en la ficha pública (solo se incluyen ítems completos).</div>
          </div>
          <div class="av3-type-micro av3-text-gray-400">Arrastrá â‹®â‹® para ordenar</div>
        </div>

        <div class="av3-space-top-4 av3-grid av3-grid-cols-1 av3-lg-grid-cols-3 av3-gap-5">
          <div class="av3-radius-xl av3-border av3-border-gray-100 av3-pad-4" data-seo-preview-bullets hidden>
            <div class="av3-type-caption av3-weight-strong av3-text-gray-700 av3-space-bottom-2">Puntos clave</div>
            <ul class="av3-type-body av3-text-gray-700 av3-flow-1" data-seo-preview-bullets-list></ul>
          </div>

          <div class="av3-radius-xl av3-border av3-border-gray-100 av3-pad-4" data-seo-preview-specs hidden>
            <div class="av3-type-caption av3-weight-strong av3-text-gray-700 av3-space-bottom-2">Especificaciones</div>
            <div class="av3-overflow-hidden av3-radius-card av3-border av3-border-gray-100">
              <table class="av3-width-full av3-type-body">
                <tbody data-seo-preview-specs-body></tbody>
              </table>
            </div>
          </div>

          <div class="av3-radius-xl av3-border av3-border-gray-100 av3-pad-4" data-seo-preview-faq hidden>
            <div class="av3-type-caption av3-weight-strong av3-text-gray-700 av3-space-bottom-2">Preguntas frecuentes</div>
            <div class="av3-flow-2" data-seo-preview-faq-list></div>
          </div>
        </div>

        <div class="av3-space-top-3 av3-type-micro av3-text-gray-500" data-seo-preview-empty>
          Todavía no hay contenido completo para mostrar en la vista previa.
        </div>
      </div>

      <!-- Templates para JS (agregar filas) -->
      <template data-seo-template="bullet">
        <div class="av3-flex av3-items-center av3-gap-2" data-seo-row draggable="true">
          <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-surface-white av3-text-gray-500 av3-hover-text-gray-700 av3-hover-border-gray-300" title="Reordenar" aria-label="Reordenar" data-seo-handle>â‹®â‹®</button>
          <input name="seo_bullets[]" value="" class="av3-width-full av3-radius-card av3-border-gray-200" placeholder="Punto clave" />
          <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-text-gray-500 av3-hover-text-var-danger av3-hover-border--color-var---danger-40" title="Quitar" data-seo-remove>×</button>
        </div>
      </template>

      <template data-seo-template="spec">
        <div class="av3-grid av3-grid-cols-12 av3-gap-2 av3-items-center" data-seo-row draggable="true">
          <div class="av3-col-span-1 av3-flex av3-justify-center">
            <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-surface-white av3-text-gray-500 av3-hover-text-gray-700 av3-hover-border-gray-300" title="Reordenar" aria-label="Reordenar" data-seo-handle>â‹®â‹®</button>
          </div>
          <input name="seo_specs_label[]" value="" class="av3-col-span-4 av3-radius-card av3-border-gray-200" placeholder="Atributo" />
          <input name="seo_specs_value[]" value="" class="av3-col-span-6 av3-radius-card av3-border-gray-200" placeholder="Valor" />
          <button type="button" class="av3-col-span-1 av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-text-gray-500 av3-hover-text-var-danger av3-hover-border--color-var---danger-40" title="Quitar" data-seo-remove>×</button>
        </div>
      </template>

      <template data-seo-template="faq">
        <div class="av3-radius-card av3-border av3-border-gray-100 av3-pad-3" data-seo-row draggable="true">
          <div class="av3-flex av3-items-center av3-gap-2">
            <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-surface-white av3-text-gray-500 av3-hover-text-gray-700 av3-hover-border-gray-300" title="Reordenar" aria-label="Reordenar" data-seo-handle>â‹®â‹®</button>
            <input name="seo_faq_q[]" value="" class="av3-width-full av3-radius-card av3-border-gray-200" placeholder="Pregunta" />
            <button type="button" class="av3-size-h-10 av3-size-w-10 av3-grid av3-radius-card av3-border av3-border-gray-200 av3-text-gray-500 av3-hover-text-var-danger av3-hover-border--color-var---danger-40" title="Quitar" data-seo-remove>×</button>
          </div>
          <textarea name="seo_faq_a[]" rows="3" class="av3-space-top-2 av3-width-full av3-radius-card av3-border-gray-200" placeholder="Respuesta"></textarea>
        </div>
      </template>
    </div>
  </div>
</div>


