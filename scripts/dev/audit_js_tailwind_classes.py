from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
tokens=collections.Counter()
for p in (r/'public_html/assets/js').rglob('*.js'):
 s=p.read_text(encoding='utf8',errors='replace')
 for line in s.splitlines():
  if 'class=' not in line and 'className' not in line and 'classList' not in line:continue
  for cl in re.findall(r'(?<![\\w-])(?:flex|inline-flex|items-center|justify-center|rounded-xl|rounded-full|text-sm|text-xs|bg-white|border-slate-\d+|gap-\d+|px-\d+|py-\d+|w-\d+|h-\d+)\b',line):
   tokens[(p.relative_to(r).as_posix(),cl)]+=1
print('JS_DYNAMIC_CLASS_CANDIDATES',len(tokens))
for (file,cl),n in tokens.most_common(45):print(n,cl,file)
