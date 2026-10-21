from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
v=r/'app/Views/pages/admin'
for p in sorted(v.rglob('*.php')):
 s=p.read_text(encoding='utf8')
 hs=re.findall(r'<header\b[^>]*class="([^"]*)"',s)
 h1=re.findall(r'<h1\b',s)
 cr=re.findall(r'(?:require .*?breadcrumbs\.php|render_partial\([^\n]*breadcrumbs)',s)
 part=re.findall(r"(?:require BASE_PATH\s*\.\s*'([^']*header[^']*\.php)'|render_partial\('([^']*header[^']*)')",s)
 print(str(p.relative_to(v)).ljust(49),'h1',len(h1),'crumb',len(cr),'headers',','.join(hs[:2])[:50],'partial',str(part[:1])[:85])
