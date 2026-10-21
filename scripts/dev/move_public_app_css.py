from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
old=r/'public_html/assets/css/app.css';new=r/'public_html/assets/css/public/app.css'
oldacc=r/'public_html/assets/css/components/account.css';newacc=r/'public_html/assets/css/public/components/account.css'
h=r/'app/Views/partials/head.php'
s=h.read_text(encoding='utf8')
assert old.is_file() and oldacc.is_file() and not new.exists() and not newacc.exists()
assert "asset('css/app.css')" in s
backup=Path(r'C:\xampp\mi-pesca-backups')/('public_app_css_move_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in [old,oldacc,h]:z.write(p,p.relative_to(r).as_posix())
new.parent.mkdir(parents=True,exist_ok=True)
newacc.parent.mkdir(parents=True,exist_ok=True)
txt=old.read_text(encoding='utf8')
for src,dst in [('./shared/','../shared/'),('./components/account.css','./components/account.css')]:
 txt=txt.replace(src,dst)
new.write_text(txt,encoding='utf8')
newacc.write_bytes(oldacc.read_bytes())
h.write_text(s.replace("asset('css/app.css')","asset('css/public/app.css')"),encoding='utf8')
old.unlink();oldacc.unlink()
print('PUBLIC_APP_CSS_MOVED',backup)
