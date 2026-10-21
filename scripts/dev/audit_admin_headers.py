from pathlib import Path
import re,json,collections
root=Path(r'C:\xampp\htdocs\mi-pesca')
views=root/'app/Views/pages/admin'
php=list((root/'app').rglob('*.php'))
route_hits=[]
for p in php:
 if 'Views' in p.parts:continue
 s=p.read_text(encoding='utf8',errors='replace')
 for match in re.finditer(r'/admin(?:/[a-zA-Z0-9_{}:/.-]+)?',s):
  route_hits.append((str(p.relative_to(root)),match.group(0)))
rows=[]
for p in sorted(views.rglob('*.php')):
 s=p.read_text(encoding='utf8',errors='replace')
 def yes(pattern):return bool(re.search(pattern,s,re.I))
 rows.append(dict(file=p.relative_to(root).as_posix(),section=p.relative_to(views).parts[0],title_tags=len(re.findall(r'<h1\b',s,re.I)),header_tags=len(re.findall(r'<header\b',s,re.I)),breadcrumbs=yes(r'breadcrumb|miga[s]?'),uses_subnav=yes(r'admin/subnav|admin/.*subnav'),actions=yes(r'<button|<a\b|av3-page-actions|admin-actions'),header_partial=yes(r'page_header\.php|page-header\.php')))
out=root/'docs'
(out/'AUDITORIA_ADMIN_ENCABEZADOS.json').write_text(json.dumps(dict(view_count=len(rows),route_literals=sorted(set(x[1] for x in route_hits)),route_sources=sorted(set(x[0] for x in route_hits)),views=rows),ensure_ascii=False,indent=2),encoding='utf8')
lines=['# Auditoría de encabezados /admin/*','','Inventario estático de vistas y rutas referenciadas. Las rutas dinámicas y parciales requieren revisión.','', '- Vistas PHP administrativas: '+str(len(rows)),'- Rutas /admin citadas en código: '+str(len(set(x[1] for x in route_hits))),'- Vistas con h1: '+str(sum(x['title_tags']>0 for x in rows)),'- Con referencia a breadcrumb: '+str(sum(x['breadcrumbs'] for x in rows)),'','## Matriz','', '| Vista | H1 | Breadcrumb | Subnav | Acciones |','|---|---:|---|---|---|']
for row in rows:
 lines.append('| '+row['file'].removeprefix('app/Views/pages/admin/')+' | '+str(row['title_tags'])+' | '+('Sí' if row['breadcrumbs'] else 'No detectado')+' | '+('Sí' if row['uses_subnav'] else 'No')+' | '+('Sí' if row['actions'] else 'No detectadas')+' |')
lines+=['','## Criterios','- La ausencia de H1/breadcrumb en vista no implica ausencia en parcial.','- Revisar subnavegación y acciones antes de modificar.','- Jerarquía de breadcrumb funcional, no derivada ciegamente del URL.','- Preservar sidebar y estructura global.','- Implementación de un único componente después del inventario.']
(out/'AUDITORIA_ADMIN_ENCABEZADOS.md').write_text('\n'.join(lines)+'\n',encoding='utf8')
print('AUDIT_COMPLETE',len(rows),'views',len(set(x[1] for x in route_hits)),'route literals')
for name in sorted(set(x['section'] for x in rows)):
 items=[x for x in rows if x['section']==name]
 print(name,len(items),'h1',sum(i['title_tags']>0 for i in items))
