from pathlib import Path
import re,hashlib,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
root=r/'app/Views'
palette={
'slate':{50:'#f8fafc',100:'#f1f5f9',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a'},
'gray':{50:'#f9fafb',100:'#f3f4f6',200:'#e5e7eb',300:'#d1d5db',400:'#9ca3af',500:'#6b7280',600:'#4b5563',700:'#374151',800:'#1f2937',900:'#111827'},
'rose':{50:'#fff1f2',600:'#e11d48',700:'#be123c',800:'#9f1239'},
'red':{50:'#fef2f2',200:'#fecaca',600:'#dc2626',700:'#b91c1c',800:'#991b1b'},
'blue':{700:'#1d4ed8',800:'#1e40af'},
'teal':{700:'#0f766e'},
'emerald':{50:'#ecfdf5',500:'#10b981',600:'#059669',700:'#047857',800:'#065f46',900:'#064e3b'},
'amber':{100:'#fef3c7',200:'#fde68a',300:'#fcd34d',500:'#f59e0b',900:'#78350f',950:'#451a03'},
'indigo':{50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',600:'#4f46e5',700:'#4338ca'},
'sky':{500:'#0ea5e9'},
'green':{900:'#14532d'},
}
display={'block':'display:block','inline':'display:inline','inline-block':'display:inline-block','hidden':'display:none','absolute':'position:absolute','relative':'position:relative','fixed':'position:fixed','sticky':'position:sticky','flex-col-reverse':'flex-direction:column-reverse','font-mono':'font-family:ui-monospace,SFMono-Regular,Consolas,monospace','font-normal':'font-weight:400','font-bold':'font-weight:700','font-extrabold':'font-weight:800','tracking-wide':'letter-spacing:.025em','leading-tight':'line-height:1.25','leading-snug':'line-height:1.375','leading-relaxed':'line-height:1.625','leading-6':'line-height:24px','max-w-none':'max-width:none','w-auto':'width:auto','w-px':'width:1px','border-2':'border-width:2px;border-style:solid','font-medium':'font-weight:500','font-semibold':'font-weight:600'}
def convert(base):
 if base in display:return display[base]
 if base.startswith('leading-[') and base.endswith(']'):return 'line-height:'+base[9:-1]
 if base.startswith('grid-cols-[') and base.endswith(']'):return 'grid-template-columns:'+base[11:-1].replace('_',' ')
 if base.startswith('space-y-'):
  try:return 'margin-top:'+str(float(base[8:])*4)+'px'
  except:pass
 if base.startswith('z-'):
  v=base[2:].strip('[]')
  if v.isdecimal():return 'z-index:'+v
 if base.startswith('opacity-'):
  try:return 'opacity:'+str(int(base[8:])/100)
  except:pass
 if base.startswith('shadow-'):
  if base=='shadow-2xl':return 'box-shadow:0 25px 50px -12px rgb(0 0 0 / .25)'
 if base.startswith('gap-'):
  try:return 'gap:'+str(float(base[4:])*4)+'px'
  except:pass
 if base.startswith(('w-','h-','min-w-','max-w-')):
  for k,property in [('min-w-','min-width'),('max-w-','max-width'),('w-','width'),('h-','height')]:
   if base.startswith(k):
    x=base[len(k):]
    if x in ('full','none','auto'):
     return property+':'+{'full':'100%','none':'none','auto':'auto'}[x]
    if x in ('sm','md','lg','xl','2xl','3xl','4xl','5xl','6xl'):
     vals={'sm':'24rem','md':'28rem','lg':'32rem','xl':'36rem','2xl':'42rem','3xl':'48rem','4xl':'56rem','5xl':'64rem','6xl':'72rem'}
     return property+':'+vals[x]
    if x.startswith('[') and x.endswith(']'):
     val=x[1:-1].replace('_',' ')
     val=re.sub(r'100%-([0-9]+px)',r'calc(100% - \1)',val)
     return property+':'+val
    try:return property+':'+str(float(x)*4)+'px'
    except:pass
 if base.startswith(('mt-','mb-','ml-','mr-','pt-','pb-','pl-','pr-','px-','py-','p-')):
  for pref,props in [('mt-',('margin-top',)),('mb-',('margin-bottom',)),('ml-',('margin-left',)),('mr-',('margin-right',)),('pt-',('padding-top',)),('pb-',('padding-bottom',)),('pl-',('padding-left',)),('pr-',('padding-right',)),('px-',('padding-left','padding-right')),('py-',('padding-top','padding-bottom')),('p-',('padding',))]:
   if base.startswith(pref):
    try:
     val=str(float(base[len(pref):])*4)+'px'
     return ';'.join(prop+':'+val for prop in props)
    except:pass
 if base.startswith(('bg-','text-','border-')):
  pref,_,x=base.partition('-')
  prop={'bg':'background-color','text':'color','border':'border-color'}[pref]
  if x.startswith('t-'):prop='border-top-color';x=x[2:]
  elif x.startswith('b-'):prop='border-bottom-color';x=x[2:]
  opacity=1
  if '/' in x:
   x,alpha=x.rsplit('/',1)
   if alpha.isdecimal():opacity=int(alpha)/100
  color=None
  if x in ('white','black'):color={'white':'#fff','black':'#000'}[x]
  elif x=='brand':color='var(--v2-color-primary)'
  elif x.startswith('[color:var(') and x.endswith(')]'):color=x[len('[color:'):-1]
  else:
   match=re.fullmatch(r'([a-z]+)-(\d{2,3})',x)
   if match:color=palette.get(match[1],{}).get(int(match[2]))
  if color:
   if opacity<1:color='color-mix(in srgb,'+color+' '+str(int(opacity*100))+'%,transparent)'
   return prop+':'+color
 if base.startswith('text-[') and base.endswith(']'):
  return 'font-size:'+base[6:-1]
 return None
def candidate(token):
 return bool(re.match(r'^(?:sm:|md:|lg:|xl:|hover:|focus:|group-hover:)?(?:bg-|text-|border-|rounded|shadow|grid-cols-|flex|grid$|hidden$|block$|inline|fixed$|absolute$|relative$|items-|justify-|p[trblxy]?-[\d.]|m[trblxy]?-[\d.]|gap-[\d.]|space-[xy]-|w-|h-|min-w-|max-w-|overflow-|opacity-|z-|font-|leading-|tracking-)',token))
def name_for(token):
 slug=re.sub(r'[^a-zA-Z0-9-]+','-',token.replace(':','-').replace('[color:var(--','var-').replace(')]','')).strip('-').lower()
 if len(slug)>62:slug=slug[:54]+'-'+hashlib.sha1(token.encode()).hexdigest()[:7]
 return 'av3-'+slug
found=collections.Counter()
for p in root.rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(root).as_posix():continue
 for m in re.finditer(rb'class\s*=\s*"([^"]*)"',p.read_bytes()):
  for token in m.group(1).decode('latin1').split():
   token=token.strip("'")
   if candidate(token) and not token.startswith('av3-'):found[token]+=1
rules={};unhandled={}
for token in found:
 parts=token.split(':',1)
 var=parts[0] if len(parts)==2 and parts[0] in ('sm','md','lg','xl','hover','focus','group-hover') else None
 base=parts[1] if var else token
 css=convert(base)
 if css is None:unhandled[token]=found[token]
 else:rules[token]=(name_for(token),var,base,css)
pattern=re.compile(rb'class\s*=\s*"([^"]*)"')
modified=[]
for p in root.rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(root).as_posix():continue
 original=p.read_bytes()
 def transform(m):
  inside=m.group(1)
  for old,(new,_,_,__) in sorted(rules.items(),key=lambda x:-len(x[0])):
   inside=re.sub(rb'(?<![\w:-])'+re.escape(old.encode())+rb'(?=[\s\x27\x22<>]|$)',new.encode(),inside)
  return b'class="'+inside+b'"'
 result=pattern.sub(transform,original)
 if result!=original:p.write_bytes(result);modified.append(p.relative_to(r).as_posix())
dest=r/'public_html/assets/css/admin-v3/components/primitives.css'
with dest.open('a',encoding='utf8') as f:
 f.write('\n/* Last static class-attribute Tailwind replacements */\n')
 for token,(alias,var,base,css) in rules.items():
  sel='body[data-page^="admin"] .'+alias
  if base.startswith('space-y-'):sel+=' > :not([hidden]) ~ :not([hidden])'
  if var=='group-hover':sel='body[data-page^="admin"] .group:hover .'+alias
  if var in ('hover','focus'):sel+=':'+var
  rule=sel+'{'+css+'}'
  if var in ('sm','md','lg','xl'):rule='@media(min-width:'+str({'sm':640,'md':768,'lg':1024,'xl':1280}[var])+'px){'+rule+'}'
  f.write(rule+'\n')
print('STATIC_MIGRATED',len(modified),'files;',sum(found[t] for t in rules),'tokens;',len(rules),'rules')
print('UNHANDLED',unhandled)
(r/'docs/UNHANDLED_TAILWIND_STATIC.txt').write_text('\n'.join(x+' '+str(y) for x,y in unhandled.items()),encoding='utf8')
