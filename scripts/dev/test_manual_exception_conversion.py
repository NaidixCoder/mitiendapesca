from pathlib import Path
import sys
sys.path.insert(0,r'C:\xampp\htdocs\mi-pesca\scripts\dev')
from migrate_admin_headers_full import convert
R=Path(r'C:\xampp\htdocs\mi-pesca')
files=['marketing_campaigns/show/header.php','facturacion/reportes/resumen/content.php','facturacion/reportes/saldo_iva/header_filters.php','marketing_email/campaign_form/pre_form.php','audit/index/hero.php','audit/logins/header.php','attributes/index/header.php','imports/view/header.php']
for item in files:
 p=R/'app/Views/partials/admin'/item
 s=p.read_text(encoding='utf8',errors='replace')
 t,reason=convert(p,s)
 print(item,'convert',bool(t),'reason',reason,'bytes',len(s),'after',len(t or ''))
