from pathlib import Path
import re,datetime,shutil,sys,json
R=Path(r'C:\xampp\htdocs\mi-pesca')
V=R/'app/Views/pages/admin';P=R/'app/Views/partials/admin'
labels={'facturacion':('Facturación','/admin/facturacion/control'),'settings':('Ajustes','/admin/settings'),'products':('Productos','/admin/products'),'brands':('Marcas','/admin/brands'),'categories':('Categorías','/admin/categories'),'catalog_tags':('Tags','/admin/catalog-tags'),'attributes':('Atributos','/admin/attributes'),'imports':('Importaciones','/admin/imports'),'users':('Usuarios','/admin/users'),'proveedores':('Proveedores','/admin/proveedores'),'marketing_campaigns':('Marketing','/admin/marketing/campanas'),'marketing_email':('Marketing','/admin/marketing/email'),'arca':('Facturación','/admin/facturacion/control'),'inventario':('Inventario','/admin/inventario'),'audit':('Auditoría','/admin/audit')}
def convert(path,src):
 h=re.search(r'<h1\b[^>]*>',src,re.I)
 if not h:return None,'without heading'
 tokens=list(re.finditer(r'<(/?)(div|header|section|form)\b[^>]*>',src,re.I))
 stack=[];nodes=[]
 for t in tokens:
  tag=t.group(2).lower()
  if not t.group(1):stack.append((tag,t.start(),t.end(),t.group(0)))
  else:
   for i in range(len(stack)-1,-1,-1):
    if stack[i][0]==tag:
     op=stack.pop(i);nodes.append((*op,t.start(),t.end()));break
 nodes=[x for x in nodes if x[1]<h.start()<x[5] and x[5]-x[1]<3500 and re.search(r'justify-between|av3-header|catalog-head|fisc-head|card-header|av3-card',x[3])]
 if not nodes:return None,'no matching header wrapper'
 n=min(nodes,key=lambda x:x[5]-x[1])
 inner=src[n[2]:n[4]]
 if len(inner)>2900 or inner.count('<h1')!=1 or '<table' in inner:return None,'complex header'
 m=re.search(r'<h1\b[^>]*>(.*?)</h1>',inner,re.S|re.I)
 if not m:return None,'bad heading'
 tail=inner[m.end():]
 close=re.search(r'</div>',tail,re.I)
 desc=None
 for x in re.finditer(r'<p\b[^>]*>(.*?)</p>',tail,re.S|re.I):
  if close is None or x.start()<close.start():desc=x;break
 if desc:
  dstart=m.end()+desc.start();dend=m.end()+desc.end()
  rest=inner[:m.start()]+inner[m.end():dstart]+inner[dend:];description=desc.group(1)
 else:rest=inner[:m.start()]+inner[m.end():];description=''
 for _ in range(5):rest=re.sub(r'<div\b[^>]*>\s*</div>','',rest,flags=re.S|re.I)
 if '<h2' in rest or '<h1' in rest or re.search(r'<p\b[^>]*class="[^"]*eyebrow',rest):return None,'extra content'
 rel=path.relative_to(V) if path.is_relative_to(V) else path.relative_to(P)
 section=rel.parts[0];sub=rel.parts[1] if len(rel.parts)>2 else ''
 label,href=labels.get(section,(section.title(),'/admin'))
 crumb="[['label'=>'Inicio','href'=>url('/admin')]"
 if section=='facturacion':
  crumb+=",['label'=>'Facturación','href'=>url('/admin/facturacion/control')]"
  if sub=='reportes':crumb+=",['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')]"
 elif section=='marketing_email':crumb+=",['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],['label'=>'Emails','href'=>url('/admin/marketing/email')]"
 elif section in ('brands','categories','catalog_tags','products','attributes','imports','proveedores','inventario'):
  crumb+=",['label'=>'Catálogo','href'=>url('/admin/products')]"
  if section!='products':crumb+=",['label'=>"+repr(label)+",'href'=>url("+repr(href)+")]"
 elif section=='settings':crumb+=",['label'=>'Ajustes','href'=>url('/admin/settings')]"
 else:crumb+=",['label'=>"+repr(label)+",'href'=>url("+repr(href)+")]"
 crumb+=",['label'=>$adminHeaderTitle]]"
 code='<?php\nob_start(); ?>'+m.group(1)+"<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));\n"
 if description:code+='ob_start(); ?>'+description+"<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));\n"
 else:code+="$adminHeaderDescription='';\n"
 if rest.strip():code+='ob_start(); ?>'+rest+"<?php $adminHeaderActions=trim(ob_get_clean());\n"
 else:code+="$adminHeaderActions='';\n"
 code+="$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:"+crumb+";\nrequire BASE_PATH.'/app/Views/partials/admin/page_header.php';\n?>"
 return src[:n[1]]+code+src[n[5]:],None
if __name__=='__main__':
 apply='--apply' in sys.argv
 changes={};skip={}
 for p in list(V.rglob('*.php'))+list(P.rglob('*header.php')):
  if p.name=='page_header.php' or any(x in p.as_posix() for x in ('settings/home_hero.php','settings/home_sections.php','attributes/index/header.php','imports/view/header.php','marketing_campaigns/show/header.php')):continue
  try:s=p.read_text(encoding='utf8')
  except UnicodeError:skip[str(p.relative_to(R))]='non UTF8';continue
  if '<h1' not in s or "partials/admin/page_header.php" in s:continue
  converted,reason=convert(p,s)
  if converted and converted!=s:changes[p]=converted
  elif reason:skip[str(p.relative_to(R))]=reason
 print('MODE', 'APPLY' if apply else 'DRY','CHANGES',len(changes),'SKIPS',len(skip))
 for p in changes:print('CANDIDATE',p.relative_to(R))
 for p,why in skip.items():print('SKIP',p,why)
 if apply:
  backup=Path(r'C:\xampp\mi-pesca-backups')/('header_full_batch_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
  for p in changes:
   dst=backup/p.relative_to(R);dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dst)
  for p,t in changes.items():p.write_text(t,encoding='utf8')
  (R/'docs/AUDITORIA_ADMIN_ENCABEZADOS_MIGRACION.json').write_text(json.dumps({'migrated':[str(p.relative_to(R)) for p in changes],'skipped':skip,'backup':str(backup)},ensure_ascii=False,indent=2),encoding='utf8')
  print('BACKUP',backup)
