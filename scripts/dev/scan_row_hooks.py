from pathlib import Path
import re,collections,json
R=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[R/'app/Views',R/'public_html/assets/js']
v=[]
for root in roots:
 for p in root.rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  txt=p.read_text(encoding='utf8',errors='replace')
  if 'admin-row-link' in txt:v.append((str(p.relative_to(R)),txt.count('admin-row-link')))
print('ROW_LINK_CONSUMERS',len(v),v)
