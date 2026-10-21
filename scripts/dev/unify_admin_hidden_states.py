from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[r/'app/Views/partials/admin',r/'app/Views/pages/admin',r/'public_html/assets/js/admin']
files=[]
for root in roots:
 for p in root.rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  s=p.read_text(encoding='utf8',errors='replace')
  if 'ui-is-hidden' in s:
   p.write_text(s.replace('ui-is-hidden','av3-hidden'),encoding='utf8')
   files.append(p.relative_to(r).as_posix())
print('ADMIN_VISIBILITY_UNIFIED',len(files))
