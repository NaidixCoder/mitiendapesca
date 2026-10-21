from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/css/admin-v3/components/admin-components.css'
s=p.read_text(encoding='utf8')
names=sorted(set(re.findall(r'(?<![\w-])\.([a-zA-Z][\w-]*)',s)))
generic=[x for x in names if not x.startswith(('av3-','ui-','site-'))]
print('REMAINING_GENERIC_SELECTORS',len(generic))
print(', '.join(generic))
