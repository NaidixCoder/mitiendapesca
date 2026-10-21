from pathlib import Path
import tinycss2,re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
base=r/'public_html/assets/css/admin-v3/components/admin-components.css'
s=base.read_text(encoding='utf8')
parts=tinycss2.parse_stylesheet(s,skip_comments=False,skip_whitespace=False)
pairs={}
for i,item in enumerate(parts):
 if item.type!='qualified-rule':continue
 key=tinycss2.serialize(item.prelude).strip()
 body=tinycss2.serialize(item.content).strip()
 prev=pairs.get(key)
 if prev and body==prev[1]:print('IDENTICAL_DUPLICATE',key[:100],prev[0],i)
 pairs[key]=(i,body)
