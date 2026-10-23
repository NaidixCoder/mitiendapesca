<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
$GLOBALS['_admin_master_capture'] = true;
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $p): string { return $p; }
function asset(string $p): string { return 'file:///C:/xampp/htdocs/mi-pesca/public_html/assets/' . $p; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="synthetic">'; }
function flash(string $type): ?string { return null; }
function admin_crumbs(string $key): array { return [['label'=>'Inicio','href'=>'/admin'],['label'=>'Etiquetas','href'=>'/admin/catalog-tags']]; }
$groups = [['id'=>1,'name'=>'Modalidad','slug'=>'modalidad','sort'=>0,'is_active'=>1,'tags_count'=>2],['id'=>2,'name'=>'Formato','slug'=>'formato','sort'=>1,'is_active'=>0,'tags_count'=>1]];
$tags = [['id'=>1,'name'=>'Spinning','slug'=>'spinning','sort'=>0,'is_active'=>1,'group_name'=>'Modalidad','products_count'=>9],['id'=>2,'name'=>'Baitcast','slug'=>'baitcast','sort'=>1,'is_active'=>1,'group_name'=>'Modalidad','products_count'=>3],['id'=>3,'name'=>'Telescópica','slug'=>'telescopica','sort'=>0,'is_active'=>0,'group_name'=>'Formato','products_count'=>0]];
$groupRow=null; $tagRow=null;
ob_start(); require BASE_PATH . '/app/Views/pages/admin/catalog_tags/index.php'; $body=ob_get_clean();
$css='file:///C:/xampp/htdocs/mi-pesca/public_html/assets/css/admin-v3/';
echo '<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
foreach (['tokens.css','base.css','components/design-system.css','components/page-header.css'] as $file) { echo '<link rel="stylesheet" href="'.$css.$file.'">';}
echo '<style>body{margin:0;background:#f6f8fa;font-family:Segoe UI,Arial,sans-serif}.av3-master-content{padding:20px;max-width:1440px;margin:auto}.av3-wrap{width:100%}</style></head><body data-page="admin-catalog-tags"><main class="av3-master-content">'.$GLOBALS['_admin_master_header'].$body.'</main></body></html>';
