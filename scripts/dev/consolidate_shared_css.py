from pathlib import Path
import zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapping={
'css/v2/tokens.css':'css/shared/tokens.css',
'css/v2/base.css':'css/shared/base.css',
'css/v2/components/ui.css':'css/shared/components/ui.css',
'css/v2/components/buttons.css':'css/shared/components/buttons.css',
'css/v2/utilities.css':'css/shared/utilities.css',
'css/v2/components/no-tailwind-public.css':'css/shared/components/interaction.css'}
head=r/'app/Views/partials/head.php'
s=head.read_text(encoding='utf8')
for a,b in mapping.items():
 assert s.count("'"+a+"',")==2,a
for a,b in mapping.items():
 src=r/'public_html/assets'/a;target=r/'public_html/assets'/b
 assert src.is_file() and not target.exists()
 target.parent.mkdir(parents=True,exist_ok=True)
 target.write_bytes(src.read_bytes())
 s=s.replace("'"+a+"',","'"+b+"',")
head.write_text(s,encoding='utf8')
app=r/'public_html/assets/css/app.css'
css=app.read_text(encoding='utf8')
for old,new in [('v2/tokens.css','shared/tokens.css'),('v2/base.css','shared/base.css'),('v2/utilities.css','shared/utilities.css')]:
 assert old in css
 css=css.replace(old,new)
app.write_text(css,encoding='utf8')
for a in mapping:(r/'public_html/assets'/a).unlink()
print('SHARED_CSS_MOVED',len(mapping),'head refs updated; app.css imports updated')
