from pathlib import Path
import sys,shutil,datetime
sys.path.insert(0,r'C:\xampp\htdocs\mi-pesca\scripts\dev')
from migrate_admin_headers_full import convert
R=Path(r'C:\xampp\htdocs\mi-pesca')
paths=['marketing_campaigns/show/header.php','facturacion/reportes/resumen/content.php','facturacion/reportes/saldo_iva/header_filters.php','marketing_email/campaign_form/pre_form.php','imports/view/header.php']
changes={}
for rel in paths:
 p=R/'app/Views/partials/admin'/rel
 s=p.read_text(encoding='utf8')
 t,reason=convert(p,s)
 assert t, (rel,reason)
 changes[p]=t
back=Path(r'C:\xampp\mi-pesca-backups')/('header_exception_batch_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for p,s in changes.items():
 bp=back/p.relative_to(R);bp.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,bp)
 p.write_text(s,encoding='utf8')
 print('MIGRATED',p.relative_to(R))
print('BACKUP',back)
