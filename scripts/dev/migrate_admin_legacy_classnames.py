from pathlib import Path
import re,zipfile,datetime,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
cssroot=r/'public_html/assets/css/admin-v3'
cssfiles=list(cssroot.rglob('*.css'))
css='\n'.join(p.read_text(encoding='utf8',errors='replace') for p in cssfiles)
classes=set(re.findall(r'(?<![\w-])\.([A-Za-z_][\w-]*)',css))
mapping={x:'av3-'+x[6:] for x in classes if x.startswith('admin-') and not x.startswith('admin-v3-') and x!='admin-surface'}
# Keep admin-surface as structural contract for positioning, and admin-v3-* as preexisting page namespace.
print('CLASS_MIGRATION_SET',len(mapping))
files=cssfiles+[p for root in [r/'app/Views/partials/admin',r/'app/Views/pages/admin',r/'public_html/assets/js/admin'] for p in root.rglob('*') if p.suffix in ('.php','.js')]
changed={}
pattern=re.compile(r'(?<![\w-])(?:'+ '|'.join(re.escape(x) for x in sorted(mapping,key=len,reverse=True))+r')(?![\w-])')
for p in files:
 src=p.read_text(encoding='utf8',errors='replace')
 dst=pattern.sub(lambda m:mapping[m.group(0)],src)
 if dst!=src:changed[p]=dst
backup=Path(r'C:\xampp\mi-pesca-backups')/('css_admin_legacy_classnames_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in changed:z.write(p,p.relative_to(r).as_posix())
for p,s in changed.items():p.write_text(s,encoding='utf8')
(r/'docs/MIGRACION_CLASES_ADMIN_V3.json').write_text(json.dumps(mapping,ensure_ascii=False,indent=2),encoding='utf8')
print('REWRITTEN',len(changed),'files','BACKUP',backup)
print('SAMPLES',list(mapping.items())[:12])
