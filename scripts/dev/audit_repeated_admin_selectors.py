from pathlib import Path
import tinycss2,collections,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
folder=r/'public_html/assets/css/admin-v3'
summary={}
for p in folder.rglob('*.css'):
 rules=tinycss2.parse_stylesheet(p.read_text(encoding='utf8'),skip_whitespace=True,skip_comments=True)
 counts=collections.Counter()
 def walk(rs):
  for rule in rs:
   if rule.type=='qualified-rule':
    key=tinycss2.serialize(rule.prelude).strip()
    counts[key]+=1
   elif rule.type=='at-rule' and rule.content:
    walk(tinycss2.parse_rule_list(rule.content,skip_comments=True,skip_whitespace=True))
 walk(rules)
 repeats={k:n for k,n in counts.items() if n>1}
 if repeats:summary[p.relative_to(r).as_posix()]=repeats
 print(p.relative_to(folder).as_posix(),'repeated',len(repeats))
(r/'docs/AUDITORIA_SELECTORES_REPETIDOS_ADMIN.json').write_text(json.dumps(summary,indent=2,ensure_ascii=False),encoding='utf8')
