from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca')
names=['attributes/index.php','attributes/value_set_edit.php','attributes/value_set_items.php','audit/index.php','audit/logins.php','audit/show.php','facturacion/reportes/resumen.php','facturacion/reportes/saldo_iva.php','imports/view.php','marketing_campaigns/show.php','marketing_email/campaign_form.php','marketing_email/templates_show.php','products/form.php','settings/home_hero.php','settings/home_sections.php','settings/mayoristas.php']
for n in names:
 p=R/'app/Views/pages/admin'/n
 s=p.read_text(encoding='utf8',errors='replace')
 print('\n===== '+n+' =====\n'+s[:1350].replace('\r',''))
