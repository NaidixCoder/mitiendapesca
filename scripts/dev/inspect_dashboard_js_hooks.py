from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for f in (r/'public_html/assets/js/admin/dashboard').rglob('*.js'):
 for n,line in enumerate(f.read_text(encoding='utf8').splitlines(),1):
  if any(x in line for x in ("hidden","classList","trend-message")):print(f.name,n,line.strip()[:160])
