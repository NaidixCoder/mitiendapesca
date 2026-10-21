from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
head=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
paths=re.findall(r"'(css/[^']+\.css)'",head)
missing=[p for p in paths if not (r/'public_html/assets'/p).exists()]
print('MISSING_STYLES',missing)
print('ADMIN_V3',*[p for p in paths if 'admin-v3/' in p],sep='\n - ')
old=['orders.css','products.css','products-v3.css','catalog-redesign.css','product-editor-redesign.css','shared.css','nav.css','control.css']
print('OLD_V2_REFS',*[p for p in paths if any(p.endswith('/'+x) for x in old)],sep='\n - ')
