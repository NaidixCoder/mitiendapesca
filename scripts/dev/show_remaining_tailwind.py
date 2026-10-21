from pathlib import Path
import json
r=Path(r'C:\xampp\htdocs\mi-pesca');d=json.loads((r/'docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json').read_text(encoding='utf8'))
for i,(s,count) in enumerate(d['top_classes'][:150],1):print(i,s,count)
