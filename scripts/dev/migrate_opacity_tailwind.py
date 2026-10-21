from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
admin=r/'public_html/assets/css/admin-v3/components/primitives.css'
pub=r/'public_html/assets/css/v2/components/no-tailwind-public.css'
css=admin.read_text(encoding='utf8')
public_css=pub.read_text(encoding='utf8')
occ=collections.Counter()
roots=[r/'app/Views',r/'public_html/assets/js']
for root in roots:
 for p in root.rglob('*'):
  if p.suffix not in ('.js','.php'):continue
  s=p.read_text(encoding='utf8',errors='replace');old=s
  is_admin='/admin/' in '/'+p.relative_to(r).as_posix()
  prefix='av3-' if is_admin else 'site-'
  for n in ['50','60','70','90']:
   s=re.sub(r'(?<![\w-])opacity-'+n+r'(?![\w-])',prefix+'opacity-'+n,s)
  if is_admin:
   s=s.replace('hover:opacity-100','av3-hover-opacity-100')
   s=re.sub(r'(?<![\w-])justify-between(?![\w-])','av3-justify-between',s)
  if s!=old:
   p.write_text(s,encoding='utf8')
   occ[p.relative_to(r).as_posix()]+=1
for n in ['50','60','70','90']:
 name='av3-opacity-'+n
 if not re.search(r'\.'+name+r'(?![\w-])',css):
  css+='\nbody[data-page^="admin"] .'+name+'{opacity:.'+n+'}\n'
 name='site-opacity-'+n
 if not re.search(r'\.'+name+r'(?![\w-])',public_css):
  public_css+='\n.'+name+'{opacity:.'+n+'}\n'
if '.av3-hover-opacity-100:hover' not in css:css+='\nbody[data-page^="admin"] .av3-hover-opacity-100:hover{opacity:1}\n'
if '.av3-justify-between' not in css:css+='\nbody[data-page^="admin"] .av3-justify-between{justify-content:space-between}\n'
admin.write_text(css,encoding='utf8')
pub.write_text(public_css,encoding='utf8')
print('OPACITY_MIGRATED',len(occ),'files')
for p in sorted(occ):print(p)
