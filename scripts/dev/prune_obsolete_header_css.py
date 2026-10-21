from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca')
files={
'public_html/assets/css/admin-v3/pages/product-editor.css':[
'product-editor-head__intro','.av3-breadcrumb'],
'public_html/assets/css/admin-v3/pages/dashboard.css':['.av3-dashboard__heading','.av3-dashboard__eyebrow']
}
removed=[]
for file,keys in files.items():
 p=R/file;s=p.read_text(encoding='utf8');lines=s.splitlines(keepends=True)
 kept=[]
 for line in lines:
  if any(k in line for k in keys) and line.lstrip().startswith('body[data-page^="admin"]'):
   removed.append((file,line[:125]));continue
  kept.append(line)
 if kept!=lines:p.write_text(''.join(kept),encoding='utf8')
print('REMOVED_DEAD_HEADER_RULES',len(removed))
for a,b in removed:print(a,b)
