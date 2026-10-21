from pathlib import Path
import datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
start=datetime.datetime(2026,10,17,22,0).timestamp()
dirs=['app/Views','app/Controllers','public_html/assets/css','public_html/assets/js','scripts/build','scripts/dev','docs']
for folder in dirs:
 selected=[p for p in (r/folder).rglob('*') if p.is_file() and p.suffix in ('.php','.js','.css','.py','.json','.md') and p.stat().st_mtime>=start]
 print(folder,len(selected))
print('ROOT DOCS',[(p.name,datetime.datetime.fromtimestamp(p.stat().st_mtime).isoformat()) for p in [r/'PLAN_CSS_UNIFICADO.md',r/'MANIFEST.md']])
