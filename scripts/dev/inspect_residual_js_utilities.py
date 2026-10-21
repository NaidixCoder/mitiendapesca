from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
tokens=['border-emerald-300','text-amber-700','bg-amber-50','rounded-full','grid-cols-2','md:grid-cols-3','border-emerald-200','rounded-2xl','shadow-xl','text-base','w-2/3','border-b','rounded-md','flex-1','text-amber-800']
for t in tokens:
 lines=[]
 for p in (r/'public_html/assets/js/admin').rglob('*.js'):
  for i,line in enumerate(p.read_text(encoding='utf8').splitlines(),1):
   if t in line:lines.append((p.relative_to(r).as_posix(),i,line.strip()[:120]))
 print(t,lines[:6])
