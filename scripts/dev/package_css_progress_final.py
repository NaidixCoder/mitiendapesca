from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
with (r/'MANIFEST.md').open('a',encoding='utf8') as f:
 f.write('\n## Dashboard V3 — limpieza adicional '+datetime.date.today().isoformat()+'\n- Eliminado public_html/assets/css/v2/pages/admin/dashboard-2026.css después de verificar que head.php carga exclusivamente admin-v3/pages/dashboard.css.\n- Agregado scripts/dev/verify_admin_v3_dashboard.py con aserciones de CSS único, contenedor admin-surface, prefijo av3 y hooks preservados.\n- El build gate general había aprobado la migración V3 antes de esta eliminación; verificación focal posterior: ADMIN_V3_DASHBOARD_CONTRACT_OK.\n')
plan=r/'PLAN_CSS_UNIFICADO.md'
s=plan.read_text(encoding='utf8')
s+='\n## Limpieza Dashboard '+datetime.date.today().isoformat()+'\n- Retirada la hoja anterior dashboard-2026.css tras verificar desconexión de carga y equivalencia migrada. Contrato estático de Dashboard pasó.\n- Sigue pendiente validar la pantalla real en navegador y limpiar parciales aún heredados. No confundir esto con la migración integral de todo admin.\n'
plan.write_text(s,encoding='utf8')
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','app/Views/partials/head.php','app/Views/pages/admin/dashboard/index.php','public_html/assets/css/admin-v3/tokens.css','public_html/assets/css/admin-v3/components/common.css','public_html/assets/css/admin-v3/pages/dashboard.css','docs/AUDITORIA_CSS_2026.md','docs/AUDITORIA_CSS_2026.json','docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json','docs/ARQUITECTURA_ADMIN_V3.md','scripts/dev/audit_css_unification.py','scripts/dev/audit_css_dependencies.py','scripts/dev/verify_admin_v3_dashboard.py','scripts/dev/migrate_dashboard_admin_v3.py','scripts/dev/create_admin_v3_base.py']
out=r/'CSS_UNIFICACION_AVANCE_01_04_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for p in paths:z.write(r/p,p)
 z.writestr('DELETIONS.txt','public_html/assets/css/v2/pages/admin/dashboard-2026.css\n')
with zipfile.ZipFile(out) as z:print('ZIP_UPDATED',len(z.namelist()),'files; test:',z.testzip(),'size',out.stat().st_size)
