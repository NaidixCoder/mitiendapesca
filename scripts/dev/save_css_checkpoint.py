from pathlib import Path
import datetime,zipfile
r=Path(r'C:\xampp\htdocs\mi-pesca')
now=datetime.date.today().isoformat()
note='\n## Continuación '+now+'\n- Confirmadas 18 hojas administrativas cargadas y sin referencias ausentes.\n- Diez hojas admin-v3 preexistentes verificadas; se trasladaron además tres CSS exclusivos de admin a admin-v3/components sin alterar ni una regla (ui-admin-compat, legacy-bridge, foundations). Se desconectaron y retiraron sus antiguos archivos v2.\n- Quedan cinco hojas v2 compartidas por público/admin; NO se movieron para evitar regresiones públicas.\n- Tailwind CDN y clases utilitarias siguen activos; falta migración efectiva de plantillas y dependencias JS.\n'
p=r/'PLAN_CSS_UNIFICADO.md'
with p.open('a',encoding='utf8') as f:f.write(note)
with (r/'MANIFEST.md').open('a',encoding='utf8') as f:f.write(note)
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','app/Views/partials/head.php','app/Views/pages/admin/dashboard/index.php','app/Views/partials/admin/dashboard/featured_products.php','app/Views/partials/admin/dashboard/top_clients_30.php','docs/AUDITORIA_CSS_2026.md','docs/AUDITORIA_CSS_2026.json','docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json','docs/ARQUITECTURA_ADMIN_V3.md','scripts/dev/check_admin_css_references.py','scripts/dev/move_admin_exclusive_css.py']
paths+=['public_html/assets/css/'+str(p.relative_to(r/'public_html/assets/css')).replace('\\','/') for p in (r/'public_html/assets/css/admin-v3').rglob('*.css')]
deleted=['public_html/assets/css/v2/pages/admin/dashboard-2026.css','public_html/assets/css/v2/layouts/admin-shell.css','public_html/assets/css/v2/components/ui-admin.css','public_html/assets/css/v2/components/admin-beta-bridge.css','public_html/assets/css/v2/components/admin-foundations.css']
deleted+=['public_html/assets/css/v2/pages/admin/'+n for n in ('orders.css','products.css','products-v3.css','catalog-redesign.css','product-editor-redesign.css')]
deleted+=['public_html/assets/css/v2/pages/admin/facturacion/'+n for n in ('shared.css','nav.css','control.css')]
out=r/'CSS_UNIFICACION_CONTINUACION_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for p in paths:
  if (r/p).is_file():z.write(r/p,p)
 z.writestr('DELETIONS.txt','\n'.join(deleted)+'\n')
with zipfile.ZipFile(out) as z:print('ZIP_READY',len(z.namelist()),out.stat().st_size,z.testzip())
