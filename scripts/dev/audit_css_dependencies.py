from pathlib import Path
import re,json,collections
R=Path(r'C:\xampp\htdocs\mi-pesca'); V=R/'app/Views'; J=R/'public_html/assets/js'
data=json.loads((R/'docs/AUDITORIA_CSS_2026.json').read_text(encoding='utf8'))
t=collections.Counter()
files=collections.defaultdict(set)
for path in V.rglob('*.php'):
 txt=path.read_text(encoding='utf8',errors='replace')
 for clstr in re.findall(r'class\s*=\s*["\x27]([^"\x27]+)',txt):
  for cl in clstr.split():
   if re.match(r'^(?:sm:|md:|lg:|xl:|2xl:|hover:|focus:|dark:|group-hover:)?(?:bg-|text-|p[trblxy]?-\d|m[trblxy]?-\d|w-\d|h-\d|gap-\d|grid-cols-|flex(?:$|-)|items-|justify-|rounded(?:$|-)|border(?:$|-)|font-(?:bold|medium|semibold)|shadow(?:$|-)|max-w-|min-w-|opacity-|z-\d|space-[xy]-)',cl):
    if not cl.startswith(('mt-','admin-','av3-')):
     t[cl]+=1; files[cl].add(path.relative_to(R).as_posix())
js=collections.defaultdict(list)
for p in J.rglob('*.js'):
 txt=p.read_text(encoding='utf8',errors='replace')
 for cl in t:
  if cl in txt:js[cl].append(p.relative_to(R).as_posix())
out={'candidate_unique':len(t),'top_classes':t.most_common(160),'js_cooccurrences':{k:v[:8] for k,v in js.items()},'high_priority_files':[]}
for p in V.rglob('*.php'):
 name=p.relative_to(R).as_posix()
 if 'admin' not in name:continue
 found=[cl for cl,paths in files.items() if name in paths]
 if found:out['high_priority_files'].append({'file':name,'utility_classes':len(found)})
out['high_priority_files'].sort(key=lambda x:-x['utility_classes'])
(R/'docs/AUDITORIA_TAILWIND_DEPENDENCIAS.json').write_text(json.dumps(out,ensure_ascii=False,indent=2),encoding='utf8')
print('CLASSIFY_DONE',len(t),'unique candidate classes;',len(js),'occur also in JS;',len(out['high_priority_files']),'admin files')
print('TOP',t.most_common(18))
