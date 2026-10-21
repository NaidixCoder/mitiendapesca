from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for name in ['v2/index.css','v2/public.css','v2/admin.css','v2/pages/public/account-address-edit.css']:
 occurrences=[]
 for base in [r/'app',r/'config',r/'public_html',r/'scripts']:
  for p in base.rglob('*'):
   if not p.is_file() or p.suffix not in ('.php','.js','.css','.html','.json'):continue
   if p==r/'public_html/assets/css'/name:continue
   txt=p.read_text(encoding='utf8',errors='replace')
   if name in txt or Path(name).name in txt:
    occurrences.append(p.relative_to(r).as_posix())
 print(name,'external_mentions',len(occurrences),occurrences[:12])
