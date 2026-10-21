from pathlib import Path
import datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
stamp=datetime.date.today().isoformat()
note=f'''
## Consolidación final de compatibilidad CSS ({stamp})
- Se consolidaron ui-admin-compat.css, legacy-bridge.css y foundations.css en una sola hoja administrativa: public_html/assets/css/admin-v3/components/admin-components.css. Se preservaron orden y contenido del CSS para no alterar la cascada.
- Se eliminaron las tres hojas de carga anterior y se actualizó app/Views/partials/head.php.
- Se actualizó scripts/build/admin_css_scope.php para exigir carga exclusiva y única de la nueva hoja.
- Se auditó la repetición de selectores por archivo: docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json. NO se borraron reglas repetidas que pueden ser correcciones intencionales de cascada.
- Atención: consolidar físicamente estos archivos NO implica que las clases legacy internas hayan desaparecido. Siguen existiendo selectores admin-* y otros contratos en uso. No declarar completada la migración semántica ni la validación visual.
- No crear ZIP adicional: cambios hechos directamente con Desktop Commander. Respaldos previos en C:\\xampp\\mi-pesca-backups.
'''
for name in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/name).open('a',encoding='utf8') as f:f.write(note)
print('DOCS_UPDATED')
