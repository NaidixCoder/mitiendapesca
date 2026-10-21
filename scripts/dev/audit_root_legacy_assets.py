from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
targets=['css/layout.css','css/tokens.css','css/app.css','css/aside.css','css/components/admin.css','css/components/account.css']
for token in targets:
 records=[]
 for base in [r/'app',r/'public_html',r/'config',r/'scripts']:
  for p in base.rglob('*'):
   if p.suffix not in ('.php','.js','.css','.html') or not p.is_file():continue
   if token== 'css/'+p.relative_to(r/'public_html/assets/css').as_posix() if p.is_relative_to(r/'public_html/assets/css') else False: continue
   if token in p.read_text(encoding='utf8',errors='replace'):records.append(p.relative_to(r).as_posix())
 print(token,len(records),records[:10])
