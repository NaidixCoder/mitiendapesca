from pathlib import Path
import re,collections
R=Path(r'C:\xampp\htdocs\mi-pesca')
locations=[]
for root in [R/'app/Views',R/'public_html/assets/js']:
 for p in root.rglob('*'):
  if p.suffix not in ('.php','.js','.css'):continue
  try:s=p.read_text(encoding='utf8')
  except UnicodeError:continue
  for term in ['admin/breadcrumbs','partials/admin/breadcrumbs.php','components/page_header','av3-breadcrumb','admin-v3-page-header']:
   if term in s: locations.append((str(p.relative_to(R)),term,s.count(term)))
print('REFERENCES',len(locations))
for x in locations:print(' | '.join(map(str,x)))
