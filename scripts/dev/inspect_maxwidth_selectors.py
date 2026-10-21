from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for term in ('max-w-6xl','max-w-4xl','mx-auto','container','group'):
 a=[]
 for f in (r/'public_html/assets/css').rglob('*.css'):
  n=f.read_text(encoding='utf8',errors='replace').count('.'+term)
  if n:a.append((f.relative_to(r).as_posix(),n))
 print(term,a[:25])
