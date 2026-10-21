from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
notice='\n## Mapa de consumidores CSS — '+datetime.date.today().isoformat()+'\n- docs/MAPA_CONSUMIDORES_LEGACY_CSS.json identifica coincidencias de clases en PHP/JS para las tres hojas antiguas. Precaución: análisis léxico conservador, puede incluir coincidencias no funcionales; no utilizar como criterio único para borrar selectores.\n- Gate completo aprobado después de retirar 17 reglas simples y agregar contrato CSS: CSS_UNIFIED_GATE_OK.\n- Riesgo pendiente: legacy-bridge.css y foundations.css tienen dependencias distribuidas; antes de retirarlas, migrar componentes reales de sus vistas y comprobar estados.\n'
for name in ('MANIFEST.md','PLAN_CSS_UNIFICADO.md'):
 with (r/name).open('a',encoding='utf8') as f:f.write(notice)
paths=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','scripts/build/gate.php','scripts/build/css_unification_contract.php','scripts/dev/verify_css_unification_integrity.py','scripts/dev/audit_compat_classes_extended.py','scripts/dev/audit_compat_removal_candidates.py','scripts/dev/prune_unused_admin_css_rules.py','scripts/dev/map_legacy_css_consumers.py','docs/AUDITORIA_CSS_COMPATIBILIDAD_CLASES.json','docs/AUDITORIA_CSS_REGLAS_CANDIDATAS.json','docs/MAPA_CONSUMIDORES_LEGACY_CSS.json','public_html/assets/css/admin-v3/components/ui-admin-compat.css']
out=r/'CSS_UNIFICACION_CONTINUACION_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for name in paths:z.write(r/name,name)
with zipfile.ZipFile(out) as z:print('FINAL_CHECKPOINT',len(z.namelist()),out.stat().st_size,z.testzip())
