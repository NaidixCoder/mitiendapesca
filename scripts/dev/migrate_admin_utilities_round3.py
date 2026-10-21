from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
rules={}
for n,px in {0:0,1:4,2:8,3:12,4:16,5:20,6:24,8:32,10:40,12:48,16:64,24:96,32:128,40:160}.items():
 for prefix,prop in [('w','width'),('h','height')]:
  rules[prefix+'-'+str(n)]=('av3-size-'+prefix+'-'+str(n),prop+':'+str(px)+'px')
for n in (0,1,2,3,4,5,6):
 px=n*4
 for k,prop in [('ml','margin-left'),('mr','margin-right'),('mt','margin-top'),('mb','margin-bottom'),('pl','padding-left'),('pr','padding-right'),('pt','padding-top'),('pb','padding-bottom')]:
  rules.setdefault(k+'-'+str(n),('av3-'+k+'-'+str(n),prop+':'+str(px)+'px'))
 for k,decl in [
  ('mx','margin-left:'+str(px)+'px;margin-right:'+str(px)+'px'),
  ('my','margin-top:'+str(px)+'px;margin-bottom:'+str(px)+'px'),
  ('px','padding-left:'+str(px)+'px;padding-right:'+str(px)+'px'),
  ('py','padding-top:'+str(px)+'px;padding-bottom:'+str(px)+'px'),
  ('p','padding:'+str(px)+'px'),
 ]:rules.setdefault(k+'-'+str(n),('av3-'+k+'-'+str(n),decl))
for n,px in {'0.5':2,'1.5':6,'2.5':10,'3.5':14}.items():
 for k in ('py','px','p','mt','mb'):
  x={'p':'padding:'+str(px)+'px','mt':'margin-top:'+str(px)+'px','mb':'margin-bottom:'+str(px)+'px','px':'padding-inline:'+str(px)+'px','py':'padding-block:'+str(px)+'px'}[k]
  rules.setdefault(k+'-'+n,('av3-'+k+'-'+n.replace('.','_'),x))
for n in (1,2,3,4,5,6):
 px=n*4
 rules['space-y-'+str(n)]=('av3-flow-'+str(n),'margin-top:'+str(px)+'px')
rules.update({
 'opacity-80':('av3-opacity-80','opacity:.8'),
 'opacity-50':('av3-opacity-50','opacity:.5'),
 'z-10':('av3-z-10','z-index:10'),
 'z-20':('av3-z-20','z-index:20'),
 'z-40':('av3-z-40','z-index:40'),
 'z-50':('av3-z-50','z-index:50'),
 'shadow-sm':('av3-shadow-sm','box-shadow:0 1px 2px 0 rgb(0 0 0 / .05)'),
 'shadow-md':('av3-shadow-md','box-shadow:0 4px 6px -1px rgb(0 0 0 / .1),0 2px 4px -2px rgb(0 0 0 / .1)'),
 'shadow-lg':('av3-shadow-lg','box-shadow:0 10px 15px -3px rgb(0 0 0 / .1),0 4px 6px -4px rgb(0 0 0 / .1)'),
 'shadow-xl':('av3-shadow-xl','box-shadow:0 20px 25px -5px rgb(0 0 0 / .1),0 8px 10px -6px rgb(0 0 0 / .1)'),
 'border-dashed':('av3-border-dashed','border-style:dashed'),
 'w-full':('av3-width-full','width:100%'),
 'h-full':('av3-height-full','height:100%'),
 'overflow-hidden':('av3-overflow-hidden','overflow:hidden'),
 'overflow-auto':('av3-overflow-auto','overflow:auto'),
 'overflow-x-auto':('av3-overflow-x-auto','overflow-x:auto'),
 'overflow-y-auto':('av3-overflow-y-auto','overflow-y:auto'),
 'text-white':('av3-text-white','color:#fff'),
 'bg-black':('av3-bg-black','background-color:#000'),
 'bg-black/40':('av3-bg-black-40','background-color:rgb(0 0 0 / .4)'),
 'bg-black/50':('av3-bg-black-50','background-color:rgb(0 0 0 / .5)'),
 'max-w-lg':('av3-width-max-lg','max-width:32rem'),
 'max-w-xl':('av3-width-max-xl','max-width:36rem'),
 'max-w-2xl':('av3-width-max-2xl','max-width:42rem'),
 'max-w-4xl':('av3-width-max-4xl','max-width:56rem'),
})
variants=('sm','md','lg','xl','hover','focus')
cnt=collections.Counter();used={}
for p in (r/'app/Views').rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(r/'app/Views').as_posix():continue
 data=p.read_bytes();old=data
 for token,(alias,decl) in rules.items():
  for var in variants:
   source=(var+':'+token).encode();new=('av3-'+var+'-'+alias[4:]).encode()
   data,n=re.subn(rb'(?<![\w:-])'+re.escape(source)+rb'(?=[\s\x27\x22<>]|$)',new,data)
   if n:used[new.decode()]=(var,token,decl);cnt[var+':'+token]+=n
  data,n=re.subn(rb'(?<![\w:-])'+re.escape(token.encode())+rb'(?=[\s\x27\x22<>]|$)',alias.encode(),data)
  if n:used[alias]=(None,token,decl);cnt[token]+=n
 if data!=old:p.write_bytes(data)
css=r/'public_html/assets/css/admin-v3/components/primitives.css'
s=css.read_text(encoding='utf8')
marker='/* Admin V3 design primitives — structured layout and color tokens */'
assert s.count(marker)==1
base=[];variantscss=[]
for alias,(var,token,decl) in used.items():
 sel='body[data-page^="admin"] .'+alias
 if token.startswith('space-y-'):
  sel+=' > :not([hidden]) ~ :not([hidden])'
 elif var in ('hover','focus'):sel+=':'+var
 txt=sel+'{'+decl+'}'
 if var in ('sm','md','lg','xl'):txt='@media(min-width:'+str({'sm':640,'md':768,'lg':1024,'xl':1280}[var])+'px){'+txt+'}'
 (base if var is None else variantscss).append(txt)
newblock='/* Admin V3 spacing, sizing, visibility, and shadow primitives */\n'+'\n'.join(base)+'\n'
s=s.replace(marker,newblock+marker)+'\n'+'\n'.join(variantscss)+'\n'
css.write_text(s,encoding='utf8')
print('ROUND3_MIGRATED',sum(cnt.values()),'classes;',len(used),'rules; TOP',cnt.most_common(20))
