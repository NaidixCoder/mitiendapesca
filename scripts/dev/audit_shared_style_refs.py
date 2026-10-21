from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
refs=['v2/tokens.css','v2/base.css','v2/components/ui.css','v2/components/buttons.css','v2/utilities.css','v2/components/no-tailwind-public.css']
for p in list((r/'public_html/assets/css').rglob('*.css')) + list((r/'scripts').rglob('*.php')):
 text=p.read_text(encoding='utf8',errors='replace')
 hits=[ref for ref in refs if ref in text]
 if hits:print(p.relative_to(r).as_posix(),':',', '.join(hits))
