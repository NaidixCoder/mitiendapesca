<?php
declare(strict_types=1);
$r=dirname(__DIR__,2);
$v=(string)file_get_contents($r.'/app/Views/partials/admin/sidebar_v2.php');
$c=(string)file_get_contents($r.'/public_html/assets/css/admin-v3/layout.css');
foreach(['av3-sidebar-link','av3-sidebar-label','av3-sidebar-icon'] as $class) {
 if(!str_contains($v,$class)||!str_contains($c,'.'.$class)) throw new RuntimeException('Contrato sidebar ausente: '.$class);
}
if(preg_match('/(?<![\\w-])av3-(?:link|label|icon)(?![\\w-])/',$v)) throw new RuntimeException('Clases genéricas nuevamente usadas en sidebar');
echo "[sidebar-css] OK: nombres aislados de los formularios\n";
