from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca');today=datetime.date.today().isoformat()
note=f'''
## Continuación {today} — depuración de CSS de compatibilidad
- Auditoría de los tres estilos de compatibilidad: legacy-bridge.css (70 reglas, 72 clases), ui-admin-compat.css (54 reglas, 26 clases), foundations.css (28 reglas, 46 clases).
- Auditadas clases consumidas en plantillas PHP y módulos JavaScript; identificadas 17 reglas simples sin referencias en ui-admin-compat.css y retiradas con respaldo en C:\\xampp\\mi-pesca-backups.
- No se eliminaron legacy-bridge.css ni foundations.css: conservan consumidores activos o selectores compuestos que requieren validación.
- Scripts reproducibles: scripts/dev/audit_compat_classes_extended.py, audit_compat_removal_candidates.py, prune_unused_admin_css_rules.py y verify_css_unification_integrity.py.
- La herramienta de integridad confirmó 20 CSS administrativos y 37 CSS públicos, sin duplicados ni referencias rotas; CDN Tailwind ausente. Barrido amplio de utilidades identificadas: 0 coincidencias. Una búsqueda estática no equivale a validación visual/funcional.
- La eliminación de compatibilidad legacy completa sigue pendiente de migración de sus consumidores; no declarar terminado el proyecto.
'''
for f in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/f).open('a',encoding='utf8') as h:h.write(note)
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','docs/AUDITORIA_CSS_COMPATIBILIDAD.json','docs/AUDITORIA_CSS_COMPATIBILIDAD_CLASES.json','docs/AUDITORIA_CSS_REGLAS_CANDIDATAS.json','docs/AUDITORIA_TAILWIND_AMPLIADA.json','app/Views/partials/head.php','public_html/assets/css/admin-v3/components/ui-admin-compat.css','scripts/dev/audit_compat_classes_extended.py','scripts/dev/audit_compat_removal_candidates.py','scripts/dev/prune_unused_admin_css_rules.py','scripts/dev/verify_css_unification_integrity.py']
out=r/'CSS_UNIFICACION_CONTINUACION_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for file in paths:
  if (r/file).is_file():z.write(r/file,file)
with zipfile.ZipFile(out) as z:print('ZIP_UPDATED',len(z.namelist()),out.stat().st_size,z.testzip())
