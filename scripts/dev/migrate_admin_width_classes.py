from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapper={'max-w-6xl':'av3-max-w-6xl','max-w-4xl':'av3-width-max-4xl','mx-auto':'av3-mx-auto','container':'av3-container','group':'av3-group'}
pattern=re.compile(rb'class\s*=\s*"([^"]*)"')
changes=0
for p in (r/'app/Views').rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(r/'app/Views').as_posix():continue
 src=p.read_bytes()
 def change(m):
  v=m.group(1)
  for a,b in mapper.items():
   v=re.sub(rb'(?<![\w:-])'+re.escape(a.encode())+rb'(?=[\s\x27\x22<>]|$)',b.encode(),v)
  return b'class="'+v+b'"'
 out=pattern.sub(change,src)
 if out!=src:p.write_bytes(out);changes+=1
layout=r/'public_html/assets/css/admin-v3/layout.css'
s=layout.read_text(encoding='utf8')
for a,b in mapper.items():
 s=s.replace('.'+a,'.'+b)
layout.write_text(s,encoding='utf8')
pr=r/'public_html/assets/css/admin-v3/components/primitives.css'
s=pr.read_text(encoding='utf8').replace('.group:hover','.av3-group:hover')
s+='''\n/* Merged layout-specific former Tailwind class tokens */
body[data-page^="admin"] .av3-mx-auto{margin-inline:auto}
body[data-page^="admin"] .av3-container{width:100%}
'''
pr.write_text(s,encoding='utf8')
print('SHELL_WIDTH_CLASSES_MIGRATED',changes,'views')
