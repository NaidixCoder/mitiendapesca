from pathlib import Path
import json,zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapping=json.loads((r/'docs/REUBICACION_CSS_PUBLICO.json').read_text(encoding='utf8'))
report='\n## CSS público separado por dominio — '+datetime.date.today().isoformat()+'\n- Reubicadas 30 hojas exclusivas del público a public_html/assets/css/public/{pages,layouts,components}, sin cambiar las declaraciones ni el orden registrado en head.php.\n- Se dejó copia de seguridad previa bajo C:\\xampp\\mi-pesca-backups. No se modificó su presentación intencionalmente.\n- Hoja compartida en css/shared y hojas administrativas en css/admin-v3; subsisten CSS legacy y compatibilidad, aún no eliminados.\n- Auditoría de rutas resueltas: 20 admin y 37 público, sin faltantes. Gate general en curso al registrar este avance.\n'
for p in ['PLAN_CSS_UNIFICADO.md','MANIFEST.md']:
 with (r/p).open('a',encoding='utf8') as f:f.write(report)
files=['PLAN_CSS_UNIFICADO.md','MANIFEST.md','app/Views/partials/head.php','docs/REUBICACION_CSS_PUBLICO.json','scripts/dev/move_public_css_to_domain.py','scripts/build/gate.php','scripts/build/css_unification_contract.php']
files += ['public_html/assets/'+v for v in mapping.values()]
out=r/'CSS_UNIFICACION_PUBLICO_ADMIN_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for f in files:z.write(r/f,f)
 z.writestr('DELETIONS.txt','\n'.join('public_html/assets/'+k for k in mapping)+'\n')
with zipfile.ZipFile(out) as z:print('CHECKPOINT',len(z.namelist()),out.stat().st_size,z.testzip())
