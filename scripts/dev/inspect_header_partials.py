from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
paths=[
'partials/admin/settings/general/header.php','partials/admin/settings/envios/header.php','partials/admin/settings/fiscal/header.php','partials/admin/settings/pagos/header.php','partials/admin/settings/mayoristas/header.php','partials/admin/settings/email_marketing/header.php','partials/admin/settings/index/header.php',
'partials/admin/marketing_campaigns/index/header.php','partials/admin/products/variants/header.php','partials/admin/facturacion/control/header.php']
for name in paths:
 p=r/'app/Views'/name
 s=p.read_text(encoding='utf8',errors='replace')
 print(name,' | ',re.sub(r'\s+',' ',s[:350]))
