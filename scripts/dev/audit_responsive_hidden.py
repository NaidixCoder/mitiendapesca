from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
count=0
for p in (r/'app/Views').rglob('*.php'):
 text=p.read_text(encoding='utf8',errors='replace')
 for a in re.findall(r'class\s*=\s*["\x27]([^"\x27]+)',text):
  if re.search(r'(?<![\w-])hidden(?![\w-])',a) and (('md-' in a) or ('lg-' in a) or ('sm-' in a)):
   print(p.relative_to(r).as_posix(),a[:180]);count+=1
print('RESPONSIVE_HIDDEN',count)
