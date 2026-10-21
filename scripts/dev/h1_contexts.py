from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
for p in (r/'app/Views/pages/admin').rglob('*.php'):
 s=p.read_text(encoding='utf8',errors='replace')
 if 'partials/admin/page_header.php' in s or '<h1' not in s:continue
 m=re.search(r'<h1\b[^>]*>',s)
 context=s[max(0,m.start()-210):m.start()+260]
 print('\n',p.relative_to(r/'app/Views/pages/admin'),'\n',re.sub(r'\s+',' ',context)[:460])
