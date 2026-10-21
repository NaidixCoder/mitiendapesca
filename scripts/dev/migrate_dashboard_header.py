from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'app/Views/pages/admin/dashboard/index.php'
s=p.read_text(encoding='utf8')
old='''  <header class="av3-dashboard__heading">
    <div><p class="av3-dashboard__eyebrow">MI PESCA / INICIO</p><h1>Resumen general</h1></div>
    <span class="av3-dashboard__context">Estado de la tienda</span>
  </header>'''
new='''  <?php
  $adminHeaderTitle='Resumen general';
  $adminHeaderDescription='';
  $adminHeaderBreadcrumbs=[['label'=>'Inicio']];
  $adminHeaderActions='<span class="av3-dashboard__context">Estado de la tienda</span>';
  require BASE_PATH.'/app/Views/partials/admin/page_header.php';
  ?>'''
assert old in s
p.write_text(s.replace(old,new,1),encoding='utf8')
print('DASHBOARD_HEADER_MIGRATED')
