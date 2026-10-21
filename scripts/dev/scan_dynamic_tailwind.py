from pathlib import Path
import re,collections,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
pattern=re.compile(rb'(?<![\w:-])((?:(?:sm|md|lg|xl|hover|focus|group-hover):)?(?:bg|text|border|rounded|shadow|grid-cols|col-span|font|space-y|px|py|p|gap|w|h|max-w|min-w|mt|mb|flex)(?:-[A-Za-z0-9_\[\].:/()%,-]+)+)(?=[\s\x27\x22<>]|$)')
cnt=collections.Counter();samples={}
for folder in ('app/Views','public_html/assets/js/admin'):
 for p in (r/folder).rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  if folder=='app/Views' and '/admin/' not in '/'+p.relative_to(r/folder).as_posix():continue
  for line in p.read_bytes().splitlines():
   for m in pattern.finditer(line):
    token=m.group(1).decode('latin1')
    if token.startswith('av3-'):continue
    cnt[token]+=1
    samples.setdefault(token,(p.relative_to(r).as_posix(),line.strip()[:170].decode('latin1')))
print('DYNAMIC_CANDIDATES',len(cnt),'occurrences',sum(cnt.values()))
for token,n in cnt.most_common(95):print(n,token,samples[token][0])
(r/'docs/AUDITORIA_UTILIDADES_DINAMICAS.json').write_text(json.dumps({'count':dict(cnt),'samples':samples},ensure_ascii=False,indent=2),encoding='utf8')
