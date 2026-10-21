from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
for p in (r/'app/Views').rglob('*.php'):
 if '/admin/' in '/'+p.relative_to(r).as_posix():continue
 for n,line in enumerate(p.read_text(encoding='utf8',errors='replace').splitlines(),1):
  if 'class=' in line and re.search(r'\b(?:grid|truncate)\b',line):
   if any(t in line for t in [' grid','grid ','truncate']):
    print(p.relative_to(r).as_posix(),n,line.strip()[:210])
