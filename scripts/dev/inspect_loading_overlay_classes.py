from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/ui/loading_overlay.js'
s=p.read_text(encoding='utf8')
strings=collections.Counter(re.findall(r'class="([^"]+)"',s))
print('CLASS_VARIANTS',len(strings))
for cls,n in strings.items():print(n,cls)
