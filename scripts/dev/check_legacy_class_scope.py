from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
base=r/'app/Views'
for name in ['admin-card','admin-table','admin-page-head','admin-surface','btn-primary','admin-filter-row']:
 names=[]
 for p in list(base.rglob('*.php'))+list((r/'public_html/assets/js').rglob('*.js')):
  if re.search(r'(?<![\w-])'+re.escape(name)+r'(?![\w-])',p.read_text(encoding='utf8',errors='replace')):
   names.append(p.relative_to(r).as_posix())
 print(name,len(names),'PUBLIC',sum('/admin/' not in x for x in names),names[:7])
