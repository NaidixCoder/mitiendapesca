from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[r/'app/Views',r/'public_html/assets/js']
pat=re.compile(r'(?:class\s*=\s*["\x27]([^"\x27]*)|\.className\s*=\s*["\x27]([^"\x27]*)|\.classList\.(?:add|remove|toggle|contains)\(([^\)]*)\))',re.S)
util={'hidden','flex','grid','absolute','relative','fixed','sticky','inline-flex','inline-block','block','border','rounded','truncate','transition','transition-all','overflow-hidden','overflow-auto','overflow-y-auto','overflow-x-auto','object-cover','object-contain','aspect-square','animate-spin','shrink-0','flex-1','items-center','items-start','justify-between','justify-end','justify-center','w-full','h-full','min-w-0','max-w-full','bg-white','shadow-lg','opacity-90','cursor-pointer','pointer-events-none','select-none'}
hits=collections.Counter()
for root in roots:
 for p in root.rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  s=p.read_text(encoding='utf8',errors='replace')
  for m in pat.finditer(s):
   val=next((v for v in m.groups() if v is not None),'')
   for token in re.findall(r'[\w:/.\[\]%-]+',val):
    if token in util:hits[(p.relative_to(r).as_posix(),token)]+=1
print('UNPREFIXED_GENERIC_TOKEN_PAIRS',len(hits),'TOTAL',sum(hits.values()))
for (p,t),n in hits.most_common(100):print(n,t,p)
