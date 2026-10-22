<?php
declare(strict_types=1);
$root = dirname(__DIR__,2);
$get = static fn(string $file): string => (string)file_get_contents($root.'/'.$file);
$policy = $get('app/Services/Catalog/ProductPublicVisibilityService.php');
$products = $get('app/Repositories/Catalog/ProductRepository.php');
$facets = $get('app/Services/Catalog/FacetService.php');
$index = $get('app/Controllers/Catalog/ProductsController.php');
$detail = $get('app/Controllers/Catalog/ProductController.php');
$category = $get('app/Controllers/Catalog/CategoryController.php');
$quick = $get('app/Controllers/Catalog/QuickAddController.php');
$home = $get('app/Services/Public/HomePageViewService.php');
$recommendations = $get('app/Services/Catalog/RecommendedProductsService.php');
$checks = [
 'policy exists' => str_contains($policy,'final class ProductPublicVisibilityService'),
 'reads existing flag only' => str_contains($policy,'catalog_hide_inactive_category_products') && str_contains($policy,"->get("),
 'uses category visibility service' => str_contains($policy,'new CategoryPublicVisibilityService($this->db)'),
 'OFF does not filter categories' => str_contains($policy,'if (!$this->hidesHiddenCategoryProducts()) return $where'),
 'ON fail closed if no visible categories' => str_contains($policy,'1=0'),
 'policy always requires active product' => str_contains($policy,'.is_active = 1'),
 'public SQL predicate before LIMIT' => str_contains($products,'->publicProductVisibility()->predicate($productAlias)'),
 'paginate and price bounds share buildCatalogWhere' => substr_count($products,'$this->buildCatalogWhere($filters,') >= 2,
 'no legacy category direct flag filter' => !str_contains($products,'c.is_active = 1'),
 'suggest search filtered before limit' => str_contains($products,'publicVisibilitySql(\'p\')'),
 'recommendations use shared filtered reads' => str_contains($recommendations,'->findRelatedFill(') && str_contains($recommendations,'->findRelatedForCart('),
 'analytics recommendation IDs filtered by SQL' => str_contains($recommendations,'->findVisibleByIdsOrdered('),
 'public list by IDs applies shared policy' => str_contains($products,'function findVisibleByIdsOrdered') && str_contains($products,'publicVisibilitySql'),
 'product 404 uses policy' => str_contains($detail,'->permitsProduct(') && str_contains($detail,'http_response_code(404)'),
 'product breadcrumb avoids 404 category' => str_contains($detail,'isCategoryPubliclyVisible(') && str_contains($detail,'category_slug'),
 'category card hides invalid category link' => str_contains($products,'->sanitizeCategoryLink('),
 'quickAdd reads public eligibility before response' => str_contains($quick,'->eligibleIdsAmong([$productId])') && str_contains($quick,'http_response_code(404)'),
 'quickAdd preserves variants' => str_contains($quick,'listActiveForProductQuickAdd('),
 'home batch checks public product ids' => str_contains($home,'->eligibleIdsAmong($ids)') && str_contains($home,'new ProductReadRepository(db())'),
 'productRead shared source not replaced' => str_contains($home,'findManyForCart($ids)'),
 'facet uses B1 common predicate' => str_contains($facets,'->productVisibility()->predicate(\'p\')'),
 'facet full hierarchy filtered' => str_contains($facets,'visibleCategoryIds()'),
 'facet effective price for variants' => str_contains($facets,"p.sale_mode = 'variants'") && str_contains($facets,'NULLIF(v.price,0)'),
 'category landing sends scoped ids before paging' => str_contains($category,'category_scope_ids'),
 'scope includes deep descendants' => str_contains($policy,'function descendantCategoryIds('),
 'home youtube uses filtered productRowsById' => str_contains($home,'buildYoutubeUsedProducts($youtubeProductIds, $productRowsById)'),
 'legacy redirect remains' => str_contains($index,'// 0) LEGACY redirect'),
 'no forbidden write SQL in policy' => !preg_match('/\b(?:INSERT INTO|UPDATE products|DELETE FROM)\b/i',$policy),
];
foreach ($checks as $name=>$passed){
 if (!$passed){fwrite(STDERR,"[B1-contract] FAIL: $name\n");exit(1);}
}
echo '[B1-contract] OK: '.count($checks)." checks\n";
