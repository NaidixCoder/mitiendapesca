from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for term in ('group','mx-auto','container'):
 found=[]
 for p in (r/'public_html/assets/js/admin').rglob('*.js'):
  t=p.read_text(encoding='utf8',errors='replace')
  if "'"+term+"'" in t or '"'+term+'"' in t:found.append(p.relative_to(r).as_posix())
 print(term,found[:15])
