from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
s=p.read_text(encoding='utf8')
start=s.index('@container (min-width:680px){')
end=s.index('/* The query container is .av3-dashboard itself:',start)
s=s[:start]+s[end:]
# Ensure stock table does not squeeze in intermediate widths.
s=s.replace('@container (min-width:780px) and (max-width:1049px){','@container (min-width:980px) and (max-width:1049px){')
p.write_text(s,encoding='utf8')
p=R/'scripts/build/dashboard_responsive_contract.php'
p.write_text("""<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$view=(string)file_get_contents($root.'/app/Views/pages/admin/dashboard/index.php');
$trend=(string)file_get_contents($root.'/app/Views/partials/admin/dashboard/trend.php');
$css=(string)file_get_contents($root.'/public_html/assets/css/admin-v3/pages/dashboard.css');
foreach(['pending-orders','av3-order-mobile','av3-stock-mobile','av3-table-wrap','av3-summary','av3-work','av3-bottom','av3-performance','av3-stock-count--zero'] as $hook){
    if(!str_contains($view,$hook))throw new RuntimeException('Dashboard perdio componente: '.$hook);
}
foreach(['trend-days','trend-metric','trend-svg','trend-chart-wrap','trend-message'] as $hook){
    if(!str_contains($trend,$hook))throw new RuntimeException('Dashboard perdio gancho de tendencia: '.$hook);
}
if(str_contains($view,'class="av3-extras"')||str_contains($view,'class="av3-todo-strip"')){
    throw new RuntimeException('Dashboard tiene indicadores secundarios duplicados');
}
if(str_contains($css,'av3-extras')||str_contains($css,'av3-dashboard__heading')||str_contains($css,'av3-todo-strip')){
    throw new RuntimeException('CSS legado de Dashboard aun presente');
}
if(substr_count($css,'@container (min-width:680px)')!==1 ||
   !str_contains($css,'@container (min-width:1050px)') ||
   !str_contains($css,'#trend-message.is-empty-period')){
    throw new RuntimeException('Faltan adaptaciones por ancho util o estado vacio compacto');
}
$work=strpos($view,'class="av3-work"');
$bottom=strpos($view,'class="av3-bottom"');
$performance=strpos($view,'class="av3-performance"');
if($work===false||$bottom===false||$performance===false||!($work<$bottom&&$bottom<$performance)){
    throw new RuntimeException('Jerarquia del dashboard desordenada');
}
echo "[dashboard-responsive] OK: datos preservados, sin duplicados, mobile-first por contenedor y grafico compacto\\n";
""",encoding='utf8')
print('CSS_DUPLICATE_REMOVED_AND_CONTRACT_UPDATED')
