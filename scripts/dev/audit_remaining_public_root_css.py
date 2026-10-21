from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
for rel in ['css/app.css','css/aside.css','css/components/account.css']:
 p=r/'public_html/assets'/rel
 text=p.read_text(encoding='utf8')
 print(rel,p.stat().st_size)
 print('imports',re.findall(r'@import\s+url\(([^)]+)\)',text)[:15])
 urls=re.findall(r'url\(([^)]+)\)',text)
 print('other urls',[x for x in urls if not x.startswith(("'./","\"./"))][:15])
