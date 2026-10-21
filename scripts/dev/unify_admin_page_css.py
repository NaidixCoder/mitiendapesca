from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca');A=R/'public_html/assets';H=R/'app/Views/partials/head.php'
s=H.read_text(encoding='utf8')
groups={
'orders.css':['css/v2/pages/admin/orders.css'],
'products.css':['css/v2/pages/admin/products.css','css/v2/pages/admin/products-v3.css'],
'catalog.css':['css/v2/pages/admin/catalog-redesign.css'],
'product-editor.css':['css/v2/pages/admin/product-editor-redesign.css'],
'billing.css':['css/v2/pages/admin/facturacion/shared.css','css/v2/pages/admin/facturacion/nav.css','css/v2/pages/admin/facturacion/control.css']
}
outdir=A/'css/admin-v3/pages';outdir.mkdir(parents=True,exist_ok=True)
mapping={}
for dest,sources in groups.items():
 body=[]
 for src in sources:
  assert s.count("'"+src+"',")==1,src
  txt=(A/src).read_text(encoding='utf8')
  assert '@import' not in txt
  body.append('/* Source before consolidation: '+src+' */\n'+txt)
  mapping[src]='css/admin-v3/pages/'+dest
 (outdir/dest).write_text('\n\n'.join(body),encoding='utf8')
# replacements: remove all old lines then insert unique at first original slot to preserve order.
lines=s.splitlines(keepends=True)
seen=set();new=[]
for line in lines:
 m=re.search(r"'(css/v2/pages/admin/[^']+\.css)'",line)
 if m and m.group(1) in mapping:
  dest=mapping[m.group(1)]
  if dest not in seen:new.append(line.replace(m.group(1),dest));seen.add(dest)
 else:new.append(line)
H.write_text(''.join(new),encoding='utf8')
for src in mapping:(A/src).unlink()
print('PAGE_CSS_UNIFIED',len(groups),'sheets; old removed',len(mapping),'refs',sorted(seen))
