from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[r/'app/Views/pages/admin',r/'app/Views/partials/admin']
for root in roots:
 for p in root.rglob('*.php'):
  if p.name=='page_header.php':continue
  s=p.read_text(encoding='utf8',errors='replace')
  if '<h1' not in s or "partials/admin/page_header.php" in s:continue
  h=s.index('<h1')
  tokens=list(re.finditer(r'<(/?)(div|header|section|form)\b[^>]*>',s,re.I))
  stack=[];nodes=[]
  for m in tokens:
   if m.group(1)=='':stack.append((m.group(2).lower(),m.start(),m.end(),m.group(0)))
   else:
    tag=m.group(2).lower()
    for i in range(len(stack)-1,-1,-1):
     if stack[i][0]==tag:
      op=stack.pop(i)
      nodes.append((op[0],op[1],op[2],m.end(),op[3]))
      break
  cans=[node for node in nodes if node[1]<h<node[3] and len(s[node[1]:node[3]])<12000 and re.search('justify-between|av3-header|catalog-head|fisc-head|card-header|av3-card',node[4])]
  if cans:
   n=min(cans,key=lambda x:x[3]-x[1])
   print('OK ',p.relative_to(r).as_posix(),str(n[3]-n[1]),n[4][:90])
  else:print('NO ',p.relative_to(r).as_posix())
