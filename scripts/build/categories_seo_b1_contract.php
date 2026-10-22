<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$get=static fn(string $file): string => (string)file_get_contents($root.'/'.$file);
$meta=$get('app/Controllers/System/MetaController.php');
$front=$get('app/Services/Seo/SeoFrontService.php');
$head=$get('app/Views/partials/head.php');
$error=$get('app/Views/pages/errors/404.php');
$product=$get('app/Controllers/Catalog/ProductController.php');
$category=$get('app/Controllers/Catalog/CategoryController.php');
$checks=[
 'sitemap product uses B1 visibility' => str_contains($meta, 'new \App\Services\Catalog\ProductPublicVisibilityService($db)'),
 'product B1 predicate inside query' => str_contains($meta, "->predicate('p')"),
 'product seo_settings batch join' => str_contains($meta, "LEFT JOIN seo_settings ss"),
 'product exclusion editorial noindex SQL' => str_contains($meta, 'ss.robots_index = 1'),
 'product canonical match prior XML output' => str_contains($meta, '$editorialCanonical !== $ownCanonical'),
 'product canon relative matches service' => str_contains($meta, "base_url('/' . ltrim("),
 'category reuses approved visible repository' => str_contains($meta, '->listPublicVisibleOrdered();'),
 'category seo settings batch query' => str_contains($meta, "WHERE entity_type='category'"),
 'category noindex excluded' => str_contains($meta, '(int)$settings[\'robots_index\'] === 0'),
 'category canonical excluded' => substr_count($meta, '$editorialCanonical !== $ownCanonical')===2,
 'sitemap product XML preserved' => str_contains($meta,'rawurlencode($slug)') && str_contains($meta,'<urlset'),
 'sitemap category XML preserved' => str_contains($meta, 'rawurlencode($slug)') && str_contains($meta, '/categoria?slug='),
 'sitemap brand function preserved' => str_contains($meta,'public function sitemapBrands(): void'),
 '404 detected before head render' => str_contains($head,'$isNotFoundPage') && str_contains($head,'errors/404'),
 '404 noindex nofollow' => str_contains($head,"'robots' => 'noindex,nofollow'"),
 '404 explicit HTTP status' => str_contains($head,'http_response_code(404)') && str_contains($error,'http_response_code(404)'),
 '404 canonical suppressed' => str_contains($head,'$isNotFoundPage ? null'),
 '404 does not emit canonical link' => str_contains($head,'if ($canonical !== null'),
 '404 extraHead suppressed' => str_contains($head,'if (!$isNotFoundPage) echo $extraHead;'),
 '404 no specific jsonld' => str_contains($head,'$isNotFoundPage ? null'),
 '404 no second robots in view' => !str_contains($error, '$extraHead ='),
 '404 body retains visual markup' => str_contains($error,'<main class="error-view">'),
 'product jsonld after canonical overrides' => strpos($front,'$seo = $this->applySettings($seo, $settings);') < strpos($front, '$seo[\'jsonld\'] = $this->jsonLdProduct'),
 'category jsonld after editorial overrides' => strpos($front,'$seo = $this->applyAdvancedSettings($seo, $settingsRow);') < strpos($front, '$seo[\'jsonld\'] = $this->jsonLdCollectionPage'),
 'schema override still allowed product' => str_contains($front, '$settings[\'schema_override\']'),
 'schema override still allowed category' => str_contains($front, '$settingsRow[\'schema_override\']'),
 'product 404 existing guard preserved' => str_contains($product,"->permitsProduct(") && str_contains($product,"render_page('errors/404'"),
 'category 404 existing guard preserved' => str_contains($category,"->findActiveBySlug(") && str_contains($category,"render_page('errors/404'"),
];
foreach($checks as $label=>$pass){
 if(!$pass){fwrite(STDERR,"[categories-seo-b1] FAIL $label\n");exit(1);}
}
echo '[categories-seo-b1] OK '.count($checks)." checks\n";
