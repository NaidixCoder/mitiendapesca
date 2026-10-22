<?php
/**
 * Admin > Categorías / Marcas > SEO PRO (panel completo)
 *
 * Variables esperadas:
 * - $entityType: 'category' | 'brand'
 * - $entity: array
 * - $seoAudit: array|null
 * - $seoSettings: array
 * - $seoBlocks: array{bullets:array, specs:array, faq:array}
 */

$entityType = (string)($entityType ?? '');
$entity = $entity ?? [];
$audit = $seoAudit ?? null;
$seoSettings = is_array($seoSettings ?? null) ? $seoSettings : [];
$seoBlocks = is_array($seoBlocks ?? null) ? $seoBlocks : ['bullets'=>[], 'specs'=>[], 'faq'=>[]];

$labelSing = ($entityType === 'brand') ? 'Marca' : 'Categoría';
$labelSingLc = mb_strtolower($labelSing);

$robots = trim((string)($seoSettings['robots'] ?? '')) ?: 'index,follow';
$seoIndex = (stripos($robots, 'noindex') !== false) ? 'noindex' : 'index';
$seoFollow = (stripos($robots, 'nofollow') !== false) ? 'nofollow' : 'follow';

$seoCanonical = (string)($seoSettings['canonical_override'] ?? '');
$seoOgTitle = (string)($seoSettings['og_title'] ?? '');
$seoOgDesc  = (string)($seoSettings['og_description'] ?? '');
$seoOgImage = (string)($seoSettings['og_image'] ?? '');
$seoTwitter = (string)($seoSettings['twitter_card'] ?? '');
$seoSchema  = (string)($seoSettings['schema_override_jsonld'] ?? '');

$name = trim((string)($entity['name'] ?? ''));
$slug = trim((string)($entity['slug'] ?? ''));

$metaTitle = (string)($entity['meta_title'] ?? '');
$metaDesc  = (string)($entity['meta_description'] ?? '');
$seoText   = (string)($entity['seo_text'] ?? '');

$lenTitle = mb_strlen((string)$metaTitle);
$lenDesc  = mb_strlen((string)$metaDesc);
$lenText  = mb_strlen(trim((string)$seoText));

$score = (int)($audit['score'] ?? 0);
$previewUrl = (string)($audit['preview']['url'] ?? '');
$previewTitle = (string)($audit['preview']['title'] ?? '');
$previewDesc = (string)($audit['preview']['description'] ?? '');

$base = function_exists('base_url') ? rtrim((string)base_url('/'), '/') : '';
$path = ($entityType === 'brand') ? '/marca?slug=' : '/categoria?slug=';

if ($previewUrl === '' && $slug !== '') {
  $previewUrl = ($base !== '' ? $base : '') . $path . rawurlencode($slug);
}
if ($previewTitle === '') {
  $previewTitle = $metaTitle !== '' ? $metaTitle : ($name !== '' ? ($name . ' | Mi Pesca & Aventura') : ($labelSing . ' | Mi Pesca & Aventura'));
}
if ($previewDesc === '') {
  $previewDesc = $metaDesc !== '' ? $metaDesc : ($lenText ? mb_substr(trim($seoText), 0, 160) : ($name !== '' ? $name : $labelSing));
}

$scorePill = $score >= 85 ? 'av3-bg-emerald-600' : ($score >= 65 ? 'av3-bg-amber-500' : 'av3-bg-rose-600');

$scoreConfig = [
  'siteName' => 'Mi Pesca & Aventura',
  'baseUrl' => ($base !== '' ? $base : ''),
  'entityName' => $name,
  'slug' => $slug,
  'entityType' => ($entityType === 'brand' ? 'brand' : 'category'),
  'initialScore' => $score,
];
?>

<?php // Adaptador visual propio de Marcas: conserva los mismos names, data-* y estructuras de bloques del SEO compartido. ?>
<div class="av3-brands-seo" data-brands-seo>
  <div class="av3-brands-seo__main">

  <section class="av3-brands-seo__fields" aria-labelledby="brand-seo-fields">
    <h3 id="brand-seo-fields">Metadatos de búsqueda</h3>
    <div class="av3-brands-seo__fields-grid">
      <div class="av3-ui-field av3-brands-seo__field-title">
        <label class="av3-ui-field-label" for="meta_title">Título SEO</label>
        <input class="av3-ui-control" id="meta_title" name="meta_title" maxlength="70"
          value="<?= e($metaTitle) ?>" placeholder="Ej.: <?= e($labelSing) ?> de pesca | Mi Pesca">
        <small class="av3-ui-field-hint">25–60 caracteres · <span data-seo-title-len><?= (int)$lenTitle ?></span> actuales</small>
      </div>
      <div class="av3-ui-field av3-brands-seo__field-desc">
        <label class="av3-ui-field-label" for="meta_description">Descripción SEO</label>
        <textarea class="av3-ui-control" id="meta_description" name="meta_description" maxlength="160"
          rows="2" placeholder="Descripción breve para resultados de búsqueda."><?= e($metaDesc) ?></textarea>
        <small class="av3-ui-field-hint">70–160 caracteres · <span data-seo-desc-len><?= (int)$lenDesc ?></span> actuales</small>
      </div>
      <div class="av3-ui-field av3-brands-seo__field-text">
        <label class="av3-ui-field-label" for="seo_text">Texto SEO</label>
        <textarea class="av3-ui-control" id="seo_text" name="seo_text" rows="3"
          placeholder="Descripción de la marca para la ficha pública."><?= e($seoText) ?></textarea>
        <small class="av3-ui-field-hint">Recomendado: 120 caracteres o más · <span data-seo-text-len><?= (int)$lenText ?></span> actuales</small>
      </div>
    </div>
  </section>

  <aside class="av3-brands-seo__diagnostic-slot" aria-label="Diagnóstico SEO">

  <div class="av3-brands-seo__diagnostic" data-seo-score data-seo-score-config="<?= htmlspecialchars(json_encode($scoreConfig, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>">
    <div class="av3-flex av3-items-start av3-justify-between av3-gap-4">
      <div>
        <div class="av3-type-body av3-weight-strong">Estado SEO</div>
        <div class="av3-type-caption av3-text-gray-500 av3-space-top-1">Vista previa y checklist de optimización.</div>
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
            $seoTextTrim = trim((string)$seoText);
            $checks = [
              ['key'=>'titulo', 'label'=>'Título de la '.$labelSingLc, 'ok'=>($name !== ''), 'detail'=>($name !== '' ? 'OK' : 'Falta nombre.')],
              ['key'=>'slug', 'label'=>'URL (slug)', 'ok'=>($slug !== ''), 'detail'=>($slug !== '' ? 'OK' : 'Falta slug.')],
              ['key'=>'meta_title', 'label'=>'Meta title (25–60)', 'ok'=>($metaTitle !== '' && $lenTitle >= 25 && $lenTitle <= 60), 'detail'=>($metaTitle === '' ? 'Falta.' : ($lenTitle > 60 ? 'Muy largo.' : ($lenTitle < 25 ? 'Muy corto.' : 'OK')))],
              ['key'=>'meta_desc', 'label'=>'Meta description (70–160)', 'ok'=>($metaDesc !== '' && $lenDesc >= 70 && $lenDesc <= 160), 'detail'=>($metaDesc === '' ? 'Falta.' : ($lenDesc > 160 ? 'Muy larga.' : ($lenDesc < 70 ? 'Muy corta.' : 'OK')))],
              ['key'=>'seo_text', 'label'=>'Texto SEO (≥ 120)', 'ok'=>($seoTextTrim !== '' && $lenText >= 120), 'detail'=>($seoTextTrim === '' ? 'Falta (recomendado).' : ($lenText < 120 ? 'Muy corto.' : 'OK'))],
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

  </aside>
  </div>

  <div class="av3-brands-seo__secondary">
    <section class="av3-brands-seo__settings" aria-labelledby="brand-seo-advanced-title">
      <header class="av3-brands-seo__settings-heading">
        <h3 id="brand-seo-advanced-title">Configuración avanzada</h3>
        <p>Indexación, enlaces, redes sociales y datos estructurados.</p>
      </header>
      <div class="av3-brands-seo__advanced-grid">
        <section class="av3-brands-seo__advanced-group" aria-labelledby="brand-seo-index-heading">
          <h3 id="brand-seo-index-heading">Indexación y enlaces</h3>
          <div class="av3-brands-seo__robots">
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_index">Indexar</label>
              <select class="av3-ui-control" id="seo_index" name="seo_index">
                <option value="index" <?= $seoIndex === 'index' ? 'selected' : '' ?>>Indexar</option>
                <option value="noindex" <?= $seoIndex === 'noindex' ? 'selected' : '' ?>>No indexar</option>
              </select>
            </div>
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_follow">Seguir enlaces</label>
              <select class="av3-ui-control" id="seo_follow" name="seo_follow">
                <option value="follow" <?= $seoFollow === 'follow' ? 'selected' : '' ?>>Follow</option>
                <option value="nofollow" <?= $seoFollow === 'nofollow' ? 'selected' : '' ?>>Nofollow</option>
              </select>
            </div>
          </div>
          <div class="av3-brands-seo__index-details">
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_canonical_override">Canonical (opcional)</label>
              <input class="av3-ui-control" id="seo_canonical_override" name="seo_canonical_override" value="<?= e($seoCanonical) ?>"
                placeholder="Automático si queda vacío">
            </div>
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_twitter_card">Twitter Card</label>
              <select class="av3-ui-control" id="seo_twitter_card" name="seo_twitter_card">
                <option value="" <?= $seoTwitter === '' ? 'selected' : '' ?>>Automático</option>
                <option value="summary_large_image" <?= $seoTwitter === 'summary_large_image' ? 'selected' : '' ?>>summary_large_image</option>
                <option value="summary" <?= $seoTwitter === 'summary' ? 'selected' : '' ?>>summary</option>
              </select>
            </div>
          </div>
          <div class="av3-brands-seo__schema">
            <div class="av3-brands-seo__schema-heading">
              <h4>Datos estructurados (JSON-LD)</h4>
              <span>Opcional</span>
            </div>
            <div class="av3-ui-field">
              <label class="av3-brands__sr-only" for="seo_schema_override_jsonld">Schema personalizado</label>
              <textarea class="av3-ui-control" id="seo_schema_override_jsonld" name="seo_schema_override_jsonld" rows="3"
                placeholder="Schema automático si queda vacío"><?= e($seoSchema) ?></textarea>
            </div>
          </div>
        </section>
        <section class="av3-brands-seo__advanced-group" aria-labelledby="brand-seo-social-heading">
          <h3 id="brand-seo-social-heading">Open Graph · Facebook / WhatsApp</h3>
          <div class="av3-brands-seo__social-grid">
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_og_title">Título para compartir</label>
              <input class="av3-ui-control" id="seo_og_title" name="seo_og_title" value="<?= e($seoOgTitle) ?>"
                placeholder="Usar título SEO si queda vacío">
            </div>
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_og_description">Descripción para compartir</label>
              <textarea class="av3-ui-control" id="seo_og_description" name="seo_og_description" rows="2"
                placeholder="Usar descripción SEO si queda vacío"><?= e($seoOgDesc) ?></textarea>
            </div>
            <div class="av3-ui-field">
              <label class="av3-ui-field-label" for="seo_og_image">URL de imagen OG</label>
              <input class="av3-ui-control" id="seo_og_image" name="seo_og_image" value="<?= e($seoOgImage) ?>"
                placeholder="Imagen predeterminada si queda vacío">
            </div>
          </div>
        </section>

      </div>
    </section>

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

    <div class="av3-brands-seo__blocks" data-seo-blocks>
      <div class="av3-flex av3-items-center av3-justify-between av3-gap-3">
        <div>
          <div class="av3-type-body av3-weight-strong">Contenido complementario</div>
          <div class="av3-type-caption av3-text-gray-500 av3-mt-0_5">Agregá información útil solo cuando la necesites.</div>
        </div>
      </div>

      <div class="av3-brands-seo__blocks-grid">
        <!-- Bullets -->
        <div class="av3-brands-seo__block">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div class="av3-type-body av3-weight-strong">Puntos clave</div>
            <button type="button" class="av3-ui-btn" data-seo-add="bullet">+ Agregar</button>
          </div>
          <div class="av3-space-top-3 av3-flow-2" data-seo-list="bullets">
            <?php foreach ($bullets as $b): ?>
              <div class="av3-flex av3-items-start av3-gap-2" draggable="true" data-seo-row>
                <button type="button" class="av3-space-top-2 av3-text-gray-400" title="Arrastrar" data-seo-handle>⋮⋮</button>
                <input class="av3-width-full av3-radius-card av3-border-gray-200" name="seo_bullets[]" value="<?= e($b) ?>" placeholder="Ej: Envíos a todo el país">
                <button type="button" class="av3-space-top-1 av3-text-gray-400 av3-hover-text-rose-600" title="Quitar" data-seo-remove>✕</button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Specs -->
        <div class="av3-brands-seo__block">
          <div class="av3-flex av3-items-center av3-justify-between">
            <div class="av3-type-body av3-weight-strong">Especificaciones</div>
            <button type="button" class="av3-ui-btn" data-seo-add="spec">+ Agregar</button>
          </div>
          <div class="av3-space-top-3 av3-flow-2" data-seo-list="specs">
            <?php foreach ($specs as $s): ?>
              <div class="av3-grid av3-grid-cols-12 av3-gap-2 av3-items-start" draggable="true" data-seo-row>
                <button type="button" class="av3-col-span-1 av3-space-top-2 av3-text-gray-400" title="Arrastrar" data-seo-handle>⋮⋮</button>
                <input class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_specs_label[]" value="<?= e($s['label'] ?? '') ?>" placeholder="Etiqueta">
                <input class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_specs_value[]" value="<?= e($s['value'] ?? '') ?>" placeholder="Valor">
                <button type="button" class="av3-col-span-1 av3-space-top-1 av3-text-gray-400 av3-hover-text-rose-600" title="Quitar" data-seo-remove>✕</button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <!-- FAQ -->
      <div class="av3-brands-seo__block">
        <div class="av3-flex av3-items-center av3-justify-between">
          <div class="av3-type-body av3-weight-strong">Preguntas frecuentes (FAQ)</div>
          <button type="button" class="av3-ui-btn" data-seo-add="faq">+ Agregar</button>
        </div>
        <div class="av3-space-top-3 av3-flow-3" data-seo-list="faq">
          <?php foreach ($faq as $f): ?>
            <div class="av3-grid av3-grid-cols-12 av3-gap-2 av3-items-start" draggable="true" data-seo-row>
              <button type="button" class="av3-col-span-1 av3-space-top-2 av3-text-gray-400" title="Arrastrar" data-seo-handle>⋮⋮</button>
              <input class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_faq_q[]" value="<?= e($f['q'] ?? '') ?>" placeholder="Pregunta">
              <textarea class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_faq_a[]" rows="2" placeholder="Respuesta"><?= e($f['a'] ?? '') ?></textarea>
              <button type="button" class="av3-col-span-1 av3-space-top-1 av3-text-gray-400 av3-hover-text-rose-600" title="Quitar" data-seo-remove>✕</button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      </div>

      <!-- Live preview -->
      <div class="av3-brands-seo__live-preview" data-seo-live-preview>
        <div class="av3-type-caption av3-text-gray-500 av3-space-bottom-3">Vista previa (sin guardar)</div>

        <div class="av3-flow-3">
          <div data-seo-preview-empty class="av3-type-body av3-text-gray-500">Todavía no agregaste contenido SEO.</div>

          <div data-seo-preview-bullets hidden>
            <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Puntos clave</div>
            <ul class="av3-flow-1 av3-type-body av3-text-gray-700" data-seo-preview-bullets-list></ul>
          </div>

          <div data-seo-preview-specs hidden>
            <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Especificaciones</div>
            <div class="av3-overflow-hidden av3-radius-card av3-border av3-border-gray-100 av3-surface-white">
              <table class="av3-width-full av3-type-body">
                <tbody data-seo-preview-specs-body></tbody>
              </table>
            </div>
          </div>

          <div data-seo-preview-faq hidden>
            <div class="av3-type-body av3-weight-strong av3-space-bottom-2">Preguntas frecuentes</div>
            <div class="av3-flow-2" data-seo-preview-faq-list></div>
          </div>
        </div>
      </div>

      <!-- Templates -->
      <template data-seo-template="bullet">
        <div class="av3-flex av3-items-start av3-gap-2" draggable="true" data-seo-row>
          <button type="button" class="av3-space-top-2 av3-text-gray-400" title="Arrastrar" data-seo-handle>⋮⋮</button>
          <input class="av3-width-full av3-radius-card av3-border-gray-200" name="seo_bullets[]" value="" placeholder="Ej: Envíos a todo el país">
          <button type="button" class="av3-space-top-1 av3-text-gray-400 av3-hover-text-rose-600" title="Quitar" data-seo-remove>✕</button>
        </div>
      </template>

      <template data-seo-template="spec">
        <div class="av3-grid av3-grid-cols-12 av3-gap-2 av3-items-start" draggable="true" data-seo-row>
          <button type="button" class="av3-col-span-1 av3-space-top-2 av3-text-gray-400" title="Arrastrar" data-seo-handle>⋮⋮</button>
          <input class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_specs_label[]" value="" placeholder="Etiqueta">
          <input class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_specs_value[]" value="" placeholder="Valor">
          <button type="button" class="av3-col-span-1 av3-space-top-1 av3-text-gray-400 av3-hover-text-rose-600" title="Quitar" data-seo-remove>✕</button>
        </div>
      </template>

      <template data-seo-template="faq">
        <div class="av3-grid av3-grid-cols-12 av3-gap-2 av3-items-start" draggable="true" data-seo-row>
          <button type="button" class="av3-col-span-1 av3-space-top-2 av3-text-gray-400" title="Arrastrar" data-seo-handle>⋮⋮</button>
          <input class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_faq_q[]" value="" placeholder="Pregunta">
          <textarea class="av3-col-span-5 av3-radius-card av3-border-gray-200" name="seo_faq_a[]" rows="2" placeholder="Respuesta"></textarea>
          <button type="button" class="av3-col-span-1 av3-space-top-1 av3-text-gray-400 av3-hover-text-rose-600" title="Quitar" data-seo-remove>✕</button>
        </div>
      </template>

    </div>
  </div>
</div>
