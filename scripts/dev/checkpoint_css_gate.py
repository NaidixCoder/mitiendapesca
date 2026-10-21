from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
note='\n## Control de regresión CSS — '+datetime.date.today().isoformat()+'\n- Se agregó scripts/build/css_unification_contract.php al gate general en scripts/build/gate.php.\n- El contrato verifica: ausencia de CDN Tailwind, existencia de todas las hojas registradas en admin y público, ausencia de rutas duplicadas y presencia del layout admin-v3.\n- Prueba individual: [css-unificado] OK. La validación visual final sigue pendiente.\n'
for fn in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/fn).open('a',encoding='utf8') as f:f.write(note)
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','scripts/build/gate.php','scripts/build/css_unification_contract.php','scripts/dev/verify_css_unification_integrity.py','scripts/dev/audit_compat_classes_extended.py','scripts/dev/audit_compat_removal_candidates.py','scripts/dev/prune_unused_admin_css_rules.py','docs/AUDITORIA_CSS_COMPATIBILIDAD_CLASES.json','docs/AUDITORIA_CSS_REGLAS_CANDIDATAS.json','public_html/assets/css/admin-v3/components/ui-admin-compat.css','app/Views/partials/head.php']
out=r/'CSS_UNIFICACION_CONTINUACION_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for fn in paths:
  if (r/fn).exists():z.write(r/fn,fn)
with zipfile.ZipFile(out) as z:print('ZIP',len(z.namelist()),out.stat().st_size,'valid',z.testzip() is None)
