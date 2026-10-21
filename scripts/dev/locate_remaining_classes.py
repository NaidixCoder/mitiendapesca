from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
for p in (r/'app/Views').rglob('*.php'):
 text=p.read_text(encoding='utf8',errors='replace')
 matches=[]
 for n,line in enumerate(text.splitlines(),1):
  if 'class=' not in line:continue
  if re.search(r'(?:text-sm|rounded-xl|space-y-2|bg-\[color:var)',line):matches.append(n)
 if matches:print(str(p.relative_to(r)),matches[:8])
