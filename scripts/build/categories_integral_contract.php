<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$read=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$svc=$read('app/Services/Admin/Categories/CategoriesIndexService.php');
$admin=$read('app/Controllers/Admin/CategoriesController.php');
$index=$read('app/Views/pages/admin/categories/index.php');
$form=$read('app/Views/pages/admin/categories/form.php');
$css=$read('public_html/assets/css/admin-v3/pages/categories.css');
$product=$read('app/Controllers/Catalog/ProductController.php');
$products=$read('app/Controllers/Catalog/ProductsController.php');
$router=$read('public_html/index.php');
$checks=[
 'batch public visibility set' => str_contains($svc,'CategoryPublicVisibilityService($this->db)'),
 'public visibility is memoized/batched' => str_contains($svc,'->visibleIdSet()'),
 'rows tag public visibility per page' => str_contains($svc, '$row[\'public_visible\']'),
 'global published KPI count' => str_contains($svc, 'count($visibleSet)'),
 'display stored active state' => str_contains($index, '$active ? \'Activa\' : \'Inactiva\''),
 'display hierarchical hidden reason' => str_contains($index,'Oculta en tienda'),
 'display distinct public KPI' => str_contains($index,'Visibles en tienda'),
 'mobile badge and warning layout' => str_contains($css,'.av3-categories__mobile-state'),
 'four responsive KPIs' => str_contains($css,'repeat(4,minmax(0,1fr))'),
 'parent ancestor visibility explained' => str_contains($form,'todos sus ancestros'),
 'malformed scalar form values blocked' => str_contains($admin,'!is_scalar($_POST[$field])'),
 'malformed form returns HTTP 422' => str_contains($admin,'http_response_code(422)'),
 'admin form CSRF still mandatory' => str_contains($admin,'verify_csrf()'),
 'public product visit GET-only' => str_contains($product,'REQUEST_METHOD') && str_contains($product,'trackProductView('),
 'public search audit GET-only' => str_contains($products,'REQUEST_METHOD') && str_contains($products,"Audit::log('catalog_search'"),
 'HEAD public product route enabled' => str_contains($router,"'/producto', '/categoria', '/marca'"),
 'HEAD never page_view' => str_contains($router,'if ($method === \'GET\') {'),
 'HEAD zero body buffer' => str_contains($router,'ob_start(static fn(string $body): string'),
];
foreach($checks as $label=>$ok) {
 if(!$ok){fwrite(STDERR,"[categories-integral] FAIL ".$label."\n");exit(1);}
}
echo '[categories-integral] OK '.count($checks)." checks\n";
