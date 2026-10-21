from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
src=(r/'scripts/dev/migrate_admin_utilities_round2.py').read_text(encoding='utf8')
namespace={}
exec(src.split('files=[]')[0],namespace)
pairs=namespace['pairs']
aliases={}
for token,(alias,_) in pairs.items():
 aliases[alias]=token
 for v in ('sm','md','lg','xl','hover','focus'):
  aliases['av3-'+v+'-'+alias[4:]]=v+':'+token
changed=[]
for p in (r/'public_html/assets/js/admin').rglob('*.js'):
 b=p.read_bytes();orig=b
 for alias,token in sorted(aliases.items(),key=lambda x:-len(x[0])):
  pattern=rb'(?<![\w:-])'+re.escape(alias.encode())+rb'(?![\w-])'
  b=re.sub(pattern,lambda m:token.encode(),b)
 if b!=orig:p.write_bytes(b);changed.append(p.relative_to(r).as_posix())
print('JS_ROUND2_ROLLED_BACK',len(changed),'files')
