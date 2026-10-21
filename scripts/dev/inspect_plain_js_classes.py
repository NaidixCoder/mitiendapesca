from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
tokens=('hidden','flex','grid','block','inline','inline-flex','absolute','relative','fixed','sticky','border','group','container','mx-auto')
for t in tokens:
 examples=[];n=0
 for p in (r/'public_html/assets/js/admin').rglob('*.js'):
  for i,line in enumerate(p.read_text(encoding='utf8',errors='replace').splitlines(),1):
   if re.search(r'(?<![\w-])'+re.escape(t)+r'(?![\w-])',line):
    n+=1
    if len(examples)<10:examples.append((p.relative_to(r).as_posix(),i,line.strip()[:125]))
 print(t,n,examples)
