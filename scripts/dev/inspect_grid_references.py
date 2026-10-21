from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/admin/product_images/preview.js'
s=p.read_text(encoding='utf8')
for line in s.splitlines():
 if 'className' in line and 'grid' in line or "classList.contains('grid')" in line:print(line.strip())
