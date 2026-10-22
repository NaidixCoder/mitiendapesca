<?php
declare(strict_types=1);

/**
 * Contrato estático de Marcas Admin V3. Ejecución independiente; su inclusión
 * en gate.php requiere coordinación sobre ese archivo compartido.
 */
$root = dirname(__DIR__, 2);
$files = [
  'controller' => "$root/app/Controllers/Admin/BrandsController.php",
  'repository' => "$root/app/Repositories/Admin/BrandAdminRepository.php",
  'viewmodel' => "$root/app/Services/Admin/Brands/ViewModels/BrandsIndexViewModel.php",
  'draft' => "$root/app/Services/Admin/Brands/BrandFormDraft.php",
  'save_service' => "$root/app/Services/Admin/Brands/BrandSaveService.php",
  'seo_input' => "$root/app/Services/Admin/Brands/BrandSeoInput.php",
  'cover_url' => "$root/app/Services/Admin/Brands/BrandCoverUrl.php",
  'index' => "$root/app/Views/pages/admin/brands/index.php",
  'form' => "$root/app/Views/pages/admin/brands/form.php",
  'seo_form' => "$root/app/Views/partials/admin/brands/seo_editor.php",
  'css' => "$root/public_html/assets/css/admin-v3/pages/brands.css",
  'js' => "$root/public_html/assets/js/admin/brands/form.js",
  'smoke' => "$root/scripts/dev/brands_readonly_smoke.php",
];
foreach ($files as $key => $path) {
  if (!is_file($path)) throw new RuntimeException("Falta archivo de Marcas: $key");
  $files[$key] = file_get_contents($path);
}
$checks = [
  'controller' => [
    'admin-brands-index', 'new BrandAdminRepository(', 'new BrandsIndexViewModel(', "verify_csrf()", "storeCoverImage(", 'BrandFormDraft::remember(', 'BrandFormDraft::overlay(',
    "slugExists(", "rate_limit_or_fail(", "public function quick(): void",
    'new BrandSaveService(', 'BrandSeoInput::parse(', 'removeManagedBrandCover(', 'originalId',
  ],
  'save_service' => ['beginTransaction(', 'commit()', 'rollBack()', 'FOR UPDATE', 'saveSettings(', 'saveForBrand(', 'recordBrandSlugChange(', 'registerSlug(', 'inTransaction()'],
  'seo_input' => ['json_decode(', 'seo_bullets', 'seo_specs_label', 'seo_faq_q', 'Cada sección de contenido'],
  'cover_url' => ["preg_match('#^https?://#i'", 'public_url('],
  'repository' => [
    'COUNT(*) FROM brands b', 'active_product_count', 'PDO::PARAM_INT', "ESCAPE '='", "'=%'", "'=_'",
    'ORDER BY b.sort ASC, b.name ASC, b.id ASC',
  ],
  'index' => [
    'av3-ui-card', 'av3-ui-field', 'av3-ui-control', 'av3-ui-table', 'av3-ui-empty',
    'av3-brands__table', 'av3-brands__mobile-list',
    'av3-brands__pagination', 'name="q"', 'name="per"', 'av3-brands__empty', 'BrandCoverUrl::resolve(',
  ],
  'form' => [
    'admin/brands/save', 'name="slug"', 'name="cover_image_file"',
    'av3-ui-card', 'av3-ui-field', 'av3-ui-control', 'av3-ui-card-header',
    'av3-brands__identity-grid', 'av3-brands__identity-tools', 'av3-brands__cover-inline', 'av3-brands__cover-controls', 'av3-brands__seo-section',
    "section('partials/admin/brands/seo_editor'", 'data-brands-form', 'data-brands-editor', 'js/admin/brands/form.js',
  ],
  'seo_form' => ['av3-brands-seo__advanced-grid', 'av3-brands-seo__advanced-group', 'av3-brands-seo__schema', 'av3-brands-seo__settings-heading', 'av3-brands-seo__blocks-grid', 'data-seo-score', 'data-seo-check-row', 'data-seo-preview-faq-list', 'name="meta_title"',
    'name="meta_description"', 'name="seo_text"', 'name="seo_index"', 'name="seo_follow"', 'name="seo_canonical_override"', 'name="seo_og_title"', 'name="seo_og_description"', 'name="seo_og_image"', 'name="seo_twitter_card"', 'name="seo_schema_override_jsonld"',
    'data-seo-blocks', 'data-seo-list="bullets"', 'data-seo-list="specs"', 'data-seo-list="faq"', 'name="seo_bullets[]"', 'name="seo_specs_label[]"', 'name="seo_specs_value[]"', 'name="seo_faq_q[]"', 'name="seo_faq_a[]"', 'data-seo-template="bullet"', 'data-seo-template="spec"', 'data-seo-template="faq"'],
  'draft' => ['remember(int $id', 'consume(int $id)', 'overlay(array &$brand'],

  'js' => ['data-brands-editor', 'data-seo-list', 'data-seo-add', 'IntersectionObserver', 'requestSubmit(primary)', 'av3-brands__block-collapsed'],
  'css' => [
    '.av3-brands__table-wrap', '.av3-brands__mobile-list', '@container (min-width:650px)',
    'var(--av3-color-primary)', '.av3-brands__form-actions',
    '.av3-brands__identity-grid', '.av3-brands__cover-inline', '.av3-brands-seo__advanced-grid', '.av3-brands-seo__fields-grid', '.av3-brands__block-collapsed',
  ],
];
foreach ($checks as $key => $fragments) {
  foreach ($fragments as $fragment) {
    if (!str_contains($files[$key], $fragment)) throw new RuntimeException("Contrato de marcas: $key no contiene $fragment");
  }
}
foreach (['index','form'] as $view) {
  if (!str_contains($files[$view], "css/admin-v3/pages/brands.css")) {
    throw new RuntimeException("Vista de $view sin hoja propia");
  }
  if (preg_match('/style\s*=\s*["\x27]/i', $files[$view])) {
    throw new RuntimeException("Vista de $view reintrodujo estilos inline");
  }
}
echo "[marcas-contrato] OK: repositorio, formularios, SEO, CSS, listados responsive.\n";
