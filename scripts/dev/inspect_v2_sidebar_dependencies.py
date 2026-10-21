from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for root in ['app','public_html/assets/js','public_html/assets/css']:
 counts=[]
 for p in (r/root).rglob('*'):
  if p.suffix not in ('.php','.js','.css'):continue
  n=p.read_text(encoding='utf8',errors='replace').count('admin-v2-')
  if n:counts.append((str(p.relative_to(r)),n))
 print(root,len(counts),counts[:35])
