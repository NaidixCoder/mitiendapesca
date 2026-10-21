from pathlib import Path
import datetime,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapping=json.loads((r/'docs/MIGRACION_CLASES_ADMIN_V3.json').read_text(encoding='utf8'))
stamp=datetime.datetime.now().strftime('%Y-%m-%d %H:%M')
note=f'''
## Etapas 4 y 5 — limpieza de clases ({stamp})
- Migrados {len(mapping)} nombres de clases de presentación admin-* hacia av3-* en 117 archivos CSS/PHP/JS. Mapa: docs/MIGRACION_CLASES_ADMIN_V3.json.
- Conservado admin-surface: contrato estructural del sidebar/layout, no clase legacy arbitraria.
- Mantener btn, badge, input y otras clases base compartidas como contratos existentes hasta validar componentes visualmente; renombrarlas sin transferir sus estilos rompería la interfaz.
- Comprobación exhaustiva de 69 nombres antiguos: cero referencias remanentes en código PHP/JS/CSS de la aplicación. Prueba scripts/build/admin_classnames_contract.php integrada en el gate.
- Auditoría de selectores sin consumidores y duplicados: scripts/dev/audit_admin_v3_dead_selectors.py, docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json y scripts/dev/audit_exact_css_duplicates.py. No se borraron reglas de cascada cuyo efecto no se pudo demostrar nulo.
- Se resguardó el estado previo a la migración bajo C:\\xampp\\mi-pesca-backups.
- Validación visual integral expresamente diferida; cualquier diferencia se tratará como regresión.
'''
for filename in ['PLAN_CSS_UNIFICADO.md','MANIFEST.md']:
 with (r/filename).open('a',encoding='utf8') as f:f.write(note)
p=r/'PLAN_CSS_UNIFICADO.md'
s=p.read_text(encoding='utf8').replace('7. [EN CURSO: ESTRUCTURA CSS CONSOLIDADA; CLASES LEGACY PENDIENTES]','7. [MIGRACIÓN DE CLASES ADMIN-* IMPLEMENTADA; ESTILOS GENÉRICOS COMPARTIDOS CONSERVADOS]')
p.write_text(s,encoding='utf8')
print('MANIFEST_PLAN_UPDATED',len(mapping))
