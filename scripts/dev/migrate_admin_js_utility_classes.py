from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
root=r/'public_html/assets/js/admin'
names=['flex','inline-flex','items-center','justify-center','gap-2','gap-3','w-0','w-1','w-12','w-14','h-1','h-12','h-14','border-slate-200','border-slate-300','border-slate-900','px-1','py-0','bg-white']
css=(r/'public_html/assets/css/admin-v3/components/primitives.css')
c=css.read_text(encoding='utf8')
add={'w-0':'width:0','w-1':'width:4px','h-1':'height:4px','bg-white':'background:#fff'}
for name,rule in add.items():
 if not re.search(r'\.av3-'+re.escape(name)+r'(?![\w-])',c):
  c+='\nbody[data-page^="admin"] .av3-'+name+'{'+rule+'}\n'
css.write_text(c,encoding='utf8')
changed={}
def replace_tokens(seq):
 parts=re.split(r'(\s+)',seq)
 return ''.join(('av3-'+x if x in names else x) for x in parts)
for p in root.rglob('*.js'):
 s=p.read_text(encoding='utf8')
 old=s
 s=re.sub(r'class="([^"]*)"',lambda m:'class="'+replace_tokens(m.group(1))+'"',s)
 s=re.sub(r'(\.className\s*=\s*)(["\x27])([^\n"\x27]*)(["\x27])',lambda m:m.group(1)+m.group(2)+replace_tokens(m.group(3))+m.group(4),s)
 for line in s.splitlines():
  if 'classList.' not in line:continue
  for name in names:
   # Replace exact quoted tokens only on classList lines.
   newline=line.replace("'"+name+"'","'av3-"+name+"'").replace('"'+name+'"','"av3-'+name+'"')
   if newline!=line:s=s.replace(line,newline)
 if s!=old:
  p.write_text(s,encoding='utf8')
  changed[p.relative_to(r).as_posix()]=sum(s.count('av3-'+x)-old.count('av3-'+x) for x in names)
print('ADMIN_JS_CHANGED_FILES',len(changed),'TOKEN_REPLACEMENTS',sum(changed.values()))
for p,n in sorted(changed.items()):print(n,p)
