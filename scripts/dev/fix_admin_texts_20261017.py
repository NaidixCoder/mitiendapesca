from pathlib import Path
root=Path(r'C:\xampp\htdocs\mi-pesca')
dirs=['app/Views/pages/admin','app/Views/partials/admin','app/Controllers/Admin','app/Services/Admin','public_html/assets/js/admin']
chars='áéíóúñüÁÉÍÓÚÑÜ¿¡'
mapping={}
for good in chars:
 try:
  single=good.encode('utf-8').decode('cp1252')
  double=single.encode('utf-8').decode('cp1252')
 except UnicodeDecodeError:
  continue
 mapping[double]=good
 mapping[single]=good
 # mixed encoded forms seen in project
 if single.startswith('Ã'):
  mapping['Ãƒ'+single[1:]]=good
changed=[]
for d in dirs:
 for p in (root/d).rglob('*'):
  if not p.is_file() or p.suffix.lower() not in ('.php','.js'):continue
  try: original=p.read_text(encoding='utf-8')
  except UnicodeDecodeError:continue
  fixed=original
  for old,new in sorted(mapping.items(),key=lambda x:-len(x[0])):
   fixed=fixed.replace(old,new)
  if fixed!=original:
   p.write_text(fixed,encoding='utf-8',newline='')
   changed.append((str(p.relative_to(root)),sum(original.count(x) for x in mapping)))
print('CHANGED',len(changed))
for p,n in changed:print(n,p)
