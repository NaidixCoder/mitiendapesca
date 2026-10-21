from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
admin=r/'public_html/assets/css/admin-v3'
css=''.join(p.read_text(encoding='utf8',errors='replace') for p in admin.rglob('*.css'))
cl=collections.Counter(re.findall(r'(?<![\w-])\.([a-zA-Z][\w-]*)',css))
legacy=[x for x in cl if x.startswith(('admin-','badge','btn','alert-'))]
print('legacy selector classes',len(legacy))
for x in sorted(legacy)[:110]:print(x,cl[x])
