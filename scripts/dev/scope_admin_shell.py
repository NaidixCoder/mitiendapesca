from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\css\v2\layouts\admin-shell.css')
s=p.read_text(encoding='utf-8')
assert s.count('body {')==2
assert '.nav-admin {' in s
s=s.replace('body {','body[data-page^="admin"] {')
s=s.replace('.nav-admin {','body[data-page^="admin"] .nav-admin {')
p.write_text(s,encoding='utf-8',newline='')
print('SCOPED 2 body declarations and legacy nav selector')
