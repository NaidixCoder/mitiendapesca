from pathlib import Path
import ast,re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
def getvar(file,name):
 tree=ast.parse((r/file).read_text(encoding='utf8'))
 return ast.literal_eval(next(x.value for x in tree.body if isinstance(x,ast.Assign) and any(isinstance(t,ast.Name) and t.id==name for t in x.targets)))
one=getvar('scripts/dev/migrate_admin_primitives.py','mapping')
two=getvar('scripts/dev/migrate_admin_spacing.py','m')
mapping={**one,**{k:v[0] for k,v in two.items()}}
counts=collections.Counter();files=[]
for p in (r/'public_html/assets/js/admin').rglob('*.js'):
 s=p.read_bytes();old=s
 for token,alias in mapping.items():
  pattern=rb'(?<![\w:-])'+re.escape(token.encode())+rb'(?=[\s\x27\x22<>]|$)'
  s,n=re.subn(pattern,alias.encode(),s);counts[token]+=n
 if s!=old:p.write_bytes(s);files.append(p.relative_to(r).as_posix())
print('JS_CLASS_REFS_UPDATED',len(files),sum(counts.values()),files[:20])
