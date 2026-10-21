from pathlib import Path
import tinycss2,re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
folder=r/'public_html/assets/css/admin-v3'
files=[p for base in [r/'app/Views',r/'public_html/assets/js'] for p in base.rglob('*') if p.suffix in ('.php','.js')]
contents='\n'.join(p.read_text(encoding='utf8',errors='replace') for p in files)
for css in folder.rglob('*.css'):
 rules=tinycss2.parse_stylesheet(css.read_text(encoding='utf8'),skip_comments=True,skip_whitespace=True)
 unused=[]
 def walk(rs):
  for x in rs:
   if x.type=='at-rule' and x.content:walk(tinycss2.parse_rule_list(x.content,skip_comments=True,skip_whitespace=True))
   if x.type!='qualified-rule':continue
   selector=tinycss2.serialize(x.prelude).strip()
   classes=set(re.findall(r'(?<![\w-])\.([A-Za-z_][\w-]*)',selector))
   if classes and all(not re.search(r'(?<![\w-])'+re.escape(cl)+r'(?![\w-])',contents) for cl in classes):
    unused.append(selector)
 walk(rules)
 print(css.relative_to(folder),len(unused),'candidates',unused[:8])
