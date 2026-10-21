from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
start=h.index('$publicStyles = [');end=h.index('];',start)
assets=re.findall(r"'(css/[^']+\.css)'",h[start:end])
for item in assets:
 p=r/'public_html/assets'/item
 txt=p.read_text(encoding='utf8')
 urls=re.findall(r'url\(([^)]+)\)',txt,re.I)
 imports=re.findall(r'@import[^;]+;',txt,re.I)
 if urls or imports:print(item,'url',urls[:6],'imports',imports[:3])
print('PUBLIC_CSS_COUNT',len(assets))
