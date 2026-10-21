from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
v=list((r/'app/Views/pages/admin').rglob('*.php'))+list((r/'app/Views/partials/admin').rglob('*.php'))
for p in v:
 s=p.read_text(encoding='utf8')
 if '<h1' in s and 'page_header.php' not in s:
  m=re.search(r'<h1\b[^>]*>(.*?)</h1>',s,re.S|re.I)
  val=re.sub('<[^>]+>','',m.group(1)) if m else ''
  print(str(p.relative_to(r)).replace('app/Views/',''), '::',repr(val[:70]))
