from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
m={
'gap-1':('av3-gap-1','gap:4px'),
'gap-2':('av3-gap-2','gap:8px'),
'gap-3':('av3-gap-3','gap:12px'),
'gap-4':('av3-gap-4','gap:16px'),
'gap-6':('av3-gap-6','gap:24px'),
'p-2':('av3-pad-2','padding:8px'),
'p-3':('av3-pad-3','padding:12px'),
'p-4':('av3-pad-4','padding:16px'),
'p-6':('av3-pad-6','padding:24px'),
'px-2':('av3-pad-x2','padding-left:8px;padding-right:8px'),
'px-3':('av3-pad-x3','padding-left:12px;padding-right:12px'),
'px-4':('av3-pad-x4','padding-left:16px;padding-right:16px'),
'py-1':('av3-pad-y1','padding-top:4px;padding-bottom:4px'),
'py-2':('av3-pad-y2','padding-top:8px;padding-bottom:8px'),
'py-3':('av3-pad-y3','padding-top:12px;padding-bottom:12px'),
'py-4':('av3-pad-y4','padding-top:16px;padding-bottom:16px'),
'mt-2':('av3-space-top-2','margin-top:8px'),
'mt-4':('av3-space-top-4','margin-top:16px'),
'mb-2':('av3-space-bottom-2','margin-bottom:8px'),
'mb-4':('av3-space-bottom-4','margin-bottom:16px'),
}
root=r/'app/Views'
counts=collections.Counter();nfiles=0
for p in root.rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(root).as_posix():continue
 b=p.read_bytes();old=b
 for k,(name,_) in m.items():
  pat=rb'(?<![\w:-])'+re.escape(k.encode())+rb'(?=[\s\x27\x22<>]|$)'
  b,n=re.subn(pat,name.encode(),b);counts[k]+=n
 if b!=old:p.write_bytes(b);nfiles+=1
css=r/'public_html/assets/css/admin-v3/components/primitives.css'
with css.open('a',encoding='utf8') as f:
 f.write('\n/* Design-system spacing primitives replacing utilities */\n')
 for token,(name,decl) in m.items():f.write('body[data-page^="admin"] .'+name+'{'+decl+'}\n')
print('SPACING_MIGRATED',nfiles,sum(counts.values()),dict(counts))
