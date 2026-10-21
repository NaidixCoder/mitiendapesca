from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\css\v2')
names=['tokens.css','components/ui.css','components/ui-admin.css','layouts/admin-shell.css','components/admin-beta-bridge.css','components/buttons.css']
for name in names:
 p=r/name;s=p.read_text(encoding='utf-8')
 sels=[]
 for match in re.finditer(r'([^{}]+)\{',re.sub(r'/\*.*?\*/','',s,flags=re.S)):
  sel=match.group(1).strip()
  if not sel.startswith('@') and len(sel)<170:sels.extend(x.strip() for x in sel.split(','))
 counts=collections.Counter(sels)
 print(name,'lines',len(s.splitlines()),'selectors',len(sels),'duplicates',sum(n>1 for n in counts.values()),'important',s.count('!important'),'media',s.count('@media'))
 print(' repeated',[(k,v) for k,v in counts.items() if v>1][:12])
