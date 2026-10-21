from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
targets=['components/admin.css','components/admin/base.css','components/admin/ops.css','components/admin/variants.css']
roots=[r/'app',r/'public_html',r/'config',r/'scripts']
for token in targets:
 hits=[]
 for root in roots:
  for p in root.rglob('*'):
   if not p.is_file() or p.suffix not in ('.php','.css','.js','.html'):continue
   if token in p.read_text(encoding='utf8',errors='replace'):
    hits.append(p.relative_to(r).as_posix())
 print(token,len(hits),hits[:16])
