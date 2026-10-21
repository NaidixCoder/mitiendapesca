from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
v=r/'app/Views/pages/admin'
h=r/'app/Views/partials/admin'
for p in sorted(v.rglob('*.php')):
 s=p.read_text(encoding='utf8',errors='replace')
 m=re.search(r"<h1\b[^>]*>(.*?)</h1>",s,re.I|re.S)
 inc=re.findall(r"(?:require BASE_PATH\s*\.\s*'([^']+)'|render_partial\('([^']+)')",s)
 headers=[(''.join(i)) for i in inc if 'header' in ''.join(i) and 'page_header.php' not in ''.join(i)]
 if 'partials/admin/page_header.php' not in s:
  print(str(p.relative_to(v)).ljust(47),'H1:'+('yes' if m else 'no'), 'INCL:'+','.join(headers[:2])[:75])
