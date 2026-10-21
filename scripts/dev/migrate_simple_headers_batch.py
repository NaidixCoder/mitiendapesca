from pathlib import Path
import re,zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
paths=list((r/'app/Views/pages/admin').rglob('*.php'))+list((r/'app/Views/partials/admin').rglob('*header.php'))
pat=re.compile(r'<header\b[^>]*>\s*<div\b[^>]*>\s*(?:<p\b[^>]*>.*?</p>\s*)?<h1\b[^>]*>.*?</h1>\s*(?:<p\b[^>]*>.*?</p>\s*)?</div>\s*(?:<div\b[^>]*>.*?</div>\s*)?</header>',re.S|re.I)
changes={}
for p in paths:
 if p.name=='page_header.php':continue
 try:s=p.read_text(encoding='utf8')
 except UnicodeError:continue
 matches=list(pat.finditer(s))
 if len(matches)!=1:continue
 m=matches[0];raw=m.group(0)
 if len(raw)>1100 or '<form' in raw or raw.count('<h1')!=1:continue
 h=re.search(r'<h1\b[^>]*>(.*?)</h1>',raw,re.S)
 ps=list(re.finditer(r'<p\b[^>]*>(.*?)</p>',raw,re.S))
 # Do not consume an eyebrow: only p AFTER heading as description
 desc=next((x.group(1) for x in ps if x.start()>h.end()),'')
 title=h.group(1)
 # find action container after the first closing div (simple-header pattern)
 trailing=raw.split('</div>',1)[1]
 am=re.search(r'<div\b[^>]*>(.*?)</div>',trailing,re.S)
 action=am.group(1) if am else ''
 def capture(fragment,var):
  if not fragment:return '$'+var+"='';\n"
  return "ob_start(); ?>"+fragment+"<?php $"+var+"=trim(strip_tags(ob_get_clean()));\n"
 section=p.relative_to(r/'app/Views').parts[2] if 'pages' in p.parts else p.relative_to(r/'app/Views/partials/admin').parts[0]
 if section=='admin':section=p.relative_to(r/'app/Views/pages/admin').parts[0]
 labels={'facturacion':('Facturación',"/admin/facturacion/control"),'settings':('Ajustes','/admin/settings'),'products':('Productos','/admin/products'),'brands':('Marcas','/admin/brands'),'categories':('Categorías','/admin/categories'),'attributes':('Atributos','/admin/attributes'),'marketing_campaigns':('Marketing','/admin/marketing/campanas'),'imports':('Importaciones','/admin/imports'),'users':('Usuarios','/admin/users'),'arca':('ARCA','/admin/facturacion/control'),'catalog_tags':('Tags','/admin/catalog-tags')}
 block='<?php\n'
 block+=capture(title,'adminHeaderTitle')
 block+=capture(desc,'adminHeaderDescription')
 if action:
  block+='ob_start(); ?>'+action+'<?php $adminHeaderActions=ob_get_clean();\n'
 else:block+="$adminHeaderActions='';\n"
 label,href=labels.get(section,(section.title(),'/admin'))
 block+="$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>"+repr(label)+",'href'=>url("+repr(href)+")],['label'=>$adminHeaderTitle]];\n"
 block+="require BASE_PATH.'/app/Views/partials/admin/page_header.php';\n?>"
 changes[p]=s[:m.start()]+block+s[m.end():]
print('CANDIDATES',len(changes))
backup=Path(r'C:\xampp\mi-pesca-backups')/('header_migration_batch_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in changes:z.write(p,p.relative_to(r).as_posix())
for p,s in changes.items():p.write_text(s,encoding='utf8');print('MIGRATED',p.relative_to(r))
print('BACKUP',backup)
