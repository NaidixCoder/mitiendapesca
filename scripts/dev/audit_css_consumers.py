from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
classes=['admin-v2-sidebar','admin-wrap','admin-container','admin-surface','nav-admin','ui-btn','v2-btn','btn-primary','admin-card','admin-table-wrap']
files=list((r/'app/Views').rglob('*.php'))+list((r/'public_html/assets/js').rglob('*.js'))
for name in classes:
 hits=[p for p in files if name in p.read_text(encoding='utf-8',errors='replace')]
 print(f'{name}: {len(hits)} files')
print('shell CSS body global:',sum(1 for x in (r/'public_html/assets/css/v2/layouts/admin-shell.css').read_text(encoding='utf-8').splitlines() if x.strip().startswith('body {')))
