from pathlib import Path
import json,re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapping=json.loads((r/'docs/MIGRACION_CLASES_ADMIN_V3.json').read_text(encoding='utf8'))
files=[p for root in [r/'app',r/'public_html/assets'] for p in root.rglob('*') if p.suffix in ('.php','.js','.css')]
rest=collections.defaultdict(list)
for p in files:
 txt=p.read_text(encoding='utf8',errors='replace')
 for old in mapping:
  if re.search(r'(?<![\w-])'+re.escape(old)+r'(?![\w-])',txt):
   rest[old].append(str(p.relative_to(r)))
print('UNMIGRATED_CLASS_NAMES',len(rest))
for old,fs in sorted(rest.items()):print(old,fs[:9])
