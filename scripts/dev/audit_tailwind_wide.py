from pathlib import Path
import re,collections,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
areas=[r/'app/Views',r/'public_html/assets/js']
pat=re.compile(r'(?<![\w-])(?:sm:|md:|lg:|xl:|2xl:|hover:|focus:|group-hover:|dark:)?(?:p[trblxy]?-\d[\w./\[\]%-]*|m[trblxy]?-\d[\w./\[\]%-]*|gap-\d[\w./\[\]%-]*|grid-cols-[\w-]+|col-span-[\w-]+|text-(?:xs|sm|base|lg|xl|[2-9]xl|slate-\d+|gray-\d+|red-\d+|white|black|\[color:[^\]]+\])|bg-(?:slate-\d+|gray-\d+|rose-\d+|emerald-\d+|white|black|amber-\d+|\[color:[^\]]+\])|border-(?:slate-\d+|gray-\d+|amber-\d+|rose-\d+|emerald-\d+)|rounded-(?:full|xl|2xl|lg|md|sm)|font-(?:bold|semibold|medium)|max-w-[\w\-.\[\]]+|min-w-[\w\-.\[\]]+|w-\d+|h-\d+|opacity-\d+|space-[xy]-\d+|shadow-[\w-]+|justify-between|items-center|inline-flex)(?![\w-])')
result=collections.defaultdict(list)
for base in areas:
 for p in base.rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  for n,line in enumerate(p.read_text(encoding='utf8',errors='replace').splitlines(),1):
   for hit in pat.findall(line):
    result[p.relative_to(r).as_posix()].append([n,hit])
for f,v in sorted(result.items(),key=lambda x:-len(x[1]))[:80]:
 print(f,len(v),' '.join(sorted(set(x[1] for x in v))[:12]))
out={'files':len(result),'matches':sum(len(v) for v in result.values()),'hits':result}
(r/'docs/AUDITORIA_TAILWIND_AMPLIADA.json').write_text(json.dumps(out,ensure_ascii=False,indent=2),encoding='utf8')
print('SUMMARY',out['files'],out['matches'])
