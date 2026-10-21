from pathlib import Path
from zipfile import ZipFile
from datetime import datetime
import hashlib
r=Path(r'C:\xampp\htdocs\mi-pesca')
z=ZipFile(r/'mi-pesca.zip')
names={n.replace('\\','/').rstrip('/') for n in z.namelist() if not n.endswith('/')}
cur=[p for p in r.rglob('*') if p.is_file() and 'vendor' not in p.parts and 'storage' not in p.parts]
sus=[p for p in cur if (p.suffix.lower() in ('.py','.qss') or 'purchase_order' in p.name.lower() or 'conciliacion' in p.name.lower() or p.name=='container.py')]
print('SUSPECTS',len(sus))
for p in sus[:55]:
 rel=p.relative_to(r).as_posix()
 print('S',rel,'BASE',rel in names)
recent=[p for p in cur if datetime.fromtimestamp(p.stat().st_mtime).strftime('%Y-%m-%d')=='2026-10-17']
print('RECENT COUNT',len(recent))
for p in recent[:60]:
 rel=p.relative_to(r).as_posix()
 print('R',rel,'BASE',rel in names)
