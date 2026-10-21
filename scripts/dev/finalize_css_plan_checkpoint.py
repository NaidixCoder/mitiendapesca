from pathlib import Path
import re,zipfile
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'PLAN_CSS_UNIFICADO.md';s=p.read_text(encoding='utf8')
for old,new in [
('8. [PENDIENTE] Remoción Tailwind','8. [IMPLEMENTADA A NIVEL DE CARGA Y BARRIDOS ESTÁTICOS, PENDIENTE VALIDACIÓN VISUAL] Remoción Tailwind'),
('9. [PENDIENTE] Revisión integral','9. [PRUEBAS AUTOMATIZADAS APROBADAS, PENDIENTE VALIDACIÓN VISUAL Y FUNCIONAL] Revisión integral'),
('7. [PENDIENTE] Resto de módulos administrativos','7. [EN CURSO: ESTRUCTURA CSS CONSOLIDADA; CLASES LEGACY PENDIENTES] Resto de módulos administrativos')]:
 s=s.replace(old,new)
p.write_text(s,encoding='utf8')
# Update the consolidated ZIP without duplicate entries.
zip_path=r/'CSS_UNIFICACION_INTEGRAL_INCREMENTAL.zip'
with zipfile.ZipFile(zip_path) as z:contents={name:z.read(name) for name in z.namelist()}
contents['PLAN_CSS_UNIFICADO.md']=p.read_bytes()
with zipfile.ZipFile(zip_path,'w',zipfile.ZIP_DEFLATED) as z:
 for name,data in contents.items():z.writestr(name,data)
with zipfile.ZipFile(zip_path) as z:print('PLAN_UPDATED_ZIP_OK',len(z.namelist()),zip_path.stat().st_size,z.testzip())
