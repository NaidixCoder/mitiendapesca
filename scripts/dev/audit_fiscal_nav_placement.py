from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
for root in [R/'app/Views/pages/admin/facturacion',R/'app/Views/partials/admin/facturacion']:
 for p in root.rglob('*.php'):
  s=p.read_text(encoding='utf8',errors='replace')
  if 'facturacion_nav' in s or "partials/admin/page_header.php" in s:
   print(p.relative_to(R),'NAV',s.count('facturacion_nav'),'HEADER',s.count('partials/admin/page_header.php'))
