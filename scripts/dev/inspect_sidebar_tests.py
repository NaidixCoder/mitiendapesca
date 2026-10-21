from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for root in ('scripts','tests'):
 p=r/root
 if not p.exists():continue
 matches=[]
 for f in p.rglob('*'):
  if f.is_file() and f.suffix in ('.py','.php','.js','.ps1','.bat','.md'):
   txt=f.read_text(encoding='utf8',errors='replace')
   if 'admin-v2' in txt:matches.append((f.relative_to(r).as_posix(),txt.count('admin-v2')))
 print(root,len(matches),matches[:60])
