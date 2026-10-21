from pathlib import Path
import re,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
source=(r/'scripts/dev/finish_admin_static_classes.py').read_text(encoding='utf8')
scope={}
exec(source.split('found=collections.Counter()')[0],scope)
convert=scope['convert'];alias_for=scope['name_for'];palette=scope['palette']
palette['red'][500]='#ef4444'
palette['green'][100]='#dcfce7'
palette['emerald'][100]='#d1fae5'
palette['rose'][100]='#ffe4e6'
palette['rose'][600]='#e11d48'
palette['rose'][200]='#fecdd3'
palette['rose'][300]='#fda4af'
palette['amber'][500]='#f59e0b'
def translate(t):
 parts=t.split(':',1)
 var=parts[0] if len(parts)==2 and parts[0] in ('sm','md','lg','xl','hover','focus','group-hover') else None
 base=parts[1] if var else t
 if base.startswith('col-span-'):
  v=base[9:]
  val='grid-column:1/-1' if v=='full' else ('grid-column:span '+v+'/span '+v if v.isdecimal() else None)
 elif base.startswith('bg-brand/'):
  pct=base.split('/')[-1]
  val='background-color:color-mix(in srgb,var(--v2-color-primary) '+pct+'%,transparent)' if pct.isdecimal() else None
 elif base.startswith('w-1/2'):
  val='width:50%'
 else:
  val=convert(base)
 return var,base,val
data=json.loads((r/'docs/AUDITORIA_UTILIDADES_DINAMICAS.json').read_text(encoding='utf8'))
todo={}
for t in data['count']:
 if t.startswith('av3-') or t in ('border-radius:16px','border-radius:','text-decoration:underline','font-family:','text-transform:','border-left:4px','border-top:1px','text-anchor'):continue
 var,base,css=translate(t)
 if css:todo[t]=(alias_for(t),var,base,css)
print('DYNAMIC_MAPPABLE',len(todo),'of',len(data['count']))
print('UNMAPPABLE',[x for x in data['count'] if x not in todo and not x.startswith('av3-')][:40])
padded=[x for x in sorted(todo,key=len,reverse=True) if '-' in x]
counts={};changed=[]
for folder in ('app/Views','public_html/assets/js/admin'):
 for p in (r/folder).rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  if folder=='app/Views' and '/admin/' not in '/'+p.relative_to(r/folder).as_posix():continue
  original=p.read_bytes();content=original
  for t in padded:
   alias=todo[t][0]
   content,n=re.subn(rb'(?<![\w:-])'+re.escape(t.encode())+rb'(?=[\s\x27\x22<>]|$)',alias.encode(),content)
   counts[t]=counts.get(t,0)+n
  if content!=original:p.write_bytes(content);changed.append(p.relative_to(r).as_posix())
cssfile=r/'public_html/assets/css/admin-v3/components/primitives.css'
s=cssfile.read_text(encoding='utf8')
append=['/* Dynamic PHP/JS class variants migrated to native V3 */']
used=[t for t,n in counts.items() if n]
for t in used:
 alias,var,base,decl=todo[t]
 if '.'+alias in s:continue
 sel='body[data-page^="admin"] .'+alias
 if var=='group-hover':sel='body[data-page^="admin"] .av3-group:hover .'+alias
 elif var in ('hover','focus'):sel+=':'+var
 elif base.startswith('space-y-'):sel+=' > :not([hidden]) ~ :not([hidden])'
 rule=sel+'{'+decl+'}'
 if var in ('sm','md','lg','xl'):rule='@media(min-width:'+str({'sm':640,'md':768,'lg':1024,'xl':1280}[var])+'px){'+rule+'}'
 append.append(rule)
s+='\n'+'\n'.join(append)+'\n'
cssfile.write_text(s,encoding='utf8')
print('DYNAMIC_MIGRATED',len(changed),'files',sum(counts.values()),'occurrences','new rules',len(append)-1)
