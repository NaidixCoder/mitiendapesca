from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
d=r/'public_html/assets/css/admin-v3/components'
names=['ui-admin-compat.css','legacy-bridge.css','foundations.css']
h=r/'app/Views/partials/head.php';t=h.read_text(encoding='utf8')
for n in names:
 assert t.count("'css/admin-v3/components/"+n+"',")==1
old=[d/x for x in names]
assert all(x.is_file() for x in old)
new=d/'admin-components.css'
assert not new.exists()
backup=Path(r'C:\xampp\mi-pesca-backups')/('css_admin_components_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 z.write(h,h.relative_to(r).as_posix())
 for p in old:z.write(p,p.relative_to(r).as_posix())
new.write_text('\n\n'.join(p.read_text(encoding='utf8') for p in old)+'\n',encoding='utf8')
for n in names[1:]:
 t=t.replace("    'css/admin-v3/components/"+n+"',\n",'')
t=t.replace("    'css/admin-v3/components/"+names[0]+"',","    'css/admin-v3/components/admin-components.css',")
h.write_text(t,encoding='utf8')
for p in old:p.unlink()
print('COMPONENTS_CONSOLIDATED',new.stat().st_size,backup)
