from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
files=[r/'public_html/assets/css/admin-v3/components'/n for n in ('legacy-bridge.css','foundations.css','ui-admin-compat.css')]
for p in files:
 t=p.read_text(encoding='utf8')
 print(p.name,'bytes',len(t),'media',t.count('@media'),'selectors',t.count('{'))
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
for f in files:print('LOAD',f.name,h.find(f.name))
