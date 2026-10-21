from pathlib import Path
import ast,re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
def getvar(file,name):
 tree=ast.parse((r/file).read_text(encoding='utf8'))
 return ast.literal_eval(next(x.value for x in tree.body if isinstance(x,ast.Assign) and any(isinstance(t,ast.Name) and t.id==name for t in x.targets)))
m={**{k:(v,None) for k,v in getvar('scripts/dev/migrate_admin_primitives.py','mapping').items()},**getvar('scripts/dev/migrate_admin_spacing.py','m')}
cnt=collections.Counter()
for p in (r/'app/Views').rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(r/'app/Views').as_posix():continue
 txt=p.read_bytes()
 for var in ('sm','md','lg','xl','hover','focus','disabled'):
  for token in m:
   count=len(re.findall(re.escape((var+':'+token).encode())+rb'(?=[\s\x27\x22<>]|$)',txt))
   if count:cnt[(var,token)]+=count
print('RESPONSIVE_UTILITY_CONFLICTS',len(cnt),sum(cnt.values()),cnt.most_common(40))
