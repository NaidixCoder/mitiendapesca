from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for base in ['app/Views/partials/admin','app/Views/pages/admin','public_html/assets/js/admin']:
 files=list((r/base).rglob('*'))
 a=[p for p in files if p.suffix in ('.php','.js') and 'ui-is-hidden' in p.read_text(encoding='utf8',errors='replace')]
 b=[p for p in files if p.suffix in ('.php','.js') and 'av3-hidden' in p.read_text(encoding='utf8',errors='replace')]
 print(base,'ui',len(a),'av3',len(b))
 print('ui files',*[str(p.relative_to(r)) for p in a[:12]],sep='\n')
