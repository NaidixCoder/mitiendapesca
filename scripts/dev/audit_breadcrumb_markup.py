from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca')
for p in (R/'app/Views').rglob('*.php'):
 try:s=p.read_text(encoding='utf8')
 except UnicodeError:continue
 for token in ['aria-label="breadcrumb"','aria-label="Ruta de navegación"','av3-breadcrumb','breadcrumb-item','admin_crumbs(']:
  if token in s and token!='admin_crumbs(':
   print(p.relative_to(R),token,s.count(token))
