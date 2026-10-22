<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$entry=(string)file_get_contents($root.'/public_html/index.php');
$resolver=(string)file_get_contents($root.'/app/Services/Seo/PublicRedirectTargetResolver.php');
$legacy=(string)file_get_contents($root.'/app/Controllers/Catalog/ProductsController.php');
$routes=(string)file_get_contents($root.'/config/routes.php');
$productController=(string)file_get_contents($root.'/app/Controllers/Catalog/ProductController.php');
$checks=[
 'HEAD body buffer' => str_contains($entry, "if (" . '$method' . " === 'HEAD') ob_start("),
 'HEAD limited public whitelist' => str_contains($entry,'$headReadOnlyPaths = ['),
 'HEAD GET dispatch only read-only' => str_contains($entry, '$headReadOnly ? \'GET\' : $method'),
 'HEAD not global GET' => !str_contains($entry, '$key = $method . \' \' . $uriPath;'),
 'HEAD product allowed after GET-only analytics guard' => str_contains($entry, "'/producto', '/categoria', '/marca'"),
 'HEAD search supported after GET-only audit guard' => !str_contains($entry,'$headReadOnly = false;'),
 'HEAD page_view not tracked' => str_contains($entry,"if (" . '$method' . " === 'GET') {"),
 'HEAD SEO resolved only safe path' => str_contains($entry,'$headAllowed') && str_contains($entry,'$destPath'),
 'HEAD SEO hit not registered' => str_contains($entry,"if (" . '$method' . " === 'GET') {") && str_contains($entry,'->registerHit($from)'),
 'tracking raw query inspected' => str_contains($entry,"explode('&',") && str_contains($entry,'$seenQueryKeys'),
 'tracking invalid percent escapes denied' => str_contains($entry,'%(?![0-9a-f]{2})'),
 'tracking decoded percent denied' => str_contains($entry,"str_contains(" . '$decodedValue' . ", '%')"),
 'tracking duplicate keys denied' => str_contains($entry,'isset($seenQueryKeys[$decodedKey])'),
 'structured brand/tag arrays preserved' => str_contains($entry,'$hasStructuredFilters') && str_contains($entry,'$repeatable'),
 'tracking captured on GET only' => str_contains($entry,"if (" . '$method' . " === 'GET' && session_status()"),
 'first touch not overwritten' => str_contains($entry,"empty(" . '$_SESSION' . "['analytics_utm'])"),
 'original UTM fields retained' => str_contains($entry,"'utm_medium'") && str_contains($entry,"'utm_campaign'"),
 'UTM cleaned before final resolution' => strpos($entry,'$cleaned = $uriPath') < strpos($entry,'->resolveCleanedRequest($cleaned)'),
 'valid target required to Location' => str_contains($entry,"if (" . '$resolved' . " !== null && (" . '$method' . " === 'GET' || " . '$headSafe' . "))"),
 'clean tracking direct no open redirect' => str_contains($entry,'url($resolved['),
 'first-hop historical hit only on GET' => str_contains($entry,"!empty(" . '$resolved' . "['historical'])"),
 'HEAD product redirects allowed if B1 eligible' => str_contains($entry,'$headTargetPath') && str_contains($entry,"'/producto', '/categoria', '/marca'"),
 'legacy filters preserved' => str_contains($legacy,"['q','min','max','orden','page','perPage','per_page']"),
 'brand multiple filter validation' => str_contains($legacy,'array_is_list($legacyBrandRaw)') && str_contains($legacy,'$brandFilter'),
 'resolver cleaned public API' => str_contains($resolver,'function resolveCleanedRequest(string $cleaned)'),
 'resolver reuses historic chain rules' => str_contains($resolver,'$this->resolve($cleaned)'),
 'resolver reuses legacy safe rules' => str_contains($resolver,'$this->resolveDirect($target)'),
 'resolver forbids unsafe products and categories' => str_contains($resolver,'->permitsProduct(') && str_contains($resolver,'->findActiveBySlug($slug)'),
 'product view tracking requires actual GET' => str_contains($productController, 'REQUEST_METHOD') && str_contains($productController, 'trackProductView('),
 'search auditing requires actual GET' => str_contains($legacy, 'REQUEST_METHOD') && str_contains($legacy, "Audit::log('catalog_search'"),
 'routes contain no indiscriminate HEAD addition' => !str_contains($routes,"'HEAD /checkout'") && !str_contains($routes,"'HEAD /admin'"),
];
foreach($checks as $label=>$ok)if(!$ok){fwrite(STDERR,"[redirect-head-utm] FAIL $label\n");exit(1);}
echo '[redirect-head-utm] OK '.count($checks)." checks\n";
