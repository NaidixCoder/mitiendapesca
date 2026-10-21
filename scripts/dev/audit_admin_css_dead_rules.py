from pathlib import Path
import tinycss2,re,collections,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
source=''.join(p.read_text(encoding='utf8',errors='replace') for root in [r/'app',r/'public_html/assets/js'] for p in root.rglob('*') if p.suffix in ('.php','.js') and 'vendor' not in p.parts)
files=[r/'public_html/assets/css/admin-v3/components'/name for name in ['legacy-bridge.css','ui-admin-compat.css','foundations.css']]
classre=re.compile(r'(?<![\\w-])\\.([A-Za-z_][\\w-]*)')
summary={}
for f in files:
 text=f.read_text(encoding='utf8')
 rules=tinycss2.parse_stylesheet(text,skip_whitespace=True,skip_comments=True)
 qualified=[x for x in rules if x.type=='qualified-rule']
 unused=[]
 for item in qualified:
  sel=tinycss2.serialize(item.prelude).strip()
  classes=set(classre.findall(sel))
  if classes and all(re.search(r'(?<![\\w-])'+re.escape(c)+r'(?![\\w-])',source) is None for c in classes):
   unused.append(sel)
 summary[f.name]={'rules':len(qualified),'certainly_unused':len(unused),'selectors':unused}
 print(f.name,len(qualified),'unused candidates',len(unused))
 for line in unused[:22]:print('  ',line[:180])
(r/'docs/AUDITORIA_CSS_COMPATIBILIDAD.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2),encoding='utf8')
