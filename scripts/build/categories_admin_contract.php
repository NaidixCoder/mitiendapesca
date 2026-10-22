<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/app/Views/pages/admin/categories/index.php');
$form = file_get_contents($root.'/app/Views/pages/admin/categories/form.php');
$controller = file_get_contents($root.'/app/Controllers/Admin/CategoriesController.php');
$css = file_get_contents($root.'/public_html/assets/css/admin-v3/pages/categories.css');
$guard = file_get_contents($root.'/app/Services/Admin/Categories/CategoryHierarchyGuard.php');
$service = file_get_contents($root.'/app/Services/Admin/Categories/CategoriesIndexService.php');
$toggle = file_get_contents($root.'/app/Services/Catalog/CategoryAdminService.php');
$visibility = file_get_contents($root.'/app/Services/Admin/Categories/CategoryVisibilityPolicyService.php');
$toggleView = file_get_contents($root.'/app/Views/partials/admin/categories/toggle_action.php');
$productFormJs = file_get_contents($root.'/public_html/assets/js/admin/product_form/index.js');
$productBasics = file_get_contents($root.'/app/Views/partials/admin/products/tab_basicos.php');
$quick = substr($controller, strpos($controller, '  public function quick(): void {'));
$saveService = file_get_contents($root.'/app/Services/Admin/Categories/CategorySaveService.php');
$checks = [
    'tabla desktop y filas móviles' => str_contains($view,'av3-categories__table') && str_contains($view,'av3-categories__mobile'),
    'paginación parametrizada' => str_contains($service, 'LIMIT :limit OFFSET :offset') && str_contains($service, "PDO::PARAM_INT"),
    'métricas por agregación' => str_contains($service, 'GROUP BY category_id') && str_contains($service, 'GROUP BY parent_id'),
    'control de ciclos' => str_contains($guard,'validateParent') && str_contains($controller,'validateParent($id, $parent)'),
    'SEO compartido y CSRF' => str_contains($form,"seo/entity_panel") && str_contains($form,'csrf_field()'),
    'toggle protegido' => str_contains($toggle, '->toggle(') && str_contains($visibility,'beginTransaction()') && str_contains($guard,'FOR UPDATE'),
    'estilos encapsulados' => str_contains($css,'.av3-categories{') && str_contains($css,'@container') && !str_contains($css,'!important'),
    'sin clases antiguas' => !str_contains($view,'av3-flex ') && !str_contains($form,'av3-grid '),
    'portada con límites' => str_contains($controller,'4 * 1024 * 1024') && str_contains($controller,'6000'),
    'controles V3 reutilizados' => str_contains($view,'av3-ui-control') && str_contains($form,'av3-ui-field') && str_contains($view,'av3-ui-table') && str_contains($view,'av3-ui-badge'),
    'botones V3 sin duplicación' => str_contains($form,'av3-ui-btn') && str_contains($toggleView,'av3-ui-btn--danger') && !str_contains($view,'av3-categories__button') && !str_contains($form,'av3-categories__button') && !str_contains($css,'.av3-categories__button'),
    'formulario ancho 860' => str_contains($css,'max-width:860px') && str_contains($css,'av3-layout--form'),
    'estados explícitos/idempotentes' => str_contains($visibility, 'function setActive(') && str_contains($visibility, "'changed' => false"),
    'única política para toggle' => str_contains($toggle, 'CategoryVisibilityPolicyService') && str_contains($toggle, '->toggle(') && !str_contains($toggle, 'UPDATE categories'),
    'lock y rollback de jerarquía' => str_contains($guard, 'FOR UPDATE') && str_contains($visibility, 'rollBack()') && str_contains($visibility, 'snapshot(true)'),
    'guardia de ancestros y reparentado' => str_contains($guard, 'validatePlacement(') && str_contains($guard, 'ancestro inactivo') && str_contains($guard, 'descendantIdsFromSnapshot'),
    'save con orquestador transaccional' => str_contains($controller, 'new \\App\\Services\\Admin\\Categories\\CategorySaveService($db)') && str_contains($saveService, '$this->db->beginTransaction()') && str_contains($saveService, '$this->db->commit()'),
    'save sin UPDATE directo de estado' => !str_contains($controller, 'sort=?,is_active=? WHERE id=?') && str_contains($saveService, "'UPDATE categories SET name=?,slug=?,parent_id=?,meta_title=?,meta_description=?,seo_text=?,cover_image=?,sort=? WHERE id=?'"),
    'save con rollback y auditoría postcommit' => str_contains($saveService, '$this->db->rollBack()') && str_contains($saveService, '$this->db->commit()') && str_contains($controller, "'cascade_ids'=>") && str_contains($controller, 'Audit::log'),
    'SEO dentro de la misma transacción' => str_contains($saveService, 'registerSlug(') && str_contains($saveService, 'recordCategorySlugChange(') && str_contains($saveService, 'saveSettings(') && str_contains($saveService, 'saveForCategory('),
    'historial protegido contra cambios de propietario' => str_contains($saveService, 'seo_slug_history') && str_contains($saveService, 'FOR UPDATE') && str_contains($saveService, 'historial SEO de otra categoría'),
    'regreso A-B-A sin redirect del canonical' => str_contains($saveService, "DELETE FROM seo_redirects WHERE from_path=?") && str_contains($saveService, "'/categoria?slug='."),
    'staging y rollback de portada' => str_contains($controller, 'storage/category-cover-staging') && str_contains($saveService, '@rename(') && str_contains($saveService, '@unlink($publishedFile)') && str_contains($saveService, '@unlink($stage)'),
    'quick devuelve reused/created explicitos' => str_contains($quick, "'created'=>false") && str_contains($quick, "'created'=>true") && str_contains($quick, "'parent_id'=>"),
    'quick conflicto slug-jerarquia HTTP 409' => str_contains($quick, 'category_slug_parent_conflict') && str_contains($quick, 'category_inactive_conflict') && str_contains($quick, '$reply(409,'),
    'quick lock jerarquico y retries unique' => str_contains($quick, 'snapshot(true)') && str_contains($quick, 'validatePlacement(0, $parentId, 1, $tree)') && str_contains($quick, '$mysqlCode === 1062'),
    'Productos envia padre y conserva modal ante error' => str_contains($productFormJs, "fd.append('parent_id', parentSel.value || '0')") && str_contains($productFormJs, 'document.body.appendChild(modal)') && str_contains($productFormJs, "if (!resp.ok)"),
    'modal categorias accesible sin cambiar marcas' => str_contains($productBasics, 'aria-labelledby="category-quick-title"') && str_contains($productBasics, 'id="category-quick-error"') && str_contains($productBasics, 'role="alert"'),
];
foreach ($checks as $name=>$ok) {
    if (!$ok) { fwrite(STDERR, '[categories-contract] ERROR: '.$name."\n"); exit(1); }
}
echo '[categories-contract] OK: '.count($checks)." contratos de Categorías Admin V3\n";
