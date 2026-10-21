from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
styles=re.findall(r"'(css/[^']+\.css)'",h)
missing=[x for x in styles if not (r/'public_html/assets'/x).is_file()]
print('CSS_LINK_REFERENCES',len(styles),'MISSING',missing)
print('ADMIN_LIST',*[x for x in styles if x.startswith('css/admin-v3')],sep='\n')
print('V2_SHARED_LOADS',*[x for x in styles if x.startswith('css/v2/tokens') or x.startswith('css/v2/base') or x.startswith('css/v2/utilities')],sep='\n')
print('CDN_REMOVED','cdn.tailwindcss.com' not in h)
assert not missing
