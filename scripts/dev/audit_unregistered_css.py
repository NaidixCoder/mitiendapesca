from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
cssroot=r/'public_html/assets/css'
registered=[]
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
registered+=re.findall(r"'(css/[^']+\.css)'",h)
registered+=['css/app.css','css/aside.css']
allcss=list(cssroot.rglob('*.css'))
unused=[]
for p in allcss:
 rel='css/'+p.relative_to(cssroot).as_posix()
 if rel not in registered:
  refs=[]
  for f in (r/'public_html/assets/css').rglob('*.css'):
   if p==f:continue
   if p.name in f.read_text(encoding='utf8',errors='replace'):refs.append(str(f.relative_to(r)))
  unused.append((rel,refs[:3]))
print('CSS_ON_DISK',len(allcss),'NOT_DIRECTLY_REGISTERED',len(unused))
for x,refs in unused[:60]:print(x,'referenced_by_css:',refs)
