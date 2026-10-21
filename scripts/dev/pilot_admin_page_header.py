from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
changes=[
('app/Views/pages/admin/orders/index.php','av3-page-head',"""<?php
$adminHeaderTitle='Pedidos';
$adminHeaderDescription='Seguimiento, filtros y acceso rápido a detalle.';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Pedidos']];
$adminHeaderActions='<a class="btn-secondary" href="'.e((string)($links['dashboard'] ?? url('/admin'))).'">Volver al dashboard</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>"""),
('app/Views/pages/admin/orders/show.php','av3-page-head',"""<?php
$adminHeaderTitle='Pedido #'.(int)$orderView['id'];
$adminHeaderDescription='Detalle operativo, pago, facturación y trazabilidad.';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Pedidos','href'=>url('/admin/orders')],['label'=>'Pedido #'.(int)$orderView['id']]];
$adminHeaderActions='<a class="btn-secondary" href="'.e((string)$orderView['index_url']).'">Volver a pedidos</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>"""),
('app/Views/pages/admin/seo/auditoria.php','av3-header',"""<?php
$adminHeaderTitle='Auditoría SEO';
$adminHeaderDescription='Productos analizados: '.(int)($total ?? 0);
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'SEO'],['label'=>'Auditoría']];
$adminHeaderActions='<a class="btn-ghost" href="'.url('/admin/products').'">Ir a productos</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>"""),
]
for filename,cls,replacement in changes:
 p=r/filename;s=p.read_text(encoding='utf8')
 pattern=r'<header class="'+re.escape(cls)+r'">.*?</header>'
 nxt,n=re.subn(pattern,lambda m:replacement,s,count=1,flags=re.S)
 assert n==1,filename
 p.write_text(nxt,encoding='utf8')
 print('MIGRATED',filename)
p=r/'app/Views/pages/admin/analytics/index.php'
s=p.read_text(encoding='utf8')
start=s.index('    <header class="av3-analytics__header')
end=s.index('    <?php require BASE_PATH',start)
rep="""    <?php
    $adminHeaderTitle='Analíticas';
    $adminHeaderDescription='Tráfico, comportamiento y conversión de tu tienda.';
    $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Analíticas']];
    $adminHeaderActions='<a class="av3-analytics__back av3-ui-btn" href="'.url('/admin').'">Volver al dashboard</a>';
    require BASE_PATH.'/app/Views/partials/admin/page_header.php';
    ?>
"""
p.write_text(s[:start]+rep+s[end:],encoding='utf8')
print('MIGRATED analytics')
