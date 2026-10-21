from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
for p in list((R/'app/Views/pages/admin/facturacion').rglob('*.php'))+list((R/'app/Views/partials/admin/facturacion').rglob('*.php')):
 s=p.read_text(encoding='utf8',errors='replace')
 if 'facturacion_nav' in s:
  pos=s.index('facturacion_nav')
  print(str(p.relative_to(R)),'\n ',repr(s[max(0,pos-65):pos+75]))
