from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for name in ('av3-border','av3-flex','av3-grid','av3-space-top-1'):
 hits=[]
 for p in (r/'app/Views').rglob('*.php'):
  if '/admin/' not in '/'+p.relative_to(r/'app/Views').as_posix():continue
  for i,line in enumerate(p.read_text(encoding='latin1').splitlines(),1):
   if name in line and 'class=' not in line:
    hits.append((p.relative_to(r).as_posix(),i,line.strip()[:145]))
 print(name,'without class= same-line:',len(hits),hits[:18])
