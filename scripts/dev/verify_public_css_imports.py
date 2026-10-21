from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/css/public/app.css'
text=p.read_text(encoding='utf8')
urls=re.findall(r"@import url\(['\"]([^'\"]+)['\"]\)",text)
assert urls
for u in urls:
 asset=(p.parent/u).resolve()
 print(u,asset.is_file())
 assert asset.is_file()
print('PUBLIC_APP_IMPORTS_OK',len(urls))
