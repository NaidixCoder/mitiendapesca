from pathlib import Path
import re,json,collections,datetime
R=Path(r'C:\xampp\htdocs\mi-pesca'); C=R/'public_html/assets/css'; V=R/'app/Views'; J=R/'public_html/assets/js'
css=list(C.rglob('*.css')); views=list(V.rglob('*.php')); js=list(J.rglob('*.js'))
rd=lambda p:p.read_text(encoding='utf8',errors='replace')
head=rd(V/'partials/head.php')
def block(name):
 m=re.search(r'\$'+name+r'\s*=\s*\[(.*?)\];',head,re.S)
 return re.findall(r"'(css/[^']+\.css)'",m.group(1)) if m else []
a=block('adminStyles'); p=block('publicStyles')
classes=re.compile(r'class\s*=\s*["\x27]([^"\x27]+)',re.I)
utility=re.compile(r'^(?:sm:|md:|lg:|xl:|hover:|focus:)?(?:flex|grid|block|inline|hidden|container|relative|absolute|fixed|sticky|items-|justify-|gap-|p[trblxy]?-[0-9]|m[trblxy]?-[0-9]|w-|h-|min-w-|max-w-|text-(?:xs|sm|base|lg|xl|[2-9]xl|left|right|center|gray-|slate-|white|red-)|bg-|border|rounded|shadow|font-(?:bold|medium|semibold)|overflow-|z-[0-9]|col-span-|grid-cols-|flex-col).*$')
hits={}
for file in views:
 arr=[]
 for n,line in enumerate(rd(file).splitlines(),1):
  for m in classes.finditer(line):
   for cl in m.group(1).split():
    if utility.match(cl): arr.append([n,cl])
 if arr:hits[file.relative_to(R).as_posix()]=arr
adminhits={k:v for k,v in hits.items() if '/admin/' in '/'+k}
out={'date':datetime.date.today().isoformat(),'css_total':len(css),'views_total':len(views),'js_total':len(js),'admin_css':a,'public_css':p,'shared_css':sorted(set(a)&set(p)),'admin_only_css':sorted(set(a)-set(p)),'public_only_css':sorted(set(p)-set(a)),'candidate_files':len(hits),'candidate_occurrences':sum(len(x) for x in hits.values()),'admin_candidate_files':len(adminhits),'admin_candidate_occurrences':sum(len(x) for x in adminhits.values()),'admin_candidate_samples':{k:v[:20] for k,v in sorted(adminhits.items(),key=lambda x:-len(x[1]))[:80]},'tailwind_cdn_references':[str(x.relative_to(R)) for x in views if 'cdn.tailwindcss.com' in rd(x)]}
d=R/'docs';d.mkdir(exist_ok=True)
(d/'AUDITORIA_CSS_2026.json').write_text(json.dumps(out,ensure_ascii=False,indent=2),encoding='utf8')
md=['# Auditoría CSS — etapa 1','', 'Auditoría estática de hojas cargadas y clases declaradas en plantillas. No equivale a validación visual.','','## Totales','- CSS: '+str(len(css)),'- Vistas PHP: '+str(len(views)),'- Archivos JS: '+str(len(js)),'- Coincidencias candidatas Tailwind: '+str(out['candidate_occurrences'])+' en '+str(len(hits))+' archivos PHP.','- Dentro de admin: '+str(out['admin_candidate_occurrences'])+' en '+str(len(adminhits))+' archivos.','','## CSS compartidos']+['- '+v for v in out['shared_css']]+['','## CSS exclusivos de admin']+['- '+v for v in out['admin_only_css']]+['','## Vistas administrativas con candidatos Tailwind']+['- '+k+': '+str(len(v))+' coincidencias; '+', '.join(sorted(set(x[1] for x in v))[:7]) for k,v in sorted(adminhits.items(),key=lambda x:-len(x[1]))[:80]]+['','## Riesgos','- Revisar falsos positivos, clases dinámicas y selectores JS antes de cambiar nada.','- Conservar geometría del sidebar y proteger frontend público.','- El inventario completo está en AUDITORIA_CSS_2026.json.']
(d/'AUDITORIA_CSS_2026.md').write_text('\n'.join(md)+'\n',encoding='utf8')
print('AUDIT_OK',json.dumps({k:out[k] for k in ['css_total','views_total','js_total','shared_css','candidate_occurrences','admin_candidate_occurrences']},ensure_ascii=False))
