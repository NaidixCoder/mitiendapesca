from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
paths=[r/'public_html/assets/css/v2'/n for n in ('index.css','admin.css','public.css')]
files=[p for root in [r/'app',r/'config',r/'public_html',r/'scripts'] for p in root.rglob('*') if p.suffix in ('.php','.js','.css','.html') and p.is_file() and p not in paths]
for p in paths:
 assert p.is_file()
 key='css/v2/'+p.name
 consumers=[f for f in files if key in f.read_text(encoding='utf8',errors='replace')]
 assert not consumers,(key,[str(x) for x in consumers][:5])
backup=Path(r'C:\xampp\mi-pesca-backups')/('obsolete_v2_css_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in paths:z.write(p,p.relative_to(r).as_posix())
for p in paths:p.unlink()
print('OBSOLETE_V2_BUNDLES_REMOVED',len(paths),'backup:',backup)
