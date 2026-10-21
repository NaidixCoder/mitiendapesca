<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$helpers=(string)file_get_contents($root.'/app/Support/helpers.php');
$layout=(string)file_get_contents($root.'/app/Views/layouts/admin.php');
$header=(string)file_get_contents($root.'/app/Views/partials/admin/page_header.php');
$subnav=(string)file_get_contents($root.'/app/Views/partials/admin/subnav.php');
$head=(string)file_get_contents($root.'/app/Views/partials/head.php');
$style=(string)file_get_contents($root.'/public_html/assets/css/admin-v3/components/master-shell.css');
foreach([
 ['Captura master',str_contains($helpers,"_admin_master_capture")],
 ['Vista layout',str_contains($helpers,"layouts/admin.php")],
 ['Sidebar central',substr_count($layout,"sidebar_v2.php")===1],
 ['Header central',str_contains($layout,"_admin_master_header")],
 ['Contenido central',str_contains($layout,'$adminMasterContent')],
 ['Captura header',str_contains($header,'ob_get_clean()')],
 ['Sidebar condicional',str_contains($subnav,"empty(\$GLOBALS['_admin_master_capture'])")],
 ['CSS único',substr_count($head,"'css/admin-v3/components/master-shell.css'")===1],
 ['Aislamiento CSS',str_contains($style,'.av3-master-workspace')],
] as [$label,$pass]){
 if(!$pass)throw new RuntimeException('Contrato admin master falló: '.$label);
}
echo "[admin-master] OK: layout, aside, encabezado y contenido con carga única\n";
