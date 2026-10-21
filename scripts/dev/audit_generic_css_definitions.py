from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
for key in ['hidden','grid','truncate','flex','absolute','fixed','border','rounded','opacity-90']:
 matches=[]
 for p in (r/'public_html/assets/css').rglob('*.css'):
  if re.search(r'(?<![\w-])\.'+re.escape(key)+r'(?![\w-])',p.read_text(encoding='utf8',errors='replace')):
   matches.append(str(p.relative_to(r)))
 print(key,len(matches),matches[:9])
