from pathlib import Path
import re,zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca');h=r/'app/Views/partials/head.php'
s=h.read_text(encoding='utf8')
m=re.search(r'\$publicStyles\s*=\s*\[(.*?)\];',s,re.S)
assert m
public=re.findall(r"'(css/[^']+\.css)'",m.group(1))
mapping={}
for old in public:
 if old.startswith('css/v2/pages/public/'):
  new=old.replace('css/v2/pages/public/','css/public/pages/')
 elif old.startswith('css/v2/layouts/public-'):
  new=old.replace('css/v2/layouts/','css/public/layouts/')
 elif old.startswith('css/v2/components/'):
  new=old.replace('css/v2/components/','css/public/components/')
 else: continue
 mapping[old]=new
assert len(mapping)==30,len(mapping)
root=r/'public_html/assets'
backup=Path(r'C:\xampp\mi-pesca-backups')/('css_public_move_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 z.write(h,h.relative_to(r).as_posix())
 for old in mapping:z.write(root/old,(root/old).relative_to(r).as_posix())
for old,new in mapping.items():
 src=root/old;dst=root/new
 assert src.is_file() and not dst.exists()
 dst.parent.mkdir(parents=True,exist_ok=True)
 dst.write_bytes(src.read_bytes())
 s=s.replace("'"+old+"',","'"+new+"',")
h.write_text(s,encoding='utf8')
for old in mapping:(root/old).unlink()
(r/'docs/REUBICACION_CSS_PUBLICO.json').write_text(__import__('json').dumps(mapping,ensure_ascii=False,indent=2),encoding='utf8')
print('PUBLIC_CSS_MOVED',len(mapping),'backup',backup)
