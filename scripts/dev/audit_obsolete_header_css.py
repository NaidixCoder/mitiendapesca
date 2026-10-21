from pathlib import Path
import re,collections
R=Path(r'C:\xampp\htdocs\mi-pesca')
cssfiles=list((R/'public_html/assets/css/admin-v3').rglob('*.css'))
srcfiles=[p for root in [R/'app/Views/pages/admin',R/'app/Views/partials/admin',R/'public_html/assets/js'] for p in root.rglob('*') if p.suffix in ('.php','.js')]
source='\n'.join(p.read_text(encoding='utf8',errors='replace') for p in srcfiles)
patterns=['av3-breadcrumb','admin-breadcrumb','admin-v3-page-header','av3-page-head','av3-dashboard__heading','av3-dashboard__eyebrow','product-editor-head__intro','catalog-eyebrow','fisc-head','fisc-head-actions','av3-header','av3-title','av3-subtitle']
for cls in patterns:
 matches=[p.relative_to(R).as_posix() for p in cssfiles if re.search(r'\.'+re.escape(cls)+r'(?![\w-])',p.read_text(encoding='utf8',errors='replace'))]
 used=bool(re.search(r'(?<![\w-])'+re.escape(cls)+r'(?![\w-])',source))
 print(cls,'IN_CSS',len(matches),'USED',used,'FILES',matches[:4])
