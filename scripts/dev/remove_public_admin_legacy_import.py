from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
app=r/'public_html/assets/css/app.css'
s=app.read_text(encoding='utf8')
needle="@import url('./components/admin.css');\n"
assert s.count(needle)==1
files=[r/'public_html/assets/css/components/admin.css']+[r/'public_html/assets/css/components/admin'/name for name in ('base.css','ops.css','variants.css')]
assert all(p.is_file() for p in files)
backup=Path(r'C:\xampp\mi-pesca-backups')/('legacy_public_admin_import_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 z.write(app,app.relative_to(r).as_posix())
 for p in files:z.write(p,p.relative_to(r).as_posix())
app.write_text(s.replace(needle,''),encoding='utf8')
for p in files:p.unlink()
print('PUBLIC_ADMIN_LEGACY_IMPORT_REMOVED',len(files),'backup',backup)
