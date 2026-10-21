from pathlib import Path
import json,datetime,zipfile
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapping=json.loads((r/'docs/REUBICACION_CSS_PUBLICO.json').read_text(encoding='utf8'))
old=mapping['css/v2/pages/public/home-hero-v2.css']
mapping['css/v2/pages/public/home-hero-v2.css']='css/public/pages/home-hero.css'
(r/'docs/REUBICACION_CSS_PUBLICO.json').write_text(json.dumps(mapping,ensure_ascii=False,indent=2),encoding='utf8')
note='\n## Estructura de carga limpia — '+datetime.date.today().isoformat()+'\n- CSS exclusivos del público: 30 hojas ubicadas bajo css/public/, respaldadas y verificadas por build gate.\n- Arrays de carga renombrados a $adminStyles/$publicStyles; nombre obsoleto home-hero-v2.css reemplazado por home-hero.css sin cambio de reglas.\n- Verificados contrato CSS, integridad de referencias y aislamiento de foundations.\n- Aún quedan estilos legacy dentro del CSS administrativo y app.css para compatibilidad pública. No eliminados sin comprobación funcional final.\n'
for f in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/f).open('a',encoding='utf8') as file:file.write(note)
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','app/Views/partials/head.php','public_html/assets/css/public/pages/home-hero.css','docs/REUBICACION_CSS_PUBLICO.json','scripts/build/admin_css_scope.php','scripts/build/css_unification_contract.php','scripts/dev/verify_css_unification_integrity.py','scripts/dev/audit_css_unification.py','scripts/dev/audit_public_css_references.py','scripts/dev/audit_public_css_relative_urls.py','scripts/dev/check_admin_css_references.py','scripts/dev/inspect_admin_css_ownership.py','scripts/dev/move_public_css_to_domain.py','scripts/dev/remove_v2_loader_names.py']
out=r/'CSS_UNIFICACION_CARGADORES_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for f in paths:z.write(r/f,f)
 z.writestr('DELETIONS.txt','public_html/assets/css/public/pages/home-hero-v2.css\n')
with zipfile.ZipFile(out) as z:print('ZIP',len(z.namelist()),out.stat().st_size,z.testzip())
