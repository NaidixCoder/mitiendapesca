from pathlib import Path
import re,zipfile,datetime
R=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[R/'app/Views', R/'public_html/assets/js']
tokens={'sticky','truncate','cursor-pointer','select-none','pointer-events-none','object-cover','object-contain','shrink-0','transition','aspect-square','animate-spin','overflow-hidden','overflow-auto','overflow-y-auto','overflow-x-auto','border'}
css_rules={
'sticky':'position:sticky',
'truncate':'overflow:hidden;text-overflow:ellipsis;white-space:nowrap',
'cursor-pointer':'cursor:pointer',
'select-none':'user-select:none',
'pointer-events-none':'pointer-events:none',
'object-cover':'object-fit:cover',
'object-contain':'object-fit:contain',
'shrink-0':'flex-shrink:0',
'transition':'transition:all .15s ease',
'aspect-square':'aspect-ratio:1 / 1',
'animate-spin':'animation:ui-spin 1s linear infinite',
'overflow-hidden':'overflow:hidden',
'overflow-auto':'overflow:auto',
'overflow-y-auto':'overflow-y:auto',
'overflow-x-auto':'overflow-x:auto',
'border':'border:1px solid currentColor'
}
changed={}
def substitute(v,prefix):
 return ' '.join((prefix+x) if x in tokens else x for x in v.split())
for root in roots:
 for p in root.rglob('*'):
  if p.suffix not in ('.js','.php'):continue
  rel=p.relative_to(R).as_posix()
  if rel.startswith('app/Views/pdf/'):continue
  is_admin='/admin/' in '/'+rel or rel.startswith('public_html/assets/js/admin/')
  prefix='av3-' if is_admin else 'site-'
  s=p.read_text(encoding='utf8',errors='replace');before=s
  s=re.sub(r'(class\s*=\s*["\x27])([^"\x27]*)(["\x27])',lambda m:m.group(1)+substitute(m.group(2),prefix)+m.group(3),s)
  if p.suffix=='.js':
   s=re.sub(r'(\.className\s*=\s*["\x27])([^"\x27]*)(["\x27])',lambda m:m.group(1)+substitute(m.group(2),prefix)+m.group(3),s)
   s=re.sub(r'\.classList\.(?:add|remove|toggle|contains|replace)\s*\([^\)]*\)',lambda m:re.sub(r'(["\x27])('+'|'.join(re.escape(x) for x in sorted(tokens,key=len,reverse=True))+r')\1',lambda x:x.group(1)+prefix+x.group(2)+x.group(1),m.group(0)),s)
  if s!=before:changed[p]=s.encode('utf8')
adm=R/'public_html/assets/css/admin-v3/components/primitives.css'
pub=R/'public_html/assets/css/v2/components/no-tailwind-public.css'
for path,prefix in [(adm,'av3-'),(pub,'site-')]:
 s=path.read_text(encoding='utf8')
 for tok,rule in css_rules.items():
  name=prefix+tok
  if not re.search(r'\.'+re.escape(name)+r'(?![\w-])',s):
   selector='.'+name if prefix=='site-' else 'body[data-page^="admin"] .'+name+',body[data-page^="admin"].'+name
   s+='\n'+selector+'{'+rule+'}\n'
 if '@keyframes ui-spin' not in s:s+='\n@keyframes ui-spin{to{transform:rotate(360deg)}}\n'
 if s!=path.read_text(encoding='utf8'):changed[path]=s.encode('utf8')
backup=Path(r'C:\xampp\mi-pesca-backups')/('css_generic_classes_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in changed:z.write(p,p.relative_to(R).as_posix())
for p,b in changed.items():p.write_bytes(b)
print('SEMANTIC_UTILITIES_CHANGED',len(changed),'BACKUP',backup)
