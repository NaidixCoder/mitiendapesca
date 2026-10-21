from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
v=r/'app/Views'
pubfiles=[p for p in v.rglob('*.php') if '/admin/' not in '/'+p.relative_to(v).as_posix()]
css=''.join(p.read_text(encoding='utf8',errors='replace') for p in (r/'public_html/assets/css').rglob('*.css'))
classp=re.compile(r'class\s*=\s*["\x27]([^"\x27]+)')
counter=collections.Counter()
for p in pubfiles:
 for attr in classp.findall(p.read_text(encoding='utf8',errors='replace')):
  for cl in attr.split():
   if ('<' in cl or '?' in cl or '$' in cl or '{' in cl or '=' in cl):continue
   counter[cl]+=1
suspects=['hidden','grid','flex','inline-flex','absolute','relative','fixed','sticky','block','inline-block','rounded','rounded-full','rounded-xl','border','shadow','truncate','transition','w-full','h-full','max-w-lg','mx-auto','items-center','justify-between','justify-center','gap-2','gap-3','overflow-hidden','font-semibold','font-medium','text-center','text-sm','text-xs','py-2','px-3','py-3','p-4','p-6']
for x in suspects:
 if counter[x]:
  defined=bool(re.search(r'\.'+re.escape(x)+r'(?![\w-])',css))
  print(x,'HTML',counter[x],'CSS_DEFINED',defined)
