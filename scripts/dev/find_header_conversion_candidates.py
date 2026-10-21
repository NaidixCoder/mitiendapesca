from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
files=list((r/'app/Views/pages/admin').rglob('*.php'))+list((r/'app/Views/partials/admin').rglob('*header.php'))
counter=collections.Counter()
for p in files:
 s=p.read_text(encoding='utf8',errors='replace')
 matches=re.findall(r'<header\b[^>]*>[\s\S]*?</header>',s,re.I)
 chosen=[m for m in matches if '<h1' in m]
 for m in chosen:
  if '<form' in m: typ='form'
  elif '<div' in m:typ='div'
  else:typ='other'
  counter[typ]+=1
  print(p.relative_to(r).as_posix(),typ,len(m))
print(counter)
