from pathlib import Path
import re,zipfile,datetime,html
r=Path(r'C:\xampp\htdocs\mi-pesca')
base=r/'app/Views/partials/admin'
targets=['settings/general/header.php','settings/envios/header.php','settings/pagos/header.php','settings/email_marketing/header.php','settings/index/header.php']
# Only direct plain-text titles/descriptions; preserve content with markup via buffered p to avoid losing emphasis
out={}
for rel in targets:
 p=base/rel;s=p.read_text(encoding='utf8')
 m=re.fullmatch(r'\s*<header class="av3-header">\s*<div>\s*<h1 class="av3-title">([^<]+)</h1>\s*<p class="av3-subtitle">(.*?)</p>\s*</div>\s*</header>\s*',s,re.S)
 if not m:print('SKIP',rel);continue
 title=m.group(1).strip()
 description=re.sub(r'<[^>]+>','',m.group(2)).strip()
 target=rel.split('/')[0]
 path={'settings':'Ajustes'}[target]
 crumbs=[('Inicio',"url('/admin')"),('Ajustes',"url('/admin/settings')"),(title.split('·')[-1].strip(),None)]
 lines=['<?php',"$adminHeaderTitle='"+title.replace("'","\\'")+"';","$adminHeaderDescription='"+description.replace("'","\\'")+"';","$adminHeaderBreadcrumbs=["]
 for label,link in crumbs:lines.append("  ['label'=>'"+label+"',"+ ("'href'=>"+link if link else '') +"],")
 lines+= ['];',"require BASE_PATH.'/app/Views/partials/admin/page_header.php';",'?>','']
 out[p]='\n'.join(lines)
backup=Path(r'C:\xampp\mi-pesca-backups')/('headers_settings_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in out:z.write(p,p.relative_to(r).as_posix())
for p,s in out.items():p.write_text(s,encoding='utf8')
print('MIGRATED_PARTIALS',len(out))
