from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
m=re.search(r'\$publicStyles\s*=\s*\[(.*?)\];',h,re.S)
files=re.findall(r"'(css/[^']+\.css)'",m.group(1))
targets=[x for x in files if x.startswith(('css/v2/pages/public/','css/v2/layouts/public-','css/v2/components/'))]
for item in targets:
 consumers=[]
 for p in list((r/'app').rglob('*.php'))+list((r/'scripts').rglob('*.php'))+list((r/'public_html/assets/css').rglob('*.css')):
  if item in p.read_text(encoding='utf8',errors='replace'):consumers.append(p.relative_to(r).as_posix())
 if consumers:print(item,'CONSUMERS',consumers[:10])
print('MIGRATABLE',len(targets))
