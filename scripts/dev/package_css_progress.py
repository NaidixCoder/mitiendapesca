from pathlib import Path
import datetime,zipfile
R=Path(r'C:\xampp\htdocs\mi-pesca');date=datetime.date.today().isoformat()
plan=R/'PLAN_CSS_UNIFICADO.md';s=plan.read_text(encoding='utf8')
s=s.replace('1. [COMPLETADA: INVENTARIO ESTÁTICO]','1. [EN SEGUIMIENTO: INVENTARIO + MAPA DE DEPENDENCIAS]')
s=s.replace('2. [DOCUMENTADA: IMPLEMENTACIÓN PENDIENTE]','2. [IMPLEMENTADA: BASE ADMIN V3]')
s=s.replace('3. [PENDIENTE] Biblioteca','3. [EN CURSO: COMPONENTES BASE] Biblioteca')
s=s.replace('4. [PENDIENTE] Dashboard','4. [MIGRADO EN CÓDIGO, FALTA VALIDAR VISUALMENTE] Dashboard')
s+='\n## Avance adicional '+date+'\n- Auditoría: 341 clases candidatas distintas; 116 aparecen también en JS (coincidencia textual, no implica selector válido). Detalle en docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json.\n- Base admin-v3 instalada en head.php para admin únicamente: tokens.css, components/common.css, pages/dashboard.css.\n- Dashboard: clases de presentación cambiadas de mt-* a av3-* y nueva hoja CSS; hooks JS admin-row-link, IDs de tendencia y shell intactos.\n- Verificación aún pendiente: render autenticado visual, accesibilidad y limpieza de todos los parciales. NO afirmar que Tailwind desapareció globalmente.\n- Etapas 5-9 no iniciadas. CDN Tailwind permanece porque otros módulos lo usan.\n'
plan.write_text(s,encoding='utf8')
with (R/'MANIFEST.md').open('a',encoding='utf8') as f:
 f.write('\n## Migración CSS unificado — avance '+date+'\n- app/Views/partials/head.php: carga admin-v3/tokens.css, admin-v3/components/common.css y admin-v3/pages/dashboard.css en admin; retira referencia a v2/pages/admin/dashboard-2026.css.\n- app/Views/pages/admin/dashboard/index.php: migración clases mt-* a av3-*, preservando DOM/IDs y hooks JS.\n- public_html/assets/css/admin-v3/{tokens.css,components/common.css,pages/dashboard.css}: nuevo sistema administrativo.\n- docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json, scripts/dev/audit_css_dependencies.py: clasificador de clases y coincidencias en JS.\n- public_html/assets/css/v2/pages/admin/dashboard-2026.css: archivo viejo desconectado, pendiente de eliminación verificada.\n- Sin cambios de apariencia pública previstos; no hay validación visual autenticada.\n')
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','app/Views/partials/head.php','app/Views/pages/admin/dashboard/index.php','public_html/assets/css/admin-v3/tokens.css','public_html/assets/css/admin-v3/components/common.css','public_html/assets/css/admin-v3/pages/dashboard.css','docs/AUDITORIA_CSS_2026.md','docs/AUDITORIA_CSS_2026.json','docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json','docs/ARQUITECTURA_ADMIN_V3.md','scripts/dev/audit_css_unification.py','scripts/dev/audit_css_dependencies.py','scripts/dev/migrate_dashboard_admin_v3.py','scripts/dev/create_admin_v3_base.py']
out=R/'CSS_UNIFICACION_AVANCE_01_04_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for p in paths:z.write(R/p,p)
with zipfile.ZipFile(out) as z:print('ZIP_READY',len(z.namelist()),out.stat().st_size,z.testzip())
