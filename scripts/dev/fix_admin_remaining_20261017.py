from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
folders=['app/Views/pages/admin','app/Views/partials/admin','app/Controllers/Admin/Concerns','app/Services/Admin/Facturacion/ViewModels']
repl={
 'Ã‚·':'·','Ã—':'×','Ã¢â‚¬”':'—','Ã¢â‚¬“':'–','Ã¢â‚¬Â¦':'…',
 'ï¿½f¡':'á','ï¿½f³':'ó','ï¿½f©':'é','ï¿½f­':'í','ï¿½fº':'ú','ï¿½f±':'ñ',
 '�f¡':'á','�f³':'ó','�f©':'é','�f­':'í','�fº':'ú','�f±':'ñ',
 '�,·':' · ','Ã‚Â·':'·',
}
changed=[]
for folder in folders:
 for p in (r/folder).rglob('*.php'):
  try:old=p.read_text(encoding='utf-8')
  except:continue
  new=old
  for a,b in repl.items():new=new.replace(a,b)
  if old!=new:
   p.write_text(new,encoding='utf-8',newline='')
   changed.append(str(p.relative_to(r)))
print('CHANGED',len(changed),*changed,sep='\n')
