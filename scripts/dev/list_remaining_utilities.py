from pathlib import Path
import json
r=Path(r'C:\xampp\htdocs\mi-pesca');d=json.loads((r/'docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json').read_text(encoding='utf8'))
print('\n'.join(f'{i+1:03} {x[0]:45} {x[1]}' for i,x in enumerate(d['top_classes'])))
