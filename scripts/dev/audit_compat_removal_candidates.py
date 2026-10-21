from pathlib import Path
import re,tinycss2,collections,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
base=r/'public_html/assets/css/admin-v3/components'
sources=[p for folder in [r/'app/Views',r/'public_html/assets/js'] for p in folder.rglob('*') if p.suffix in ('.php','.js')]
source='\n'.join(p.read_text(encoding='utf8',errors='replace') for p in sources)
files=['legacy-bridge.css','ui-admin-compat.css','foundations.css']
summary={}
for filename in files:
 p=base/filename
 sheet=tinycss2.parse_stylesheet(p.read_text(encoding='utf8'),skip_whitespace=False,skip_comments=False)
 kept=[];removed=[]
 for rule in sheet:
  if rule.type!='qualified-rule':
   kept.append(rule);continue
  selector=tinycss2.serialize(rule.prelude)
  classes=set(re.findall(r'(?<![\w-])\.([A-Za-z_][\w-]*)',selector))
  # Exclude structural pseudo classes, broad descendant selectors and selectors with attribute/data hooks.
  safe=bool(classes) and ':' not in selector and '[' not in selector and ',' not in selector and '>' not in selector and '+' not in selector and '~' not in selector
  # Conservative: require every class identifier to be absent from all source, regardless of actual selector reachability.
  if safe and all(re.search(r'(?<![\w-])'+re.escape(c)+r'(?![\w-])',source) is None for c in classes):
   removed.append(selector.strip())
  else:kept.append(rule)
 summary[filename]={'removable_simple_rules':removed,'count':len(removed)}
 print(filename,'safe removals',len(removed))
 # Do not delete yet: this is a trace-only list; dynamic class composition can create false absences.
(r/'docs/AUDITORIA_CSS_REGLAS_CANDIDATAS.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2),encoding='utf8')
