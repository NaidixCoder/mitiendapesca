from pathlib import Path
import datetime,zipfile,json
r=Path(r'C:\xampp\htdocs\mi-pesca');today=datetime.date.today().isoformat()
progress='\n## Continuación '+today+' — limpieza Tailwind en plantillas y módulos JS\n- Migradas las últimas tres plantillas PHP identificadas con utilidades Tailwind: partials/flash_inline.php, partials/catalogo/active-filter.php y pages/auth/reset_password.php.\n- Añadido css/v2/components/no-tailwind-public.css a las cargas pública y administrativa para estilos semánticos equivalentes; CSS público visualmente pendiente de validar al final.\n- Migrados los elementos dinámicos de recent_searches.js y ui/loading_overlay.js a clases site-*, incluidos estados de visibilidad. No se eliminó aún el CDN global: siguen consumidores JS administrativos.\n- audit_css_dependencies.py ahora reporta 0 candidatos en declaraciones class de vistas PHP (alcance de ese escáner). JS conserva clases y selectores utilitarios no renombrados; audit_js_tailwind_classes.py listó 66 parejas archivo/clase candidatas.\n- No declarar ausencia completa de Tailwind mientras exista carga global y JS sin migrar.\n'
for file in ['PLAN_CSS_UNIFICADO.md','MANIFEST.md']:
 with (r/file).open('a',encoding='utf8') as f:f.write(progress)
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','app/Views/partials/head.php','app/Views/partials/flash_inline.php','app/Views/partials/catalogo/active-filter.php','app/Views/pages/auth/reset_password.php','public_html/assets/js/tracking/recent_searches.js','public_html/assets/js/ui/loading_overlay.js','public_html/assets/css/v2/components/no-tailwind-public.css','docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json','scripts/dev/audit_css_dependencies.py','scripts/dev/audit_js_tailwind_classes.py','scripts/dev/migrate_remaining_public_tailwind.py','scripts/dev/migrate_recent_searches_css.py','scripts/dev/migrate_loading_overlay_css.py']
out=r/'CSS_UNIFICACION_PLANTILLAS_JS_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for p in paths:
  assert (r/p).exists(),p
  z.write(r/p,p)
with zipfile.ZipFile(out) as z:print('ZIP_OK',len(z.namelist()),out.stat().st_size,z.testzip())
