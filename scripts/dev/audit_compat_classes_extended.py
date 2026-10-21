from pathlib import Path
import re,collections,json,tinycss2
r=Path(r'C:\xampp\htdocs\mi-pesca')
root=r/'public_html/assets/css/admin-v3/components'
names=['legacy-bridge.css','ui-admin-compat.css','foundations.css']
templates=[p for base in [r/'app/Views',r/'public_html/assets/js'] for p in base.rglob('*') if p.suffix in ('.php','.js')]
source='\n'.join(p.read_text(encoding='utf8',errors='replace') for p in templates)
out={}
for name in names:
 p=root/name
 rules=tinycss2.parse_stylesheet(p.read_text(encoding='utf8'),skip_comments=True,skip_whitespace=True)
 sel=[]
 def walk(items):
  for item in items:
   if item.type=='qualified-rule':sel.append(tinycss2.serialize(item.prelude).strip())
   if item.type=='at-rule' and item.content is not None:walk(tinycss2.parse_rule_list(item.content,skip_whitespace=True,skip_comments=True))
 walk(rules)
 classes=sorted(set(re.findall(r'(?<![\w-])\.([A-Za-z_][\w-]*)', '\n'.join(sel))))
 active=[c for c in classes if re.search(r'(?<![\w-])'+re.escape(c)+r'(?![\w-])',source)]
 inactive=[c for c in classes if c not in active]
 out[name]={'rules':len(sel),'classes':len(classes),'referenced_in_source':len(active),'unreferenced_candidates':inactive}
 print(name,'rules',len(sel),'classes',len(classes),'referenced',len(active),'unreferenced',len(inactive))
 print('candidates',', '.join(inactive[:20]))
(r/'docs/AUDITORIA_CSS_COMPATIBILIDAD_CLASES.json').write_text(json.dumps(out,ensure_ascii=False,indent=2),encoding='utf8')
