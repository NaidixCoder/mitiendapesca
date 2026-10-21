<?php
declare(strict_types=1);
$r=dirname(__DIR__,2);
$layout=(string)file_get_contents($r.'/app/Views/layouts/admin.php');
$style=(string)file_get_contents($r.'/public_html/assets/css/admin-v3/components/master-shell.css');
$js=(string)file_get_contents($r.'/public_html/assets/js/admin/core/sidebar.js');
foreach(['av3-mobile-menu-toggle','av3-mobile-backdrop'] as $id){if(!str_contains($layout,$id))throw new RuntimeException('Falta navegación móvil: '.$id);}
foreach(['--v2-sidebar-offset:0px','translateX(-105%)','av3-mobile-nav-open','z-index:120'] as $rule){if(!str_contains($style,$rule))throw new RuntimeException('Falta estilo overlay: '.$rule);}
foreach(['Escape','aria-expanded','closeMobileMenu','mobileMedia'] as $hook){if(!str_contains($js,$hook))throw new RuntimeException('Falta comportamiento overlay: '.$hook);}
echo "[admin-mobile-nav] OK: sin sidebar comprimido en móvil, menú overlay y cierre accesible\n";
