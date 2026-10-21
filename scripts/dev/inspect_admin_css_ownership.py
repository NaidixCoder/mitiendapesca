from pathlib import Path
import re,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
head=r/'app/Views/partials/head.php';s=head.read_text(encoding='utf8')
m=re.search(r'\$adminStyles\s*=\s*\[(.*?)\];',s,re.S)
styles=re.findall(r"'(css/[^']+\.css)'",m.group(1))
for css in styles:
 p=r/'public_html/assets'/css
 print(css, 'size',p.stat().st_size,'imports',len(re.findall('@import',p.read_text(encoding='utf8',errors='replace'))))
