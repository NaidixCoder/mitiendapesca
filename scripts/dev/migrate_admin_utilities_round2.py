from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
pairs={
'flex':('av3-flex','display:flex'),
'inline-flex':('av3-inline-flex','display:inline-flex'),
'grid':('av3-grid','display:grid'),
'items-center':('av3-items-center','align-items:center'),
'items-start':('av3-items-start','align-items:flex-start'),
'items-end':('av3-items-end','align-items:flex-end'),
'justify-between':('av3-justify-between','justify-content:space-between'),
'justify-end':('av3-justify-end','justify-content:flex-end'),
'justify-center':('av3-justify-center','justify-content:center'),
'justify-start':('av3-justify-start','justify-content:flex-start'),
'flex-wrap':('av3-flex-wrap','flex-wrap:wrap'),
'flex-col':('av3-flex-col','flex-direction:column'),
'flex-row':('av3-flex-row','flex-direction:row'),
'flex-1':('av3-flex-1','flex:1 1 0%'),
'grid-cols-1':('av3-grid-cols-1','grid-template-columns:repeat(1,minmax(0,1fr))'),
'grid-cols-2':('av3-grid-cols-2','grid-template-columns:repeat(2,minmax(0,1fr))'),
'grid-cols-3':('av3-grid-cols-3','grid-template-columns:repeat(3,minmax(0,1fr))'),
'grid-cols-4':('av3-grid-cols-4','grid-template-columns:repeat(4,minmax(0,1fr))'),
'grid-cols-5':('av3-grid-cols-5','grid-template-columns:repeat(5,minmax(0,1fr))'),
'grid-cols-6':('av3-grid-cols-6','grid-template-columns:repeat(6,minmax(0,1fr))'),
'grid-cols-12':('av3-grid-cols-12','grid-template-columns:repeat(12,minmax(0,1fr))'),
'min-w-0':('av3-min-w-0','min-width:0'),
'min-w-full':('av3-min-w-full','min-width:100%'),
'text-right':('av3-text-right','text-align:right'),
'text-left':('av3-text-left','text-align:left'),
'text-center':('av3-text-center','text-align:center'),
'border':('av3-border','border-width:1px;border-style:solid'),
'border-b':('av3-border-bottom','border-bottom-width:1px;border-bottom-style:solid'),
'border-t':('av3-border-top','border-top-width:1px;border-top-style:solid'),
'rounded-2xl':('av3-radius-xl','border-radius:16px'),
'rounded-full':('av3-radius-full','border-radius:9999px'),
'rounded':('av3-radius-sm','border-radius:4px'),
'rounded-md':('av3-radius-md','border-radius:6px'),
'text-xl':('av3-type-xl','font-size:20px;line-height:28px'),
'text-base':('av3-type-base','font-size:16px;line-height:24px'),
'text-lg':('av3-type-lg','font-size:18px;line-height:28px'),
'text-2xl':('av3-type-2xl','font-size:24px;line-height:32px'),
'text-[11px]':('av3-type-micro','font-size:11px'),
'mb-1':('av3-space-bottom-1','margin-bottom:4px'),
'mb-3':('av3-space-bottom-3','margin-bottom:12px'),
'p-5':('av3-pad-5','padding:20px'),
'py-1.5':('av3-pad-y1half','padding-top:6px;padding-bottom:6px'),
'py-0.5':('av3-pad-yhalf','padding-top:2px;padding-bottom:2px'),
'py-6':('av3-pad-y6','padding-top:24px;padding-bottom:24px'),
'px-5':('av3-pad-x5','padding-left:20px;padding-right:20px'),
'pr-3':('av3-pad-right-3','padding-right:12px'),
'pr-4':('av3-pad-right-4','padding-right:16px'),
'pl-5':('av3-pad-left-5','padding-left:20px'),
}
colors={
'slate-400':'#94a3b8','slate-500':'#64748b','slate-600':'#475569','slate-700':'#334155','slate-800':'#1e293b','slate-900':'#0f172a',
'gray-400':'#9ca3af','gray-500':'#6b7280','gray-600':'#4b5563','gray-700':'#374151','gray-200':'#e5e7eb','gray-100':'#f3f4f6',
'slate-50':'#f8fafc','slate-100':'#f1f5f9','slate-200':'#e2e8f0','white':'#fff',
'amber-50':'#fffbeb','amber-200':'#fde68a','amber-700':'#b45309','amber-800':'#92400e','amber-900':'#78350f',
'red-600':'#dc2626','red-700':'#b91c1c','emerald-50':'#ecfdf5','emerald-200':'#a7f3d0','emerald-600':'#059669','emerald-700':'#047857',
}
for col,code in colors.items():
 for prefix,decl in [('text','color'),('bg','background-color'),('border','border-color')]:
  token=prefix+'-'+col
  pairs[token]=('av3-'+prefix+'-'+col,decl+':'+code)
for var in ['muted','admin-muted','fg','line-soft','admin-border','border','surface','danger']:
 for pref,property in [('text','color'),('border','border-color'),('bg','background-color')]:
  token=pref+'-[color:var(--'+var+')]'
  pairs[token]=('av3-'+pref+'-var-'+var,property+':var(--'+var+')')
spacings={1:'4px',2:'8px',3:'12px',4:'16px',5:'20px',6:'24px'}
for n,px in spacings.items():
 for direction,property in [('top','margin-top'),('bottom','margin-bottom')]:
  token=('mt-' if direction=='top' else 'mb-')+str(n)
  pairs.setdefault(token,('av3-space-'+direction+'-'+str(n),property+':'+px))
# tokens with breakpoints or pseudo-classes become own explicit CSS rules, so they do not rely on Tailwind.
variants=['sm','md','lg','xl','hover','focus']
files=[];count=collections.Counter();used={}
for root in ('app/Views','public_html/assets/js/admin'):
 for p in (r/root).rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  if root=='app/Views' and '/admin/' not in '/'+p.relative_to(r/root).as_posix():continue
  data=p.read_bytes();old=data
  for token,(alias,style) in pairs.items():
   for var in variants:
    original=(var+':'+token).encode()
    dest=('av3-'+var+'-'+alias[4:]).encode()
    pattern=rb'(?<![\w:-])'+re.escape(original)+rb'(?=[\s\x27\x22<>]|$)'
    data,n=re.subn(pattern,dest,data)
    if n:used[dest.decode()]=(var,style);count[var+':'+token]+=n
   pattern=rb'(?<![\w:-])'+re.escape(token.encode())+rb'(?=[\s\x27\x22<>]|$)'
   data,n=re.subn(pattern,alias.encode(),data)
   if n:used[alias]=(None,style);count[token]+=n
  if data!=old:p.write_bytes(data);files.append(p.relative_to(r).as_posix())
base=r/'public_html/assets/css/admin-v3/components/primitives.css'
with base.open('a',encoding='utf8') as f:
 f.write('\n/* Admin V3 design primitives — structured layout and color tokens */\n')
 for alias,(var,style) in used.items():
  sel='body[data-page^="admin"] .'+alias
  if var in ('hover','focus'):sel+=':'+var;rule=sel+'{'+style+'}'
  elif var in ('sm','md','lg','xl'):
   width={'sm':640,'md':768,'lg':1024,'xl':1280}[var]
   rule='@media(min-width:'+str(width)+'px){'+sel+'{'+style+'}}'
  else:rule=sel+'{'+style+'}'
  f.write(rule+'\n')
print('ROUND2_MIGRATED',len(files),'files;',sum(count.values()),'occurrences;',len(used),'CSS rules; sample:',count.most_common(18))
