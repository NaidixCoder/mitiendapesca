from pathlib import Path
import json,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
d=json.loads((r/'docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json').read_text(encoding='utf8'))
groups=collections.defaultdict(lambda:[0,0])
for x in d['high_priority_files']:
 path=x['file']; bits=path.replace('\\','/').split('/')
 try:
  i=bits.index('admin');module=bits[i+1] if len(bits)>i+1 else 'unknown'
 except ValueError:module='other'
 groups[module][0]+=1;groups[module][1]+=x['utility_classes']
for name,(num,classes) in sorted(groups.items(),key=lambda kv:-kv[1][1]):print(f'{name:25} {num:3} files {classes:4} distinct-file utility instances')
