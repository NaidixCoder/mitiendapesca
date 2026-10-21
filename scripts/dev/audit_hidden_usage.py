from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
for root in [r/'app/Views',r/'public_html/assets/js']:
 counts=collections.Counter()
 for p in root.rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  text=p.read_text(encoding='utf8',errors='replace')
  n=len(re.findall(r'class\s*=\s*["\x27][^"\x27]*\bhidden\b',text))
  n+=len(re.findall(r'classList\.(?:add|remove|toggle|contains|replace)\(\s*["\x27]hidden["\x27]',text))
  n+=len(re.findall(r'className\s*=\s*["\x27][^"\x27]*\bhidden\b',text))
  if n:counts[p.relative_to(r).as_posix()]=n
 print(root.name,'FILES',len(counts),'OCC',sum(counts.values()))
 for x,n in counts.most_common(30):print(n,x)
