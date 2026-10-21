from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
paths=list((r/'app/Views/pages/admin').rglob('*.php'))+list((r/'app/Views/partials/admin').rglob('*header.php'))
pattern=re.compile(r'<header\b[^>]*>\s*<div\b[^>]*>\s*(?:<p\b[^>]*>.*?</p>\s*)?<h1\b[^>]*>.*?</h1>\s*(?:<p\b[^>]*>.*?</p>\s*)?</div>\s*(?:<div\b[^>]*>.*?</div>\s*)?</header>',re.S|re.I)
for p in paths:
 try:s=p.read_text(encoding='utf8')
 except UnicodeError:continue
 for m in pattern.finditer(s):
  if 'page_header.php' in p.name or 'header_card' in p.name:continue
  print(p.relative_to(r).as_posix(), len(m.group(0)))
