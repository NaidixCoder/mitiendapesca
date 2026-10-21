<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$view=(string)file_get_contents($root.'/app/Views/pages/admin/dashboard/index.php');
$trend=(string)file_get_contents($root.'/app/Views/partials/admin/dashboard/trend.php');
$css=(string)file_get_contents($root.'/public_html/assets/css/admin-v3/pages/dashboard.css');
foreach(['pending-orders','av3-order-mobile','av3-order-mobile__middle','av3-order-mobile__date','av3-stock-mobile','av3-table-wrap','av3-summary','av3-dashboard__columns','av3-dashboard__column--primary','av3-dashboard__column--secondary','av3-stock-count--zero'] as $hook){
    if(!str_contains($view,$hook))throw new RuntimeException('Falta componente del Dashboard: '.$hook);
}
foreach(['trend-days','trend-metric','trend-svg','trend-chart-wrap','trend-message'] as $hook){
    if(!str_contains($trend,$hook))throw new RuntimeException('Se perdio un control del grafico: '.$hook);
}
foreach(['av3-orders','av3-alerts','av3-stock','av3-events'] as $module){
    if(substr_count($view,'class="av3-panel av3-card '.$module.'"')!==1 &&
       ($module!=='av3-orders'||substr_count($view,'class="av3-panel av3-card av3-orders"')!==1)){
        throw new RuntimeException('Modulo ausente o duplicado: '.$module);
    }
}
if(str_contains($view,'class="av3-extras"')||str_contains($view,'class="av3-todo-strip"')||
   str_contains($view,'class="av3-work"')||str_contains($view,'class="av3-bottom"')){
    throw new RuntimeException('El Dashboard conserva bloques descartados o grillas antiguas');
}
if(str_contains($css,'av3-extras')||str_contains($css,'av3-dashboard__heading')||
   str_contains($css,'av3-todo-strip')||str_contains($css,'.av3-work')||str_contains($css,'.av3-bottom')){
    throw new RuntimeException('El CSS del Dashboard conserva selectores antiguos');
}
foreach(['@media(min-width:900px)','@media(min-width:1200px)','@container (min-width:620px)','av3-dashboard__column{display:contents}','av3-order-mobile__middle'] as $rule){
    if(!str_contains($css,$rule))throw new RuntimeException('Falta regla responsive: '.$rule);
}
$posOrders=strpos($view,'<section id="pending-orders"');
$posStocks=strpos($view,'class="av3-panel av3-card av3-stock"');
$posAlerts=strpos($view,'class="av3-panel av3-card av3-alerts"');
$posEvents=strpos($view,'class="av3-panel av3-card av3-events"');
$posPerformance=strpos($view,'class="av3-performance"');
if($posOrders===false||$posStocks===false||$posAlerts===false||$posEvents===false||$posPerformance===false||
   !($posOrders<$posStocks&&$posStocks<$posAlerts&&$posAlerts<$posEvents&&$posEvents<$posPerformance)){
    throw new RuntimeException('Las columnas independientes no mantienen el orden acordado');
}
if(!str_contains($css,'#trend-message.is-empty-period')){
    throw new RuntimeException('Falta el estado de grafico vacio compacto');
}
echo "[dashboard-responsive] OK: dos columnas independientes, 4 KPI escritorio, pedidos compactos en movil, datos y JS conservados\n";
