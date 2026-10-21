from pathlib import Path
import re,json,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
names=['legacy-bridge.css','ui-admin-compat.css','foundations.css']
cssdir=r/'public_html/assets/css/admin-v3/components'
files=[p for base in [r/'app/Views',r/'public_html/assets/js'] for p in base.rglob('*') if p.suffix in ('.php','.js')]
owners=collections.defaultdict(set)
for p in files:
 text=p.read_text(encoding='utf8',errors='replace')
 for name in names:
  css=(cssdir/name).read_text(encoding='utf8')
  classes=set(re.findall(r'(?<![\w-])\.([A-Za-z_][\w-]*)',css))
  for cl in classes:
   if re.search(r'(?<![\w-])'+re.escape(cl)+r'(?![\w-])',text):
    owners[(name,cl)].add(p.relative_to(r).as_posix())
payload={}
for name in names:
 cls={k[1]:sorted(v) for k,v in owners.items() if k[0]==name}
 payload[name]={'classes':cls,'used_classes':len(cls),'files_affected':len(set(f for arr in cls.values() for f in arr))}
 print(name,'used classes',len(cls),'files',payload[name]['files_affected'])
 for cl,fs in sorted(cls.items(),key=lambda t:-len(t[1]))[:8]:print(' ',cl,len(fs))
(r/'docs/MAPA_CONSUMIDORES_LEGACY_CSS.json').write_text(json.dumps(payload,ensure_ascii=False,indent=2),encoding='utf8')
