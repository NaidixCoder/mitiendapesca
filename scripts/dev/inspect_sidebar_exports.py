from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
for p in (r/'public_html/assets/js').rglob('*.js'):
 txt=p.read_text(encoding='utf8')
 if 'initAdminSidebarV2' in txt:print(p.relative_to(r))
