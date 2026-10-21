from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca');j=r/'public_html/assets/js/admin'
css=''.join(p.read_text(encoding='utf8',errors='replace') for p in (r/'public_html/assets/css/admin-v3').rglob('*.css'))
tokens=collections.Counter()
for p in j.rglob('*.js'):
 s=p.read_text(encoding='utf8',errors='replace')
 # Explicit className attribute and JS markup class
 for m in re.finditer(r'class="([^"]+)"|className\s*=\s*[\'"]([^\'"]+)',s):
  for token in (m.group(1) or m.group(2)).split():
   if re.match(r'^(?:hidden|grid|flex|inline-flex|relative|absolute|border|rounded|truncate|opacity-\d+|object-cover|items-center|justify-center|w-full|h-full|overflow-hidden|shrink-0|aspect-square|ring-\d+|ring-brand|last:border-b-0)$',token):
    tokens[(str(p.relative_to(r)),token)]+=1
print('ADMIN_JS_RAW_CLASSES',len(tokens),'occurrences',sum(tokens.values()))
for (file,tok),count in tokens.most_common(55):print(count,tok,file)
