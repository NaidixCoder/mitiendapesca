from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
plan=r/'PLAN_CSS_UNIFICADO.md'
s=plan.read_text(encoding='utf8')
s=s.replace('1. [EN CURSO] Auditoría','1. [COMPLETADA: INVENTARIO ESTÁTICO] Auditoría')
s=s.replace('2. [PENDIENTE] Arquitectura','2. [DOCUMENTADA: IMPLEMENTACIÓN PENDIENTE] Arquitectura')
s+= '\n## Avance '+datetime.date.today().isoformat()+'\n- Etapa 1: inventario estático generado (58 CSS, 373 PHP y 116 JS); archivos AUDITORIA_CSS_2026.md/json. No es una auditoría dinámica exhaustiva: falta revisar clases PHP/JS construidas en ejecución y falsos positivos.\n- Etapa 2: documento de arquitectura y migración creado en docs/ARQUITECTURA_ADMIN_V3.md. NO se han migrado componentes ni cargado hojas nuevas todavía.\n- Pendiente: clasificación efectiva de candidatos Tailwind, mapa JS por ruta y validación visual. No desconectar CDN ni bridges prematuramente.\n'
plan.write_text(s,encoding='utf8')
manifest=r/'MANIFEST.md'
with manifest.open('a',encoding='utf8') as f:
 f.write('\n## Unificación CSS — etapas 1 y 2 (documentación)\n- PLAN_CSS_UNIFICADO.md: plan maestro vivo.\n- docs/AUDITORIA_CSS_2026.md: inventario estático, alcance y riesgos.\n- docs/AUDITORIA_CSS_2026.json: resultados cuantificados y archivos con posibles utilidades Tailwind.\n- docs/ARQUITECTURA_ADMIN_V3.md: arquitectura, normas y checklist por módulo.\n- scripts/dev/audit_css_unification.py: auditoría reproducible.\n- Sin cambios en archivos CSS cargados, vistas, lógica, sidebar o público. Esta etapa es documental y diagnóstica.\n')
paths=['PLAN_CSS_UNIFICADO.md','MANIFEST.md','docs/AUDITORIA_CSS_2026.md','docs/AUDITORIA_CSS_2026.json','docs/ARQUITECTURA_ADMIN_V3.md','scripts/dev/audit_css_unification.py']
out=r/'CSS_UNIFICACION_ETAPAS_01_02_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',compression=zipfile.ZIP_DEFLATED) as z:
 for f in paths:z.write(r/f,f)
with zipfile.ZipFile(out) as z:print('ZIP_OK',out,'files:',len(z.namelist()),'bytes:',out.stat().st_size,'valid:',z.testzip() is None)
