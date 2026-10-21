from pathlib import Path
import re,zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
paths=[r/'app/Views/partials/admin/sidebar_v2.php',r/'public_html/assets/css/admin-v3/layout.css']
replacements={'av3-link':'av3-sidebar-link','av3-label':'av3-sidebar-label','av3-icon':'av3-sidebar-icon'}
backup=Path(r'C:\xampp\mi-pesca-backups')/('sidebar_collision_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in paths:z.write(p,p.relative_to(r).as_posix())
for p in paths:
 s=p.read_text(encoding='utf8')
 for old,new in replacements.items():
  s=re.sub(r'(?<![\w-])'+re.escape(old)+r'(?![\w-])',new,s)
 p.write_text(s,encoding='utf8')
print('SIDEBAR_CLASS_NAMES_ISOLATED',backup)
