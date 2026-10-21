from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
css=''.join(p.read_text(encoding='utf8',errors='replace') for p in (r/'public_html/assets/css/admin-v3').rglob('*.css'))
for c in ['flex','inline-flex','items-center','justify-center','gap-2','gap-3','w-0','w-1','w-12','w-14','h-1','h-12','h-14','border-slate-200','border-slate-300','border-slate-900','px-1','py-0','bg-white']:
 print(c,'AV3_MATCH',bool(re.search(r'\.av3-'+re.escape(c)+r'(?![\w-])',css)))
