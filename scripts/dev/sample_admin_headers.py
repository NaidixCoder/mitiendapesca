from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for name in ['app/Views/partials/admin/products/index/header.php','app/Views/partials/admin/attributes/index/header.php','app/Views/partials/admin/facturacion/control/header.php','app/Views/partials/admin/settings/general/header.php','app/Views/partials/admin/users/index/header.php','app/Views/partials/admin/imports/index/header.php','app/Views/pages/admin/facturacion/comprobantes/index.php']:
 p=r/name
 print('\n####',name,'\n',p.read_text(encoding='utf8')[:1450])
