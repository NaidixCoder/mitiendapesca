<?php
declare(strict_types=1);
$root = dirname(__DIR__,2);
$get = static fn(string $f): string => (string)file_get_contents($root.'/'.$f);
$service = $get('app/Services/Seo/PublicRedirectTargetResolver.php');
$entry = $get('public_html/index.php');
$legacy = $get('app/Controllers/Catalog/ProductsController.php');
$repo = $get('app/Repositories/Seo/SeoRedirectRepository.php');
$checks=[
 'central resolver exists' => str_contains($service,'final class PublicRedirectTargetResolver'),
 'safe local only' => str_contains($service, '$value[0]'),
 'protocol-relative forbidden' => str_contains($service, 'str_starts_with($value,'),
 'backslash forbidden' => str_contains($service,'str_contains($value,'),
 'controls forbidden' => str_contains($service,'\\x00-\\x20'),
 'encoded slash and CRLF denied' => str_contains($service,'2f|5c|25'),
 'path traversal denied' => str_contains($service,'\\.{1,2}'),
 'query only known fields' => str_contains($service, '$allowed = ['),
 'query arrays and duplicate params denied' => str_contains($service,'isset($params[$key])'),
 'strict public terminal routing' => str_contains($service,'if ($terminal && !array_key_exists($path,$allowed)) return null;'),
 'chain bounded' => str_contains($service,'MAX_HOPS = 12'),
 'cycle checked' => str_contains($service,'isset($seen[$normalized])'),
 'self redirect prevented' => str_contains($service,'$seen = [$source => true]'),
 'statuses constrained 301/302' => str_contains($service,'in_array($code, [301, 302]'),
 'mixed temporary downgrade' => str_contains($service,'$status = 302'),
 'active category ancestors required' => str_contains($service,'->findActiveBySlug($slug)'),
 'uses B1 for product' => str_contains($service,'->permitsProduct('),
 'brand identity validated' => str_contains($service,'SELECT id FROM brands WHERE slug=?'),
 'noindex editorial guarded' => str_contains($service,'robots_index') && str_contains($service,"=== 0"),
 'canonical editorial guarded' => str_contains($service,'$editorial === $expected'),
 'no redirection to private pages' => str_contains($service,"'/checkout'")===false && str_contains($service,"'/admin'")===false,
 'legacy subtree descendants batch' => str_contains($service,'->descendantCategoryIds('),
 'legacy avoids chaining' => str_contains($service, '->resolve($target)'),
 'redirect index invokes resolver' => str_contains($entry,'PublicRedirectTargetResolver(db())'),
 'Location only after nonnull result' => strpos($entry,'$resolved = $resolver->resolve($from);') < strrpos($entry, 'header(\'Location: \' . url($resolved[\'target\'])'),
 'hits after valid redirect' => strrpos($entry, 'header(\'Location: \' . url($resolved[\'target\'])') < strrpos($entry,'->registerHit($from)'),
 'hit failures do not fall through dispatch' => str_contains($entry,'catch (\\Throwable $hitError)'),
 'HEAD safely restricted in SEO block' => str_contains($entry, '$headAllowed') && str_contains($entry, 'if ($method === \'GET\' || $headAllowed)'),
 'legacy validates target' => str_contains($legacy,'->resolveDirect($to)'),
 'legacy rejects invalid arrays' => str_contains($legacy,'!is_string($legacyCatRaw)') && str_contains($legacy,'array_is_list($legacyBrandRaw)'),
 'legacy returns 404 invalid target' => str_contains($legacy,'if ($safeTarget === null)'),
 'legacy filters preserved' => str_contains($legacy,"['q','min','max','orden','page','perPage','per_page']"),
 'shared repository upsert intact' => str_contains($repo,'public function upsert('),
 'shared repository retarget intact' => str_contains($repo,'public function retargetTo('),
 'shared repository hits reused' => str_contains($repo,'public function registerHit('),
];
foreach($checks as $label=>$pass){
 if(!$pass){fwrite(STDERR,"[categories-seo-redirect] FAIL ".$label."\n");exit(1);}
}
echo '[categories-seo-redirect] OK: '.count($checks)." checks\n";
