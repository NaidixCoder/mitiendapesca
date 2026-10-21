from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca');freq=collections.Counter()
for p in (r/'app/Views').rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(r/'app/Views').as_posix():continue
 for m in re.finditer(rb'class\s*=\s*"([^"]*)"',p.read_bytes()):
  for c in m.group(1).split():
   t=c.decode('latin1')
   if re.match(r'^(?:sm:|md:|lg:|hover:|focus:)?(?:bg-|text-|border|rounded|shadow|grid-cols-|flex|grid$|hidden$|block$|inline|fixed$|absolute$|relative$|items-|justify-|p[trblxy]?-[\d.]|m[trblxy]?-[\d.]|gap-[\d.]|space-[xy]-|w-|h-|min-w-|max-w-|overflow-|opacity-|z-|font-|leading-|tracking-)',t) and not t.startswith('av3-'):freq[t]+=1
print('CANDIDATE_CLASSES',len(freq),'occurrences',sum(freq.values()))
for t,n in freq.most_common():print(t,n)
