from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
s=(r/'scripts/dev/migrate_admin_utilities_round2.py').read_text(encoding='utf8')
ns={};exec(s.split('files=[]')[0],ns)
aliases=[v[0] for v in ns['pairs'].values()]
find=[]
for p in (r/'app/Views').rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(r/'app/Views').as_posix():continue
 for n,line in enumerate(p.read_text(encoding='latin1').splitlines(),1):
  if 'class=' in line:continue
  for a in aliases:
   if re.search(r'(?<![\w-])'+re.escape(a)+r'(?![\w-])',line):
    find.append((p.relative_to(r).as_posix(),n,line.strip()[:180]));break
print('NON_CLASS_PHP_ALIAS_LINES',len(find));print(*find[:50],sep='\n')
